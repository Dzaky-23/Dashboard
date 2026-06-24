<?php

namespace App\Http\Controllers;

use App\Models\BpjsRefIcd;
use App\Models\Lb1Penta;
use App\Models\Puskesmas;
use App\Services\RecapLogicService;
use App\Services\RekapHarianService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PenyakitRecapController extends Controller
{
    protected RekapHarianService $service;

    public function __construct(RekapHarianService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $limit = 10;
        $yearInput = $request->input('year', date('Y'));
        $mapping = RecapLogicService::getMappingKodeToKecamatan();
        $puskesmasNames = RecapLogicService::getPuskesmasNames();

        // 1. Available Years
        $yearExpression = DB::getDriverName() === 'sqlite' ? "strftime('%Y', tanggal)" : "YEAR(tanggal)";
        $availableYears = DB::table('rekap_harian')
            ->selectRaw("{$yearExpression} as year")
            ->whereNotNull('tanggal')
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year');

        if ($availableYears->isEmpty()) {
            $availableYears = DB::table('lb1_penta')
                ->selectRaw("{$yearExpression} as year")
                ->whereNotNull('tanggal')
                ->groupBy('year')
                ->orderByDesc('year')
                ->pluck('year');
        }

        if ($availableYears->isEmpty()) {
            $availableYears = collect([date('Y')]);
        }

        // 2. Fetch main daily aggregate records grouped by Puskesmas
        $query = DB::table('rekap_harian as rh')
            ->join('puskesmas as p', 'rh.kode_puskesmas', '=', 'p.kode_p')
            ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
            ->select([
                'rh.kode_puskesmas as kpusk',
                'rh.kode_penyakit',
                DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"),
                DB::raw("SUM(rh.jumlah_kasus) as count")
            ]);

        if ($yearInput) {
            $query->whereRaw("{$yearExpression} = ?", [$yearInput]);
        }

        $rekapData = $query->groupBy('rh.kode_puskesmas', 'rh.kode_penyakit', 'icd.nmDiag')->get();
        $groupedByPusk = $rekapData->groupBy('kpusk');

        // 3. Kecamatan cards aggregates
        $listKecamatanUnik = array_unique(array_values($mapping));
        sort($listKecamatanUnik);

        $kecamatanDataList = [];
        foreach ($listKecamatanUnik as $kecName) {
            $puskesmasDiKecamatan = array_keys(array_filter($mapping, function ($kec) use ($kecName) {
                return $kec === $kecName;
            }));

            if (!empty($puskesmasDiKecamatan)) {
                $kecPenyakitAgg = collect();
                $totalKasusKec = 0;

                foreach ($puskesmasDiKecamatan as $pusk) {
                    if (!isset($groupedByPusk[$pusk])) {
                        continue;
                    }

                    $items = $groupedByPusk[$pusk];
                    $totalKasusKec += $items->sum('count');
                    foreach ($items as $row) {
                        $key = $row->kode_penyakit;
                        $kecPenyakitAgg[$key] = ($kecPenyakitAgg[$key] ?? 0) + $row->count;
                    }
                }

                $topPenyakit = null;
                if ($kecPenyakitAgg->isNotEmpty()) {
                    $topKey = $kecPenyakitAgg->sortDesc()->keys()->first();
                    $topPenyakit = (object)[
                        'kode_penyakit' => $topKey,
                        'count' => $kecPenyakitAgg[$topKey],
                    ];
                }

                $kecamatanDataList[$kecName] = [
                    'nama' => $kecName,
                    'total_puskesmas' => count($puskesmasDiKecamatan),
                    'total_kasus' => $totalKasusKec,
                    'top_penyakit' => $topPenyakit,
                    'list_puskesmas' => $puskesmasDiKecamatan
                ];
            }
        }

        // 4. Data for dropdowns
        $listPuskesmas = array_keys($mapping);
        sort($listPuskesmas);
        $listKecamatan = array_unique(array_values($mapping));
        sort($listKecamatan);

        $exportKecamatanOptions = DB::table('kecamatan')
            ->select('kode_kc as code', 'kecamatan as name')
            ->orderBy('kecamatan')
            ->get()
            ->map(fn($row) => [
                'code' => $row->code,
                'name' => strtoupper($row->name),
            ]);

        $exportPuskesmasOptions = DB::table('puskesmas as p')
            ->leftJoin('kecamatan as k', 'p.kode_kc', '=', 'k.kode_kc')
            ->select('p.kode_p', 'p.nama as p_nama', 'k.kode_kc', 'k.kecamatan as k_nama')
            ->orderBy('p.nama')
            ->get()
            ->map(function ($puskesmas) {
                return [
                    'code' => trim((string) $puskesmas->kode_p),
                    'name' => trim((string) ($puskesmas->p_nama ?: $puskesmas->kode_p)),
                    'kecamatan_code' => trim((string) $puskesmas->kode_kc),
                    'kecamatan_name' => strtoupper(trim((string) ($puskesmas->k_nama ?? $puskesmas->kode_kc))),
                ];
            });

        // 5. Global Stats
        $totalKasus = $rekapData->sum('count');
        $icdNames = $rekapData->pluck('nama_penyakit', 'kode_penyakit')->filter()->toArray();

        $topPenyakitAgg = $rekapData->groupBy('kode_penyakit')->map(function ($items) {
            return $items->sum('count');
        })->sortDesc();
        
        $topPenyakitData = $topPenyakitAgg->isNotEmpty()
            ? (object)['kode_penyakit' => $topPenyakitAgg->keys()->first(), 'count' => $topPenyakitAgg->first()]
            : null;
        $topPenyakitName = $topPenyakitData ? ($icdNames[$topPenyakitData->kode_penyakit] ?? $topPenyakitData->kode_penyakit) : '';
        $topPenyakit = $topPenyakitData ? $topPenyakitName . ' (' . $topPenyakitData->count . ' Kasus)' : 'Tidak Ada';

        $totalPuskesmas = count($listPuskesmas);
        $totalKecamatan = count($listKecamatan);

        // 6. Global Top Chart
        $from = $yearInput ? Carbon::create($yearInput, 1, 1)->startOfYear() : Carbon::create(1970, 1, 1);
        $to = $yearInput ? Carbon::create($yearInput, 12, 31)->endOfYear() : Carbon::create(2099, 12, 31);
        
        $topUmum = $this->service->queryTopUmum($from, $to, $limit);

        $chartData = $topUmum->map(function ($item) {
            return (object)[
                'label' => $item->kode_penyakit,
                'total' => (int) $item->total,
                'status' => $item->nama_penyakit,
            ];
        })->values();

        $maxChartWidth = $chartData->max('total') ?: 1;

        return view('recap.index', compact(
            'groupedByPusk', 'mapping', 'listPuskesmas', 'listKecamatan', 
            'kecamatanDataList', 'totalKasus', 'topPenyakit', 
            'totalPuskesmas', 'totalKecamatan', 'chartData', 'maxChartWidth',
            'availableYears', 'yearInput', 'puskesmasNames', 'icdNames',
            'exportKecamatanOptions', 'exportPuskesmasOptions'
        ));
    }

    public function globalTopChartData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
        ]);

        $year = (int) $validated['year'];
        $from = Carbon::create($year, 1, 1)->startOfYear();
        $to = Carbon::create($year, 12, 31)->endOfYear();

        $chartData = $this->service->queryTopUmum($from, $to, 10)
            ->map(function ($item) {
                return [
                    'label' => $item->kode_penyakit,
                    'total' => (int) $item->total,
                    'status' => $item->nama_penyakit,
                ];
            })
            ->values();

        return response()->json([
            'data' => $chartData,
            'max' => $chartData->max('total') ?: 1,
        ]);
    }

    public function searchIcd(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = strtoupper(trim($validated['q'] ?? ''));

        $results = BpjsRefIcd::query()
            ->selectRaw("TRIM(kdDiag) as code, COALESCE(NULLIF(TRIM(nmDiag), ''), TRIM(kdDiag)) as name")
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($nested) use ($query): void {
                    $nested->whereRaw('UPPER(TRIM(kdDiag)) LIKE ?', ['%' . $query . '%'])
                        ->orWhereRaw('UPPER(TRIM(nmDiag)) LIKE ?', ['%' . $query . '%']);
                });
            })
            ->orderByRaw('TRIM(kdDiag) ASC')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $results,
        ]);
    }

    public function show(Request $request, $puskesmas)
    {
        $limitInput = $request->input('limit');
        $limit = $limitInput === null ? 10 : (int) $limitInput;
        $limit = $limit > 10 ? 10 : $limit;
        $mapping = RecapLogicService::getMappingKodeToKecamatan();
        $kecamatan = $mapping[$puskesmas] ?? 'Tidak Diketahui';

        $lastMonth = Carbon::now()->subMonth();
        $periodType = $request->input('period_type', 'month');
        $year = $request->input('year', $lastMonth->year);
        $month = $request->input('month', $lastMonth->month);
        $semester = $request->input('semester', 1);
        $quarter = $request->input('quarter', 1);

        $startDate = null;
        $endDate = null;
        $isNotFinished = false;

        if ($periodType === 'all') {
            $startDate = Carbon::create(1970, 1, 1);
            $endDate = Carbon::create(2099, 12, 31);
        } elseif ($periodType === 'year') {
            $startDate = Carbon::create($year)->startOfYear();
            $endDate = Carbon::create($year)->endOfYear();
        } elseif ($periodType === 'semester') {
            if ($semester == 1) {
                $startDate = Carbon::create($year, 1, 1)->startOfMonth();
                $endDate = Carbon::create($year, 6, 1)->endOfMonth();
            } else {
                $startDate = Carbon::create($year, 7, 1)->startOfMonth();
                $endDate = Carbon::create($year, 12, 1)->endOfMonth();
            }
        } elseif ($periodType === 'quarter') {
            $startMonth = ($quarter - 1) * 3 + 1;
            $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
            $endDate = Carbon::create($year, $startMonth + 2, 1)->endOfMonth();
        } elseif ($periodType === 'month') {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        }

        if ($endDate && $endDate->isFuture()) {
            $isNotFinished = true;
        }

        $rekapRaw = $this->service->queryTopPerPuskesmas($startDate, $endDate, 9999, [$puskesmas]);
        
        $rekapData = $rekapRaw->map(function ($row) {
            return (object)[
                'kode_penyakit' => $row->kode_penyakit,
                'nama_penyakit' => $row->nama_penyakit,
                'count' => (int) $row->count,
            ];
        });

        $totalDiagnosaUnik = $rekapData->count();
        $warningLimit = null;
        if ($limit > $totalDiagnosaUnik && $limitInput !== null) {
            $warningLimit = "Angka N ($limit) melebihi jumlah total diagnosa unik yang ada ($totalDiagnosaUnik jenis). Semua jenis penyakit telah ditampilkan.";
            $limit = $totalDiagnosaUnik;
        }

        $totalKasus = $rekapData->sum('count');
        $rekapChartData = $rekapData->take($limit);
        $maxChartWidth = $rekapChartData->isNotEmpty() ? $rekapChartData->max('count') : 1;

        $icdNames = $rekapData->pluck('nama_penyakit', 'kode_penyakit')->filter()->toArray();

        return view('recap.puskesmas.show', compact('puskesmas', 'kecamatan', 'rekapData', 'totalKasus', 'limit', 'rekapChartData', 'maxChartWidth', 'totalDiagnosaUnik', 'warningLimit', 'isNotFinished', 'periodType', 'year', 'month', 'semester', 'quarter', 'icdNames'));
    }

    public function showKecamatan(Request $request, $kecamatan)
    {
        $limitInput = $request->input('limit');
        $limit = $limitInput === null ? 10 : (int) $limitInput;
        $mapping = RecapLogicService::getMappingKodeToKecamatan();
        
        $lastMonth = Carbon::now()->subMonth();
        $periodType = $request->input('period_type', 'month');
        $year = $request->input('year', $lastMonth->year);
        $month = $request->input('month', $lastMonth->month);
        $semester = $request->input('semester', 1);
        $quarter = $request->input('quarter', 1);

        $startDate = null;
        $endDate = null;
        $isNotFinished = false;

        if ($periodType === 'all') {
            $startDate = Carbon::create(1970, 1, 1);
            $endDate = Carbon::create(2099, 12, 31);
        } elseif ($periodType === 'year') {
            $startDate = Carbon::create($year)->startOfYear();
            $endDate = Carbon::create($year)->endOfYear();
        } elseif ($periodType === 'semester') {
            if ($semester == 1) {
                $startDate = Carbon::create($year, 1, 1)->startOfMonth();
                $endDate = Carbon::create($year, 6, 1)->endOfMonth();
            } else {
                $startDate = Carbon::create($year, 7, 1)->startOfMonth();
                $endDate = Carbon::create($year, 12, 1)->endOfMonth();
            }
        } elseif ($periodType === 'quarter') {
            $startMonth = ($quarter - 1) * 3 + 1;
            $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
            $endDate = Carbon::create($year, $startMonth + 2, 1)->endOfMonth();
        } elseif ($periodType === 'month') {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        }

        if ($endDate && $endDate->isFuture()) {
            $isNotFinished = true;
        }

        $puskesmasInKecamatan = array_keys(array_filter($mapping, function ($val) use ($kecamatan) {
            return $val === $kecamatan;
        }));

        if (empty($puskesmasInKecamatan)) {
            abort(404, 'Kecamatan tidak ditemukan.');
        }

        $kodeKecamatan = array_search($kecamatan, RecapLogicService::MAPPING_NAMA_KECAMATAN, true);
        if ($kodeKecamatan === false) {
            abort(404, 'Kecamatan tidak ditemukan.');
        }

        // Fetch rekap per puskesmas in this kecamatan
        $rekapByPusk = $this->service->queryTopPerPuskesmas($startDate, $endDate, $limit, $puskesmasInKecamatan)->groupBy('kpusk');

        // Fetch overall kecamatan rekap
        $rekapRaw = $this->service->queryTopPerKecamatan($startDate, $endDate, 9999, [$kodeKecamatan]);
        
        $rekapData = $rekapRaw->map(function ($row) {
            return (object)[
                'kode_penyakit' => $row->kode_penyakit,
                'nama_penyakit' => $row->nama_penyakit,
                'count' => (int) $row->count,
            ];
        });

        $totalDiagnosaUnik = $rekapData->count();
        $warningLimit = null;
        if ($limit > $totalDiagnosaUnik && $limitInput !== null) {
            $warningLimit = "Angka N ($limit) melebihi batas jenis diagnosa yang tercatat di Kecamatan ini ($totalDiagnosaUnik varian). Limit disesuaikan otomatis.";
            $limit = $totalDiagnosaUnik;
        }

        $totalKasus = $rekapData->sum('count');
        $totalPuskesmas = count($puskesmasInKecamatan);
        $rekapChartData = $rekapData->take($limit);
        $maxChartWidth = $rekapChartData->isNotEmpty() ? $rekapChartData->max('count') : 1;
        
        $puskesmasStats = [];
        $puskesmasNames = RecapLogicService::getPuskesmasNames();
        foreach ($puskesmasInKecamatan as $puskCode) {
            $puskName = $puskesmasNames[$puskCode] ?? $puskCode;
            if (!isset($rekapByPusk[$puskCode])) {
                continue;
            }

            $items = $rekapByPusk[$puskCode];
            $top = $items->sortByDesc('count')->first();
            $puskesmasStats[] = (object)[
                'nama' => $puskName,
                'total_kasus' => $items->sum('count'),
                'top_penyakit' => $top ? (object)[
                    'kode_penyakit' => $top->kode_penyakit,
                    'count' => $top->count,
                ] : null,
            ];
        }

        $icdNames = $rekapData->pluck('nama_penyakit', 'kode_penyakit')->filter()->toArray();

        return view('recap.kecamatan.show', compact('kecamatan', 'rekapData', 'totalKasus', 'totalPuskesmas', 'limit', 'rekapChartData', 'maxChartWidth', 'totalDiagnosaUnik', 'warningLimit', 'puskesmasStats', 'isNotFinished', 'periodType', 'year', 'month', 'semester', 'quarter', 'icdNames'));
    }

    public function fullList(Request $request, $puskesmas)
    {
        $year = (int) $request->input('year', date('Y'));
        $periodType = $request->input('period_type', 'month');
        $periodValue = (int) $request->input('period_value', date('n'));
        $search = $request->input('search');
        $sort = $request->input('sort', 'cases_desc');

        $startDate = null;
        $endDate = null;
        if ($periodType === 'month') {
            $startDate = Carbon::create($year, $periodValue, 1)->startOfMonth();
            $endDate = Carbon::create($year, $periodValue, 1)->endOfMonth();
        } elseif ($periodType === 'quarter') {
            $startMonth = ($periodValue - 1) * 3 + 1;
            $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
            $endDate = Carbon::create($year, $startMonth + 2, 1)->endOfMonth();
        } elseif ($periodType === 'semester') {
            if ($periodValue == 1) {
                $startDate = Carbon::create($year, 1, 1)->startOfMonth();
                $endDate = Carbon::create($year, 6, 1)->endOfMonth();
            } else {
                $startDate = Carbon::create($year, 7, 1)->startOfMonth();
                $endDate = Carbon::create($year, 12, 1)->endOfMonth();
            }
        } else {
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate = Carbon::create($year, 12, 31)->endOfYear();
        }

        $query = DB::table('rekap_harian as rh')
            ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
            ->select('rh.kode_penyakit', DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"), DB::raw('SUM(rh.jumlah_kasus) as count'))
            ->where('rh.kode_puskesmas', $puskesmas)
            ->whereBetween('rh.tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($search) {
            $query->where('rh.kode_penyakit', 'LIKE', '%' . $search . '%');
        }

        $query->groupBy('rh.kode_penyakit', 'icd.nmDiag');

        if ($sort === 'cases_asc') {
            $query->orderBy('count', 'asc');
        } elseif ($sort === 'alphabet_asc') {
            $query->orderBy('rh.kode_penyakit', 'asc');
        } elseif ($sort === 'alphabet_desc') {
            $query->orderByDesc('rh.kode_penyakit');
        } else {
            $query->orderByDesc('count');
        }

        $penyakits = $query->paginate(10)->withQueryString();
        $icdNames = $penyakits->pluck('nama_penyakit', 'kode_penyakit')->filter()->toArray();

        return view('recap.puskesmas.full_list', compact('puskesmas', 'year', 'periodType', 'periodValue', 'penyakits', 'search', 'sort', 'icdNames'));
    }

    public function fullListKecamatan(Request $request, $kecamatan)
    {
        $year = (int) $request->input('year', date('Y'));
        $periodType = $request->input('period_type', 'month');
        $periodValue = (int) $request->input('period_value', date('n'));
        $search = $request->input('search');
        $sort = $request->input('sort', 'cases_desc');

        $mapping = RecapLogicService::getMappingKodeToKecamatan();
        $puskesmasList = array_keys(array_filter($mapping, fn($k) => $k === $kecamatan));

        if (empty($puskesmasList)) {
            abort(404, 'Kecamatan tidak ditemukan.');
        }

        $kodeKecamatan = array_search($kecamatan, RecapLogicService::MAPPING_NAMA_KECAMATAN, true);
        if ($kodeKecamatan === false) {
            abort(404, 'Kecamatan tidak ditemukan.');
        }

        $startDate = null;
        $endDate = null;
        if ($periodType === 'month') {
            $startDate = Carbon::create($year, $periodValue, 1)->startOfMonth();
            $endDate = Carbon::create($year, $periodValue, 1)->endOfMonth();
        } elseif ($periodType === 'quarter') {
            $startMonth = ($periodValue - 1) * 3 + 1;
            $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
            $endDate = Carbon::create($year, $startMonth + 2, 1)->endOfMonth();
        } elseif ($periodType === 'semester') {
            if ($periodValue == 1) {
                $startDate = Carbon::create($year, 1, 1)->startOfMonth();
                $endDate = Carbon::create($year, 6, 1)->endOfMonth();
            } else {
                $startDate = Carbon::create($year, 7, 1)->startOfMonth();
                $endDate = Carbon::create($year, 12, 1)->endOfMonth();
            }
        } else {
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate = Carbon::create($year, 12, 31)->endOfYear();
        }

        $query = DB::table('rekap_harian as rh')
            ->join('puskesmas as p', 'rh.kode_puskesmas', '=', 'p.kode_p')
            ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
            ->select('rh.kode_penyakit', DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"), DB::raw('SUM(rh.jumlah_kasus) as count'))
            ->where('p.kode_kc', $kodeKecamatan)
            ->whereBetween('rh.tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($search) {
            $query->where('rh.kode_penyakit', 'LIKE', '%' . $search . '%');
        }

        $query->groupBy('rh.kode_penyakit', 'icd.nmDiag');

        if ($sort === 'cases_asc') {
            $query->orderBy('count', 'asc');
        } elseif ($sort === 'alphabet_asc') {
            $query->orderBy('rh.kode_penyakit', 'asc');
        } elseif ($sort === 'alphabet_desc') {
            $query->orderByDesc('rh.kode_penyakit');
        } else {
            $query->orderByDesc('count');
        }

        $penyakits = $query->paginate(10)->withQueryString();
        $icdNames = $penyakits->pluck('nama_penyakit', 'kode_penyakit')->filter()->toArray();

        return view('recap.kecamatan.full_list', compact('kecamatan', 'year', 'periodType', 'periodValue', 'penyakits', 'search', 'sort', 'icdNames'));
    }

    public function export()
    {
        abort(400, 'Export synchronous is disabled. Use asynchronous export.');
    }

    public function trendChartData(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $diseasesInput = $request->input('diseases', []);
        if (is_string($diseasesInput)) {
            $diseasesInput = json_decode($diseasesInput, true) ?: [];
        }

        $timeMode = $request->input('time_mode', 'year');
        
        $startDate = Carbon::create($year)->startOfYear();
        $endDate = Carbon::create($year)->endOfYear();

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
        $selectedLabels = $labels;
        $selectedMonths = range(1, 12);

        if ($timeMode === 'custom_months') {
            $customMonths = $request->input('custom_months', []);
            if (is_string($customMonths)) {
                $customMonths = json_decode($customMonths, true) ?: [];
            }
            $customMonths = array_map('intval', $customMonths);
            $customMonths = array_filter($customMonths, fn($m) => $m >= 1 && $m <= 12);
            if (!empty($customMonths)) {
                sort($customMonths);
                $selectedMonths = $customMonths;
                $selectedLabels = array_map(fn($m) => $labels[$m - 1], $selectedMonths);
            }
        } elseif ($timeMode === 'last_n') {
            $lastN = (int) $request->input('last_n', 6);
            if ($lastN < 1) $lastN = 1;
            
            $baseDate = ($year == date('Y')) ? Carbon::now() : Carbon::create($year, 12, 31);
            $startDate = $baseDate->copy()->subMonths($lastN - 1)->startOfMonth();
            $endDate = $baseDate->copy()->endOfMonth();
            
            $selectedMonths = [];
            $selectedLabels = [];
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $selectedMonths[] = $current->month;
                $selectedLabels[] = $labels[$current->month - 1] . ($current->year != $year ? " '{$current->format('y')}" : '');
                $current->addMonth();
            }
        } elseif ($timeMode === 'last_n_years') {
            $lastN = (int) $request->input('last_n', 5);
            if ($lastN < 1) $lastN = 1;
            
            $startDate = Carbon::create($year)->subYears($lastN - 1)->startOfYear();
            $endDate = Carbon::create($year)->endOfYear();
            
            $selectedMonths = [];
            $selectedLabels = [];
            for ($i = 0; $i < $lastN; $i++) {
                $y = $year - $lastN + 1 + $i;
                $selectedLabels[] = (string) $y;
            }
        }

        if (empty($diseasesInput) && $request->input('is_initial')) {
            $topRaw = $this->service->queryTopUmum($startDate, $endDate, 3);
            $diseasesInput = $topRaw->pluck('kode_penyakit')->toArray();
        }

        if (empty($diseasesInput)) {
            return response()->json(['trend' => [], 'labels' => $selectedLabels]);
        }

        $monthExpression = DB::getDriverName() === 'sqlite' ? "CAST(strftime('%m', tanggal) AS INTEGER)" : "MONTH(tanggal)";
        $yearExpression = DB::getDriverName() === 'sqlite' ? "CAST(strftime('%Y', tanggal) AS INTEGER)" : "YEAR(tanggal)";
        
        $query = DB::table('rekap_harian')
            ->selectRaw("kode_penyakit, {$monthExpression} as month, {$yearExpression} as yr, SUM(jumlah_kasus) as count")
            ->whereIn('kode_penyakit', $diseasesInput)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('kode_penyakit', DB::raw($yearExpression), DB::raw($monthExpression));

        if ($timeMode === 'custom_months' && !empty($selectedMonths)) {
            $query->whereIn(DB::raw($monthExpression), $selectedMonths);
        }

        $trendRaw = $query->get();

        $diseaseNames = [];
        foreach ($diseasesInput as $kode) {
            $icd = BpjsRefIcd::where('kdDiag', $kode)->first();
            $diseaseNames[$kode] = $icd && !empty($icd->nmDiag) ? $icd->nmDiag : $kode;
        }

        $trendData = [];
        foreach ($diseasesInput as $kode) {
            $monthlyData = [];
            if ($timeMode === 'last_n_years') {
                for ($i = 0; $i < $lastN; $i++) {
                    $y = $year - $lastN + 1 + $i;
                    $val = $trendRaw->where('kode_penyakit', $kode)->where('yr', $y)->sum('count');
                    $monthlyData[] = (int) $val;
                }
            } elseif ($timeMode === 'last_n') {
                $current = $startDate->copy();
                while ($current->lte($endDate)) {
                    $m = $current->month;
                    $y = $current->year;
                    $val = $trendRaw->where('kode_penyakit', $kode)->where('month', $m)->where('yr', $y)->sum('count');
                    $monthlyData[] = (int) $val;
                    $current->addMonth();
                }
            } else {
                foreach ($selectedMonths as $m) {
                    $val = $trendRaw->where('kode_penyakit', $kode)->where('month', $m)->sum('count');
                    $monthlyData[] = (int) $val;
                }
            }

            $trendData[] = [
                'kode' => $kode,
                'nama' => $diseaseNames[$kode],
                'data' => $monthlyData
            ];
        }

        return response()->json([
            'trend' => $trendData,
            'labels' => $selectedLabels
        ]);
    }

    public function pieChartData(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $limit = (int) $request->input('limit', 5);
        if ($limit < 1) $limit = 5;
        
        $scope = $request->input('scope', 'global');
        $scopeValue = $request->input('scope_value');

        $timeMode = $request->input('time_mode', 'year');
        $startDate = Carbon::create($year)->startOfYear();
        $endDate = Carbon::create($year)->endOfYear();

        $selectedMonths = [];
        if ($timeMode === 'custom_months') {
            $customMonths = $request->input('custom_months', []);
            if (is_string($customMonths)) {
                $customMonths = json_decode($customMonths, true) ?: [];
            }
            $customMonths = array_map('intval', $customMonths);
            $selectedMonths = array_filter($customMonths, fn($m) => $m >= 1 && $m <= 12);
        } elseif ($timeMode === 'last_n') {
            $lastN = (int) $request->input('last_n', 6);
            if ($lastN < 1) $lastN = 1;
            
            $baseDate = ($year == date('Y')) ? Carbon::now() : Carbon::create($year, 12, 31);
            $startDate = $baseDate->copy()->subMonths($lastN - 1)->startOfMonth();
            $endDate = $baseDate->copy()->endOfMonth();
        } elseif ($timeMode === 'last_n_years') {
            $lastN = (int) $request->input('last_n', 5);
            if ($lastN < 1) $lastN = 1;
            
            $startDate = Carbon::create($year)->subYears($lastN - 1)->startOfYear();
            $endDate = Carbon::create($year)->endOfYear();
        }

        $buildQuery = function() use ($startDate, $endDate, $timeMode, $selectedMonths, $scope, $scopeValue) {
            $query = DB::table('rekap_harian as rh')
                ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
                ->whereBetween('rh.tanggal', [$startDate->toDateString(), $endDate->toDateString()]);
            
            if ($timeMode === 'custom_months' && !empty($selectedMonths)) {
                $monthExpression = DB::getDriverName() === 'sqlite' ? "CAST(strftime('%m', rh.tanggal) AS INTEGER)" : "MONTH(rh.tanggal)";
                $query->whereIn(DB::raw($monthExpression), $selectedMonths);
            }

            if ($scope === 'puskesmas' && $scopeValue) {
                $query->where('rh.kode_puskesmas', $scopeValue);
            } elseif ($scope === 'kecamatan' && $scopeValue) {
                $query->join('puskesmas as p', 'rh.kode_puskesmas', '=', 'p.kode_p')
                      ->where('p.kode_kc', $scopeValue);
            }
            return $query;
        };

        $topQuery = $buildQuery()
            ->select('rh.kode_penyakit', DB::raw('SUM(rh.jumlah_kasus) as count'))
            ->groupBy('rh.kode_penyakit')
            ->orderByDesc('count')
            ->limit($limit);
            
        $diseasesInput = $topQuery->pluck('kode_penyakit')->toArray();

        if (empty($diseasesInput)) {
            return response()->json(['pie' => []]);
        }

        $pieRaw = $buildQuery()
            ->select('rh.kode_penyakit', DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"), DB::raw('SUM(rh.jumlah_kasus) as count'))
            ->whereIn('rh.kode_penyakit', $diseasesInput)
            ->groupBy('rh.kode_penyakit', 'icd.nmDiag')
            ->orderBy('count', 'desc')
            ->get();

        $pieData = [];
        foreach ($pieRaw as $row) {
            $pieData[] = [
                'kode' => $row->kode_penyakit,
                'nama' => $row->nama_penyakit,
                'count' => (int) $row->count
            ];
        }

        return response()->json([
            'pie' => $pieData
        ]);
    }
}
