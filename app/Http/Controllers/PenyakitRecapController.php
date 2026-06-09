<?php

namespace App\Http\Controllers;

use App\Models\BpjsRefIcd;
use App\Models\RekamMedis;
use App\Models\RekapPenyakitTop;
use App\Models\RefPuskesmas;
use App\Services\RecapLogicService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PenyakitRecapController extends Controller
{
    public function index(Request $request)
    {
        $limit = 10;
        $yearInput = $request->input('year', date('Y'));
        $mapping = \App\Services\RecapLogicService::getMappingKodeToKecamatan();
        $puskesmasNames = \App\Services\RecapLogicService::getPuskesmasNames();

        // Ambil daftar tahun unik yang ada datanya
        $availableYears = RekapPenyakitTop::query()
            ->select('year')
            ->where('scope', 'global')
            ->where('period_type', 'year')
            ->whereNotNull('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if ($availableYears->isEmpty()) {
            $availableYears = RekamMedis::selectRaw('YEAR(tanggal) as year')
                ->whereNotNull('tanggal')
                ->groupBy('year')
                ->orderByDesc('year')
                ->pluck('year');
        }

        $queryRekap = RekapPenyakitTop::query()
            ->select('kpusk', 'kode_penyakit', 'nama_penyakit', DB::raw('jumlah_kasus as count'))
            ->where('scope', 'puskesmas');

        if ($yearInput) {
            $queryRekap->where('period_type', 'year')->where('year', $yearInput);
        } else {
            $queryRekap->where('period_type', 'all');
        }

        // Menghitung Data Kecamatan secara Kolektif untuk UI Tampilan Grid Card
        $listKecamatanUnik = array_unique(array_values($mapping));
        sort($listKecamatanUnik);

        $cacheKeyRekap = 'rekap:index:' . ($yearInput ?: 'all');
        $rekapData = collect(Cache::remember($cacheKeyRekap, now()->addMinutes(10), function () use ($queryRekap) {
            return $queryRekap->orderBy('kpusk')
                ->orderByDesc('jumlah_kasus')
                ->get()
                ->map(function ($row) {
                    return [
                        'kpusk' => $row->kpusk,
                        'kode_penyakit' => $row->kode_penyakit,
                        'nama_penyakit' => $row->nama_penyakit,
                        'count' => (int) $row->count,
                    ];
                })
                ->all();
        }))->map(function ($row) {
            return (object) $row;
        });
            
        $groupedByPusk = $rekapData->groupBy('kpusk');

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
        
        // Data for dropdowns
        $listPuskesmas = array_keys($mapping);
        sort($listPuskesmas);
        $listKecamatan = array_unique(array_values($mapping));
        sort($listKecamatan);

        $exportKecamatanOptions = collect(RecapLogicService::MAPPING_NAMA_KECAMATAN)
            ->map(fn(string $name, string $code) => [
                'code' => $code,
                'name' => $name,
            ])
            ->values();

        $exportPuskesmasOptions = RefPuskesmas::query()
            ->select('kode_puskesmas', 'puskesmas', 'kode_kecamatan')
            ->orderBy('puskesmas')
            ->get()
            ->toBase()
            ->map(function (RefPuskesmas $puskesmas) {
                $kodeKecamatan = strtoupper(trim((string) $puskesmas->kode_kecamatan));

                return [
                    'code' => trim((string) $puskesmas->kode_puskesmas),
                    'name' => trim((string) ($puskesmas->puskesmas ?: $puskesmas->kode_puskesmas)),
                    'kecamatan_code' => $kodeKecamatan,
                    'kecamatan_name' => RecapLogicService::MAPPING_NAMA_KECAMATAN[$kodeKecamatan] ?? $kodeKecamatan,
                ];
            })
            ->values();
        
        // Data Global Stats Overview untuk Recap.Index
        $totalKasus = $rekapData->sum('count');
        $icdNames = $rekapData->pluck('nama_penyakit', 'kode_penyakit')
            ->filter()
            ->toArray();
        if (count($icdNames) < $rekapData->pluck('kode_penyakit')->unique()->count()) {
            $missingCodes = $rekapData->pluck('kode_penyakit')
                ->unique()
                ->diff(array_keys($icdNames))
                ->values()
                ->toArray();
            if (!empty($missingCodes)) {
                $icdNames = array_replace($icdNames, RecapLogicService::getIcdNames($missingCodes));
            }
        }

        $topPenyakitAgg = $rekapData->groupBy('kode_penyakit')->map(function ($items) {
            return $items->sum('count');
        })->sortDesc();
        $topPenyakitData = $topPenyakitAgg->isNotEmpty()
            ? (object)['kode_penyakit' => $topPenyakitAgg->keys()->first(), 'count' => $topPenyakitAgg->first()]
            : null;
        $topPenyakitName = $topPenyakitData ? ($icdNames[$topPenyakitData->kode_penyakit] ?? $topPenyakitData->kode_penyakit) : '';
        $topPenyakit = $topPenyakitData ? $topPenyakitName . ' (' . $topPenyakitData->count . ' Kasus)' : 'Tidak Ada';

        $totalPuskesmas = count(array_keys($mapping));
        $totalKecamatan = count(array_unique(array_values($mapping)));

        // --- GRAFIK GLOBAL UMUM ---
        $chartQuery = RekapPenyakitTop::query()
            ->select('kode_penyakit', 'nama_penyakit', DB::raw('jumlah_kasus as total'))
            ->where('scope', 'global');
            
        if ($yearInput) {
            $chartQuery->where('period_type', 'year')->where('year', $yearInput);
        } else {
            $chartQuery->where('period_type', 'all');
        }

        $chartData = $chartQuery->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(function ($item) use ($icdNames) {
                $name = $item->nama_penyakit ?? ($icdNames[$item->kode_penyakit] ?? $item->kode_penyakit);
                return (object)[
                    'label' => $item->kode_penyakit,
                    'total' => (int) $item->total,
                    'status' => $name,
                ];
            });

        $maxChartWidth = $chartData->max('total') ?: 1;

        return view('recap.index', compact(
            'groupedByPusk', 'mapping', 'listPuskesmas', 'listKecamatan', 
            'kecamatanDataList', 'totalKasus', 'topPenyakit', 
            'totalPuskesmas', 'totalKecamatan', 'chartData', 'maxChartWidth',
            'availableYears', 'yearInput', 'puskesmasNames', 'icdNames',
            'exportKecamatanOptions', 'exportPuskesmasOptions'
        ));
    }

    public function searchIcd(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = $this->normalizeFilterToken($validated['q'] ?? '');

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
        $mapping = \App\Services\RecapLogicService::getMappingKodeToKecamatan();
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

        if ($periodType !== 'all') {
            if ($periodType === 'year') {
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
        }

        $cacheKey = implode(':', [
            'rekap:show',
            strtoupper($puskesmas),
            $periodType,
            $year,
            $month,
            $semester,
            $quarter,
        ]);

        $rekapData = collect(Cache::remember($cacheKey, now()->addMinutes(10), function () use ($puskesmas, $periodType, $year, $month, $semester, $quarter) {
            $query = RekapPenyakitTop::query()
                ->select('kode_penyakit', 'nama_penyakit', DB::raw('jumlah_kasus as count'))
                ->where('scope', 'puskesmas')
                ->where('kpusk', $puskesmas)
                ->where('period_type', $periodType);

            if ($periodType === 'year') {
                $query->where('year', $year);
            } elseif ($periodType === 'semester') {
                $query->where('year', $year)->where('semester', $semester);
            } elseif ($periodType === 'quarter') {
                $query->where('year', $year)->where('quarter', $quarter);
            } elseif ($periodType === 'month') {
                $query->where('year', $year)->where('month', $month);
            }

            return $query->orderByDesc('jumlah_kasus')
                ->get()
                ->map(function ($row) {
                    return [
                        'kode_penyakit' => $row->kode_penyakit,
                        'nama_penyakit' => $row->nama_penyakit,
                        'count' => (int) $row->count,
                    ];
                })
                ->all();
        }))->map(function ($row) {
            return (object) $row;
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

        $icdNames = $rekapData->pluck('nama_penyakit', 'kode_penyakit')
            ->filter()
            ->toArray();
        if (count($icdNames) < $rekapData->pluck('kode_penyakit')->unique()->count()) {
            $missingCodes = $rekapData->pluck('kode_penyakit')
                ->unique()
                ->diff(array_keys($icdNames))
                ->values()
                ->toArray();
            if (!empty($missingCodes)) {
                $icdNames = array_replace($icdNames, RecapLogicService::getIcdNames($missingCodes));
            }
        }

        return view('recap.puskesmas.show', compact('puskesmas', 'kecamatan', 'rekapData', 'totalKasus', 'limit', 'rekapChartData', 'maxChartWidth', 'totalDiagnosaUnik', 'warningLimit', 'isNotFinished', 'periodType', 'year', 'month', 'semester', 'quarter', 'icdNames'));
    }

    public function showKecamatan(Request $request, $kecamatan)
    {
        $limitInput = $request->input('limit');
        $limit = $limitInput === null ? 10 : (int) $limitInput;
        $mapping = \App\Services\RecapLogicService::getMappingKodeToKecamatan();
        
        $lastMonth = Carbon::now()->subMonth();
        $periodType = $request->input('period_type', 'month');
        $year = $request->input('year', $lastMonth->year);
        $month = $request->input('month', $lastMonth->month);
        $semester = $request->input('semester', 1);
        $quarter = $request->input('quarter', 1);

        $startDate = null;
        $endDate = null;
        $isNotFinished = false;

        if ($periodType !== 'all') {
            if ($periodType === 'year') {
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
        }

        $puskesmasInKecamatan = array_keys(array_filter($mapping, function ($val) use ($kecamatan) {
            return $val === $kecamatan;
        }));

        if (empty($puskesmasInKecamatan)) {
            abort(404, 'Kecamatan tidak ditemukan.');
        }

        $cacheKey = implode(':', [
            'rekap:kecamatan',
            strtoupper($kecamatan),
            $periodType,
            $year,
            $month,
            $semester,
            $quarter,
        ]);

        $kodeKecamatan = array_search($kecamatan, RecapLogicService::MAPPING_NAMA_KECAMATAN, true);
        if ($kodeKecamatan === false) {
            abort(404, 'Kecamatan tidak ditemukan.');
        }

        $rekapByPusk = collect(Cache::remember($cacheKey, now()->addMinutes(10), function () use ($kodeKecamatan, $periodType, $year, $month, $semester, $quarter) {
            $query = RekapPenyakitTop::query()
                ->select('kpusk', 'kode_penyakit', 'nama_penyakit', DB::raw('jumlah_kasus as count'))
                ->where('scope', 'puskesmas')
                ->where('kode_kecamatan', $kodeKecamatan)
                ->where('period_type', $periodType);

            if ($periodType === 'year') {
                $query->where('year', $year);
            } elseif ($periodType === 'semester') {
                $query->where('year', $year)->where('semester', $semester);
            } elseif ($periodType === 'quarter') {
                $query->where('year', $year)->where('quarter', $quarter);
            } elseif ($periodType === 'month') {
                $query->where('year', $year)->where('month', $month);
            }

            return $query->orderBy('kpusk')
                ->orderByDesc('jumlah_kasus')
                ->get()
                ->map(function ($row) {
                    return [
                        'kpusk' => $row->kpusk,
                        'kode_penyakit' => $row->kode_penyakit,
                        'nama_penyakit' => $row->nama_penyakit,
                        'count' => (int) $row->count,
                    ];
                })
                ->all();
        }))->groupBy('kpusk')->map(function ($items) {
            return collect($items)->map(function ($row) {
                return (object) $row;
            });
        });

        // -------------------------
        // TARIK DATA KECAMATAN UTUH
        // -------------------------
        $cacheKeyKec = implode(':', [
            'rekap:kecamatan_total',
            strtoupper($kecamatan),
            $periodType,
            $year,
            $month,
            $semester,
            $quarter,
        ]);

        $rekapDataRaw = collect(Cache::remember($cacheKeyKec, now()->addMinutes(10), function () use ($kodeKecamatan, $periodType, $year, $month, $semester, $quarter) {
            $query = RekapPenyakitTop::query()
                ->select('kode_penyakit', 'nama_penyakit', DB::raw('jumlah_kasus as count'))
                ->where('scope', 'kecamatan')
                ->where('kode_kecamatan', $kodeKecamatan)
                ->where('period_type', $periodType);

            if ($periodType === 'year') {
                $query->where('year', $year);
            } elseif ($periodType === 'semester') {
                $query->where('year', $year)->where('semester', $semester);
            } elseif ($periodType === 'quarter') {
                $query->where('year', $year)->where('quarter', $quarter);
            } elseif ($periodType === 'month') {
                $query->where('year', $year)->where('month', $month);
            }

            return $query->orderByDesc('jumlah_kasus')->get()->map(function ($row) {
                return [
                    'kode_penyakit' => $row->kode_penyakit,
                    'nama_penyakit' => $row->nama_penyakit,
                    'count' => (int) $row->count,
                ];
            })->all();
        }));

        $rekapData = $rekapDataRaw->map(function ($row) {
            return (object) $row;
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
        
        // Extract array ringkasan masing-masing puskesmas di dalam wilayah kecamatan ini untuk Grid Card di view
        $puskesmasStats = [];
        foreach ($puskesmasInKecamatan as $puskName) {
            if (!isset($rekapByPusk[$puskName])) {
                continue;
            }

            $items = $rekapByPusk[$puskName];
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

        $icdNames = $rekapData->pluck('nama_penyakit', 'kode_penyakit')
            ->filter()
            ->toArray();
        if (count($icdNames) < $rekapData->pluck('kode_penyakit')->unique()->count()) {
            $missingCodes = $rekapData->pluck('kode_penyakit')
                ->unique()
                ->diff(array_keys($icdNames))
                ->values()
                ->toArray();
            if (!empty($missingCodes)) {
                $icdNames = array_replace($icdNames, RecapLogicService::getIcdNames($missingCodes));
            }
        }

        return view('recap.kecamatan.show_kecamatan', compact('kecamatan', 'rekapData', 'totalKasus', 'totalPuskesmas', 'limit', 'rekapChartData', 'maxChartWidth', 'totalDiagnosaUnik', 'warningLimit', 'puskesmasStats', 'isNotFinished', 'periodType', 'year', 'month', 'semester', 'quarter', 'icdNames'));
    }

    public function export(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300); 

        $validated = $request->validate([
            'format' => ['nullable', 'in:pdf,excel'],
            'top_n_umum' => ['nullable', 'integer', 'min:1'],
            'top_n_kecamatan' => ['nullable', 'integer', 'min:1'],
            'top_n_puskesmas' => ['nullable', 'integer', 'min:1'],
            'kecamatan_filter_mode' => ['nullable', 'in:all,selected'],
            'selected_kecamatan' => ['nullable', 'array'],
            'selected_kecamatan.*' => ['nullable', 'string', 'max:20'],
            'puskesmas_filter_mode' => ['nullable', 'in:all,selected'],
            'selected_puskesmas' => ['nullable', 'array'],
            'selected_puskesmas.*' => ['nullable', 'string', 'max:50'],
            'period_type' => ['nullable', 'in:year,semester,quarter,month,custom_date'],
            'year' => ['nullable', 'integer'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'semester' => ['nullable', 'integer', 'between:1,2'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'export_scope' => ['nullable', 'array'],
            'export_scope.*' => ['in:umum,kecamatan,puskesmas'],
            'include_prefixes' => ['nullable', 'array'],
            'include_prefixes.*' => ['nullable', 'string', 'max:20'],
            'exclude_prefixes' => ['nullable', 'array'],
            'exclude_prefixes.*' => ['nullable', 'string', 'max:20'],
            'include_codes' => ['nullable', 'array'],
            'include_codes.*' => ['nullable', 'string', 'max:20'],
            'exclude_codes' => ['nullable', 'array'],
            'exclude_codes.*' => ['nullable', 'string', 'max:20'],
            'include_icd' => ['nullable', 'string'],
            'exclude_icd' => ['nullable', 'string'],
            'exclude_exceptions' => ['nullable', 'string', 'max:255'],
        ]);

        $format = $request->input('format', 'pdf');
        $topNUmum = (int) $request->input('top_n_umum', 10);
        $topNKecamatan = (int) $request->input('top_n_kecamatan', 10);
        $topNPuskesmas = (int) $request->input('top_n_puskesmas', 10);
        $kecamatanFilterMode = $request->input('kecamatan_filter_mode', 'all');
        $puskesmasFilterMode = $request->input('puskesmas_filter_mode', 'all');
        $selectedKecamatan = $this->normalizeSelectionFilters($request->input('selected_kecamatan', []));
        $selectedPuskesmas = $this->normalizeSelectionFilters($request->input('selected_puskesmas', []));
        
        $periodType = $request->input('period_type', 'year');
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
        $semester = $request->input('semester', '1');
        $quarter = $request->input('quarter', '1');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $rawRanges = [];
        $aggregateMonths = [];

        if ($periodType === 'custom_date') {
            $request->validate([
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ]);

            $startDate = Carbon::parse($startDate)->toDateString();
            $endDate = Carbon::parse($endDate)->toDateString();

            list($rawRanges, $aggregateMonths) = $this->parseCustomDateRanges($startDate, $endDate);
        }
        
        $exportScopes = $request->input('export_scope', []);
        $includePrefixes = $this->mergePrefixFilters(
            $request->input('include_prefixes', []),
            $request->input('include_icd')
        );
        $excludePrefixes = $this->mergePrefixFilters(
            $request->input('exclude_prefixes', []),
            $request->input('exclude_icd')
        );
        $includeCodes = $this->normalizeCodeFilters($request->input('include_codes', []));
        $excludeCodes = $this->normalizeCodeFilters($request->input('exclude_codes', []));
        $excludeExceptions = $this->normalizePrefixFilters(
            $request->filled('exclude_exceptions') ? explode(',', $request->input('exclude_exceptions')) : []
        );
        
        $mapping = \App\Services\RecapLogicService::getMappingKodeToKecamatan();
        $puskesmasNames = \App\Services\RecapLogicService::getPuskesmasNames();
        $rawData = $periodType === 'custom_date'
            ? $this->getRawHistoryExportData($exportScopes, $startDate, $endDate, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions)
            : $this->getAggregateExportData($periodType, $year, $month, $semester, $quarter, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);

        if ($kecamatanFilterMode === 'selected' && !empty($selectedKecamatan)) {
            $rawData = $rawData->reject(function ($row) use ($selectedKecamatan) {
                return $row->scope === 'kecamatan' && !in_array($row->kode_kecamatan, $selectedKecamatan, true);
            })->values();
        }

        if ($puskesmasFilterMode === 'selected' && !empty($selectedPuskesmas)) {
            $rawData = $rawData->reject(function ($row) use ($selectedPuskesmas) {
                return $row->scope === 'puskesmas' && !in_array($row->kpusk, $selectedPuskesmas, true);
            })->values();
        }

        // 1. Data Top N Umum
        $topUmum = collect();
        if (in_array('umum', $exportScopes)) {
            $groupedUmum = $rawData->where('scope', 'global')->groupBy('kode_penyakit');
            foreach ($groupedUmum as $kode => $items) {
                $topUmum->push((object)[
                    'kode_penyakit' => $kode,
                    'nama_penyakit' => $items->first()->nama_penyakit ?? null,
                    'count' => $items->sum('count')
                ]);
            }
            $topUmum = $topUmum->sortByDesc('count')->take($topNUmum)->values();
        }

        // 2. Data Top N Per Kecamatan
        $kecamatanData = [];
        if (in_array('kecamatan', $exportScopes)) {
            $listKecamatan = array_unique(array_values($mapping));
            sort($listKecamatan);

            if ($kecamatanFilterMode === 'selected' && !empty($selectedKecamatan)) {
                $listKecamatan = array_values(array_filter($listKecamatan, function (string $kecName) use ($selectedKecamatan) {
                    $kodeKecamatan = array_search($kecName, RecapLogicService::MAPPING_NAMA_KECAMATAN, true);
                    return $kodeKecamatan !== false && in_array($kodeKecamatan, $selectedKecamatan, true);
                }));
            }

            foreach ($listKecamatan as $kecName) {
                $kodeKecamatan = array_search($kecName, RecapLogicService::MAPPING_NAMA_KECAMATAN, true);
                if ($kodeKecamatan === false) {
                    $kecamatanData[$kecName] = collect();
                    continue;
                }

                $dataKec = $rawData->where('scope', 'kecamatan')->where('kode_kecamatan', $kodeKecamatan);
                
                $groupedKec = $dataKec->groupBy('kode_penyakit');
                $topKec = collect();
                foreach ($groupedKec as $kode => $items) {
                    $topKec->push((object)[
                        'kode_penyakit' => $kode,
                        'nama_penyakit' => $items->first()->nama_penyakit ?? null,
                        'count' => $items->sum('count')
                    ]);
                }
                $kecamatanData[$kecName] = $topKec->sortByDesc('count')->take($topNKecamatan)->values();
            }
        }

        // 3. Data Top N Per Puskesmas
        $puskesmasData = [];
        if (in_array('puskesmas', $exportScopes)) {
            $groupedPusk = collect($rawData->where('scope', 'puskesmas')->groupBy('kpusk')->all());

            if ($puskesmasFilterMode === 'selected' && !empty($selectedPuskesmas)) {
                $groupedPusk = $groupedPusk->only($selectedPuskesmas);
            }

            foreach ($groupedPusk as $kodePuskesmas => $items) {
                $namaPuskesmas = $puskesmasNames[$kodePuskesmas] ?? $kodePuskesmas;
                $topPusk = collect();
                $groupedPenyakit = $items->groupBy('kode_penyakit');
                foreach ($groupedPenyakit as $kode => $penyakits) {
                    $topPusk->push((object)[
                        'kode_penyakit' => $kode,
                        'nama_penyakit' => $penyakits->first()->nama_penyakit ?? null,
                        'count' => $penyakits->sum('count')
                    ]);
                }
                $puskesmasData[$namaPuskesmas] = $topPusk->sortByDesc('count')->take($topNPuskesmas)->values();
            }
        }

        $kecBreakdown = collect();
        $puskBreakdown = collect();

        if (in_array('umum', $exportScopes) && $topUmum->isNotEmpty()) {
            $topDiseaseCodes = $topUmum->pluck('kode_penyakit')->toArray();

            if ($periodType === 'custom_date') {
                $kecBreakdown = $this->getRawHistoryBreakdownData('kecamatan', $topDiseaseCodes, $rawRanges, $aggregateMonths, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
                $puskBreakdown = $this->getRawHistoryBreakdownData('puskesmas', $topDiseaseCodes, $rawRanges, $aggregateMonths, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
            } else {
                $kecBreakdown = $this->getAggregateBreakdownData('kecamatan', $topDiseaseCodes, $periodType, $year, $month, $semester, $quarter, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
                $puskBreakdown = $this->getAggregateBreakdownData('puskesmas', $topDiseaseCodes, $periodType, $year, $month, $semester, $quarter, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
            }
        }

        $kecBreakdownGrouped = $kecBreakdown->groupBy('kode_penyakit');
        $puskBreakdownGrouped = $puskBreakdown->groupBy('kode_penyakit');

        // ====== GENERATE EXCEL (BINARY XLSX) ======
        if ($format === 'excel') {
            $filename = "Laporan_Rekap_Penyakit_" . date('Ymd_His') . ".xlsx";
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Worksheet');

            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(50);
            $sheet->getColumnDimension('D')->setWidth(18);

            $currentRow = 1;
            $charts = [];

            $addChart = function ($titleText, $startRow, $endRow, $currentRow) use (&$charts) {
                $xAxisTickValues = [
                    new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', "'Worksheet'!\$C\${$startRow}:\$C\${$endRow}", null, $endRow - $startRow + 1)
                ];
                $dataSeriesValues = [
                    new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', "'Worksheet'!\$D\${$startRow}:\$D\${$endRow}", null, $endRow - $startRow + 1)
                ];

                $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
                    \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART,
                    \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_STANDARD,
                    range(0, count($dataSeriesValues) - 1),
                    [],
                    $xAxisTickValues,
                    $dataSeriesValues
                );
                $series->setPlotDirection(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::DIRECTION_BAR);

                $layout = new \PhpOffice\PhpSpreadsheet\Chart\Layout();
                $layout->setShowVal(true);
                $layout->setShowCatName(false);
                $layout->setDLblPos('outEnd');

                $categoryAxis = new \PhpOffice\PhpSpreadsheet\Chart\Axis();
                $categoryAxis->setAxisOptionsProperties(
                    \PhpOffice\PhpSpreadsheet\Chart\Properties::AXIS_LABELS_NEXT_TO,
                    null,
                    null,
                    \PhpOffice\PhpSpreadsheet\Chart\Properties::ORIENTATION_REVERSED
                );

                $valueAxis = new \PhpOffice\PhpSpreadsheet\Chart\Axis();

                $plotArea = new \PhpOffice\PhpSpreadsheet\Chart\PlotArea($layout, [$series]);
                $title = new \PhpOffice\PhpSpreadsheet\Chart\Title($titleText);
                
                $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
                    'chart_' . $startRow,
                    $title,
                    null,
                    $plotArea,
                    true,
                    0,
                    null,
                    null,
                    $categoryAxis,
                    $valueAxis
                );
                
                $chart->setTopLeftPosition('F' . ($startRow - 1));
                // Give a little extra width for value labels and enough height for longer rankings.
                $chart->setBottomRightPosition('Q' . max($endRow, $startRow + 12));
                $charts[] = $chart;
            };

            if (in_array('umum', $exportScopes)) {
                $sheet->setCellValue("A{$currentRow}", 'SECTION: TOP PENYAKIT UMUM (KESELURUHAN WILAYAH)');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                $currentRow += 2;
                
                $sheet->setCellValue("A{$currentRow}", 'Peringkat');
                $sheet->setCellValue("B{$currentRow}", 'Kode Penyakit (ICD-X)');
                $sheet->setCellValue("C{$currentRow}", 'Nama Penyakit');
                $sheet->setCellValue("D{$currentRow}", 'Jumlah Kasus');
                $sheet->getStyle("A{$currentRow}:D{$currentRow}")->getFont()->setBold(true);
                $currentRow++;
                
                $startRow = $currentRow;
                foreach ($topUmum as $index => $row) {
                    $sheet->setCellValue("A{$currentRow}", $index + 1);
                    $sheet->setCellValue("B{$currentRow}", $row->kode_penyakit);
                    $sheet->setCellValue("C{$currentRow}", $row->nama_penyakit ?? $row->kode_penyakit);
                    $sheet->setCellValue("D{$currentRow}", $row->count);
                    $currentRow++;
                }
                if ($currentRow > $startRow) {
                    $addChart('Top Penyakit Umum', $startRow, $currentRow - 1, $currentRow);
                }
                $currentRow += 2;
            }

            if (in_array('kecamatan', $exportScopes)) {
                $sheet->setCellValue("A{$currentRow}", 'SECTION: TOP PENYAKIT PER KECAMATAN');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                $currentRow += 2;
                
                foreach ($kecamatanData as $kecName => $kecData) {
                    $sheet->setCellValue("A{$currentRow}", "Kecamatan: $kecName");
                    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                    $currentRow += 2;

                    $sheet->setCellValue("A{$currentRow}", 'Peringkat');
                    $sheet->setCellValue("B{$currentRow}", 'Kode Penyakit (ICD-X)');
                    $sheet->setCellValue("C{$currentRow}", 'Nama Penyakit');
                    $sheet->setCellValue("D{$currentRow}", 'Jumlah Kasus');
                    $sheet->getStyle("A{$currentRow}:D{$currentRow}")->getFont()->setBold(true);
                    $currentRow++;

                    $startRow = $currentRow;
                    foreach ($kecData as $index => $row) {
                        $sheet->setCellValue("A{$currentRow}", $index + 1);
                        $sheet->setCellValue("B{$currentRow}", $row->kode_penyakit);
                        $sheet->setCellValue("C{$currentRow}", $row->nama_penyakit ?? $row->kode_penyakit);
                        $sheet->setCellValue("D{$currentRow}", $row->count);
                        $currentRow++;
                    }
                    if ($currentRow > $startRow) {
                        $addChart("Top Penyakit - $kecName", $startRow, $currentRow - 1, $currentRow);
                    }
                    $currentRow += 2;
                }
            }

            if (in_array('puskesmas', $exportScopes)) {
                $sheet->setCellValue("A{$currentRow}", 'SECTION: TOP PENYAKIT PER PUSKESMAS');
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                $currentRow += 2;
                
                foreach ($puskesmasData as $puskName => $puskData) {
                    $sheet->setCellValue("A{$currentRow}", "Puskesmas: $puskName");
                    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                    $currentRow += 2;

                    $sheet->setCellValue("A{$currentRow}", 'Peringkat');
                    $sheet->setCellValue("B{$currentRow}", 'Kode Penyakit (ICD-X)');
                    $sheet->setCellValue("C{$currentRow}", 'Nama Penyakit');
                    $sheet->setCellValue("D{$currentRow}", 'Jumlah Kasus');
                    $sheet->getStyle("A{$currentRow}:D{$currentRow}")->getFont()->setBold(true);
                    $currentRow++;

                    $startRow = $currentRow;
                    foreach ($puskData as $index => $row) {
                        $sheet->setCellValue("A{$currentRow}", $index + 1);
                        $sheet->setCellValue("B{$currentRow}", $row->kode_penyakit);
                        $sheet->setCellValue("C{$currentRow}", $row->nama_penyakit ?? $row->kode_penyakit);
                        $sheet->setCellValue("D{$currentRow}", $row->count);
                        $currentRow++;
                    }
                    if ($currentRow > $startRow) {
                        $addChart("Top Penyakit - $puskName", $startRow, $currentRow - 1, $currentRow);
                    }
                    $currentRow += 2;
                }
            }
            
            // Penempelan Objek Chart Ke Spreadsheet Utama
            foreach ($charts as $chart) {
                $sheet->addChart($chart);
            }

            if (in_array('umum', $exportScopes) && $topUmum->isNotEmpty()) {
                $detailSheet = $spreadsheet->createSheet();
                $detailSheet->setTitle('Detail Sebaran Penyakit');
                
                $detailSheet->getColumnDimension('A')->setWidth(8);
                $detailSheet->getColumnDimension('B')->setWidth(25);
                $detailSheet->getColumnDimension('C')->setWidth(15);
                $detailSheet->getColumnDimension('D')->setWidth(5);
                $detailSheet->getColumnDimension('E')->setWidth(8);
                $detailSheet->getColumnDimension('F')->setWidth(25);
                $detailSheet->getColumnDimension('G')->setWidth(15);

                $rowNum = 1;

                foreach ($topUmum as $index => $disease) {
                    $kode = $disease->kode_penyakit;
                    $nama = $disease->nama_penyakit ?? $kode;
                    $total = $disease->count;

                    // 1. Header Penyakit
                    $detailSheet->mergeCells("A{$rowNum}:G{$rowNum}");
                    $detailSheet->setCellValue("A{$rowNum}", "Peringkat #" . ($index + 1) . ": {$nama} ({$kode}) - Total: {$total} Kasus");
                    $detailSheet->getStyle("A{$rowNum}")->getFont()->setBold(true)->setSize(12);
                    $detailSheet->getStyle("A{$rowNum}")->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFE0E0E0');
                    $rowNum++;

                    // 2. Sub-Header Tabel (Kecamatan vs Puskesmas)
                    $detailSheet->mergeCells("A{$rowNum}:C{$rowNum}");
                    $detailSheet->setCellValue("A{$rowNum}", 'Peringkat Sebaran Kecamatan');
                    $detailSheet->getStyle("A{$rowNum}")->getFont()->setBold(true);

                    $detailSheet->mergeCells("E{$rowNum}:G{$rowNum}");
                    $detailSheet->setCellValue("E{$rowNum}", 'Peringkat Sebaran Puskesmas');
                    $detailSheet->getStyle("E{$rowNum}")->getFont()->setBold(true);
                    $rowNum++;

                    // Header Kolom
                    $detailSheet->setCellValue("A{$rowNum}", 'Rank');
                    $detailSheet->setCellValue("B{$rowNum}", 'Nama Kecamatan');
                    $detailSheet->setCellValue("C{$rowNum}", 'Kasus');

                    $detailSheet->setCellValue("E{$rowNum}", 'Rank');
                    $detailSheet->setCellValue("F{$rowNum}", 'Nama Puskesmas');
                    $detailSheet->setCellValue("G{$rowNum}", 'Kasus');

                    $detailSheet->getStyle("A{$rowNum}:C{$rowNum}")->getFont()->setItalic(true);
                    $detailSheet->getStyle("E{$rowNum}:G{$rowNum}")->getFont()->setItalic(true);
                    $rowNum++;

                    // 3. Ambil Seluruh Data Sebaran & Urutkan secara Menurun
                    $diseaseKec = $kecBreakdownGrouped->get($kode, collect())->sortByDesc('count')->values();
                    $diseasePusk = $puskBreakdownGrouped->get($kode, collect())->sortByDesc('count')->values();

                    $maxRows = max($diseaseKec->count(), $diseasePusk->count(), 1);

                    for ($i = 0; $i < $maxRows; $i++) {
                        // Tulis Kecamatan
                        if ($i < $diseaseKec->count()) {
                            $kecItem = $diseaseKec[$i];
                            $namaKec = \App\Services\RecapLogicService::MAPPING_NAMA_KECAMATAN[$kecItem->kode_kecamatan] ?? $kecItem->kode_kecamatan;
                            $detailSheet->setCellValue("A{$rowNum}", $i + 1);
                            $detailSheet->setCellValue("B{$rowNum}", $namaKec);
                            $detailSheet->setCellValue("C{$rowNum}", $kecItem->count);
                        } else if ($i == 0 && $diseaseKec->isEmpty()) {
                            $detailSheet->setCellValue("B{$rowNum}", 'Tidak ada data');
                        }

                        // Tulis Puskesmas
                        if ($i < $diseasePusk->count()) {
                            $puskItem = $diseasePusk[$i];
                            $namaPusk = $puskesmasNames[$puskItem->kpusk] ?? $puskItem->kpusk;
                            $detailSheet->setCellValue("E{$rowNum}", $i + 1);
                            $detailSheet->setCellValue("F{$rowNum}", $namaPusk);
                            $detailSheet->setCellValue("G{$rowNum}", $puskItem->count);
                        } else if ($i == 0 && $diseasePusk->isEmpty()) {
                            $detailSheet->setCellValue("F{$rowNum}", 'Tidak ada data');
                        }
                        $rowNum++;
                    }

                    // Beri jarak antar penyakit
                    $rowNum += 2;
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            
            ob_start();
            $writer->save('php://output');
            $content = ob_get_clean();
            
            return response($content)
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'max-age=0');
        }

        // ====== GENERATE HTML (PRINTABLE PDF) ======
        return view('recap.export_print', compact(
            'topUmum', 
            'kecamatanData', 
            'puskesmasData', 
            'topNUmum', 
            'topNKecamatan', 
            'topNPuskesmas',
            'periodType',
            'year',
            'month',
            'semester',
            'quarter',
            'startDate',
            'endDate',
            'exportScopes',
            'includePrefixes',
            'excludePrefixes',
            'includeCodes',
            'excludeCodes'
        ));
    }

    private function getAggregateExportData(
        string $periodType,
        string|int|null $year,
        string|int|null $month,
        string|int|null $semester,
        string|int|null $quarter,
        array $includePrefixes,
        array $excludePrefixes,
        array $includeCodes,
        array $excludeCodes,
        array $excludeExceptions = []
    ) {
        $query = RekapPenyakitTop::query()
            ->select('scope', 'kpusk', 'kode_kecamatan', 'kode_penyakit', 'nama_penyakit', DB::raw('jumlah_kasus as count'), 'year', 'month', 'period_type')
            ->whereIn('scope', ['global', 'kecamatan', 'puskesmas']);

        if ($periodType === 'month') {
            $query->where('period_type', 'month')
                ->where('year', $year)
                ->where('month', $month);
        } elseif ($periodType === 'semester') {
            $query->where('period_type', 'semester')
                ->where('year', $year)
                ->where('semester', $semester);
        } elseif ($periodType === 'quarter') {
            $query->where('period_type', 'quarter')
                ->where('year', $year)
                ->where('quarter', $quarter);
        } else {
            $query->where('period_type', 'year')
                ->where('year', $year);
        }

        $this->applyKodePenyakitFilters($query, 'kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);

        return $query->get();
    }

    private function getRawHistoryExportData(
        array $exportScopes,
        string $startDate,
        string $endDate,
        array $includePrefixes,
        array $excludePrefixes,
        array $includeCodes,
        array $excludeCodes,
        array $excludeExceptions = []
    ) {
        list($rawRanges, $aggregateMonths) = $this->parseCustomDateRanges($startDate, $endDate);

        $rawData = collect();

        if (in_array('umum', $exportScopes, true)) {
            $rawUmum = collect();
            if (!empty($rawRanges)) {
                $rawQuery = DB::table('history as h')
                    ->leftJoin('ref_puskesmas as rp', 'h.kpusk', '=', 'rp.kode_puskesmas')
                    ->leftJoin('bpjs_ref_icd as icd', 'h.kode_penyakit', '=', 'icd.kdDiag')
                    ->whereNotNull('h.kode_penyakit')
                    ->where('h.kode_penyakit', '<>', '')
                    ->where(function ($q) use ($rawRanges) {
                        foreach ($rawRanges as $range) {
                            $q->orWhereBetween('h.tanggal', [$range['start'], $range['end']]);
                        }
                    });
                $this->applyKodePenyakitFilters($rawQuery, 'h.kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
                $rawUmum = $rawQuery
                    ->selectRaw("
                        'global' as scope,
                        '' as kpusk,
                        '' as kode_kecamatan,
                        h.kode_penyakit,
                        COALESCE(NULLIF(icd.nmDiag, ''), h.kode_penyakit) as nama_penyakit,
                        COUNT(*) as count
                    ")
                    ->groupBy('h.kode_penyakit', 'icd.nmDiag')
                    ->get();
            }

            $aggUmum = collect();
            if (!empty($aggregateMonths)) {
                $aggQuery = DB::table('rekap_penyakit_top')
                    ->where('scope', 'global')
                    ->where('period_type', 'month')
                    ->where(function ($q) use ($aggregateMonths) {
                        foreach ($aggregateMonths as $monthData) {
                            $q->orWhere(function ($sub) use ($monthData) {
                                $sub->where('year', $monthData['year'])
                                    ->where('month', $monthData['month']);
                            });
                        }
                    });
                $this->applyKodePenyakitFilters($aggQuery, 'kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
                $aggUmum = $aggQuery
                    ->selectRaw("
                        'global' as scope,
                        '' as kpusk,
                        '' as kode_kecamatan,
                        kode_penyakit,
                        nama_penyakit,
                        SUM(jumlah_kasus) as count
                    ")
                    ->groupBy('kode_penyakit', 'nama_penyakit')
                    ->get();
            }

            $mergedUmum = $rawUmum->concat($aggUmum)
                ->groupBy(function ($item) {
                    return $item->kode_penyakit;
                })
                ->map(function ($group) {
                    $first = $group->first();
                    return (object)[
                        'scope' => 'global',
                        'kpusk' => '',
                        'kode_kecamatan' => '',
                        'kode_penyakit' => $first->kode_penyakit,
                        'nama_penyakit' => $first->nama_penyakit,
                        'count' => $group->sum('count'),
                        'period_type' => 'custom_date',
                        'year' => null,
                        'month' => null
                    ];
                })
                ->values();
            $rawData = $rawData->concat($mergedUmum);
        }

        if (in_array('kecamatan', $exportScopes, true)) {
            $rawKec = collect();
            if (!empty($rawRanges)) {
                $rawQuery = DB::table('history as h')
                    ->leftJoin('ref_puskesmas as rp', 'h.kpusk', '=', 'rp.kode_puskesmas')
                    ->leftJoin('bpjs_ref_icd as icd', 'h.kode_penyakit', '=', 'icd.kdDiag')
                    ->whereNotNull('rp.kode_kecamatan')
                    ->where('rp.kode_kecamatan', '<>', '')
                    ->whereNotNull('h.kode_penyakit')
                    ->where('h.kode_penyakit', '<>', '')
                    ->where(function ($q) use ($rawRanges) {
                        foreach ($rawRanges as $range) {
                            $q->orWhereBetween('h.tanggal', [$range['start'], $range['end']]);
                        }
                    });
                $this->applyKodePenyakitFilters($rawQuery, 'h.kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
                $rawKec = $rawQuery
                    ->selectRaw("
                        'kecamatan' as scope,
                        '' as kpusk,
                        rp.kode_kecamatan,
                        h.kode_penyakit,
                        COALESCE(NULLIF(icd.nmDiag, ''), h.kode_penyakit) as nama_penyakit,
                        COUNT(*) as count
                    ")
                    ->groupBy('rp.kode_kecamatan', 'h.kode_penyakit', 'icd.nmDiag')
                    ->get();
            }

            $aggKec = collect();
            if (!empty($aggregateMonths)) {
                $aggQuery = DB::table('rekap_penyakit_top')
                    ->where('scope', 'kecamatan')
                    ->where('period_type', 'month')
                    ->where(function ($q) use ($aggregateMonths) {
                        foreach ($aggregateMonths as $monthData) {
                            $q->orWhere(function ($sub) use ($monthData) {
                                $sub->where('year', $monthData['year'])
                                    ->where('month', $monthData['month']);
                            });
                        }
                    });
                $this->applyKodePenyakitFilters($aggQuery, 'kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
                $aggKec = $aggQuery
                    ->selectRaw("
                        'kecamatan' as scope,
                        '' as kpusk,
                        kode_kecamatan,
                        kode_penyakit,
                        nama_penyakit,
                        SUM(jumlah_kasus) as count
                    ")
                    ->groupBy('kode_kecamatan', 'kode_penyakit', 'nama_penyakit')
                    ->get();
            }

            $mergedKec = $rawKec->concat($aggKec)
                ->groupBy(function ($item) {
                    return $item->kode_kecamatan . '|' . $item->kode_penyakit;
                })
                ->map(function ($group) {
                    $first = $group->first();
                    return (object)[
                        'scope' => 'kecamatan',
                        'kpusk' => '',
                        'kode_kecamatan' => $first->kode_kecamatan,
                        'kode_penyakit' => $first->kode_penyakit,
                        'nama_penyakit' => $first->nama_penyakit,
                        'count' => $group->sum('count'),
                        'period_type' => 'custom_date',
                        'year' => null,
                        'month' => null
                    ];
                })
                ->values();
            $rawData = $rawData->concat($mergedKec);
        }

        if (in_array('puskesmas', $exportScopes, true)) {
            $rawPusk = collect();
            if (!empty($rawRanges)) {
                $rawQuery = DB::table('history as h')
                    ->leftJoin('ref_puskesmas as rp', 'h.kpusk', '=', 'rp.kode_puskesmas')
                    ->leftJoin('bpjs_ref_icd as icd', 'h.kode_penyakit', '=', 'icd.kdDiag')
                    ->whereNotNull('h.kpusk')
                    ->where('h.kpusk', '<>', '')
                    ->whereNotNull('h.kode_penyakit')
                    ->where('h.kode_penyakit', '<>', '')
                    ->where(function ($q) use ($rawRanges) {
                        foreach ($rawRanges as $range) {
                            $q->orWhereBetween('h.tanggal', [$range['start'], $range['end']]);
                        }
                    });
                $this->applyKodePenyakitFilters($rawQuery, 'h.kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
                $rawPusk = $rawQuery
                    ->selectRaw("
                        'puskesmas' as scope,
                        h.kpusk,
                        rp.kode_kecamatan,
                        h.kode_penyakit,
                        COALESCE(NULLIF(icd.nmDiag, ''), h.kode_penyakit) as nama_penyakit,
                        COUNT(*) as count
                    ")
                    ->groupBy('h.kpusk', 'rp.kode_kecamatan', 'h.kode_penyakit', 'icd.nmDiag')
                    ->get();
            }

            $aggPusk = collect();
            if (!empty($aggregateMonths)) {
                $aggQuery = DB::table('rekap_penyakit_top')
                    ->where('scope', 'puskesmas')
                    ->where('period_type', 'month')
                    ->where(function ($q) use ($aggregateMonths) {
                        foreach ($aggregateMonths as $monthData) {
                            $q->orWhere(function ($sub) use ($monthData) {
                                $sub->where('year', $monthData['year'])
                                    ->where('month', $monthData['month']);
                            });
                        }
                    });
                $this->applyKodePenyakitFilters($aggQuery, 'kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);
                $aggPusk = $aggQuery
                    ->selectRaw("
                        'puskesmas' as scope,
                        kpusk,
                        kode_kecamatan,
                        kode_penyakit,
                        nama_penyakit,
                        SUM(jumlah_kasus) as count
                    ")
                    ->groupBy('kpusk', 'kode_kecamatan', 'kode_penyakit', 'nama_penyakit')
                    ->get();
            }

            $mergedPusk = $rawPusk->concat($aggPusk)
                ->groupBy(function ($item) {
                    return $item->kpusk . '|' . $item->kode_penyakit;
                })
                ->map(function ($group) {
                    $first = $group->first();
                    return (object)[
                        'scope' => 'puskesmas',
                        'kpusk' => $first->kpusk,
                        'kode_kecamatan' => $first->kode_kecamatan,
                        'kode_penyakit' => $first->kode_penyakit,
                        'nama_penyakit' => $first->nama_penyakit,
                        'count' => $group->sum('count'),
                        'period_type' => 'custom_date',
                        'year' => null,
                        'month' => null
                    ];
                })
                ->values();
            $rawData = $rawData->concat($mergedPusk);
        }

        return $rawData->values();
    }

    private function parseCustomDateRanges(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $rawRanges = [];
        $aggregateMonths = [];

        if ($start->format('Y-m') === $end->format('Y-m')) {
            if ($start->day == 1 && $start->copy()->endOfMonth()->day == $end->day) {
                $aggregateMonths[] = ['year' => $start->year, 'month' => $start->month];
            } else {
                $rawRanges[] = ['start' => $start->toDateString(), 'end' => $end->toDateString()];
            }
        } else {
            if ($start->day == 1) {
                $aggregateMonths[] = ['year' => $start->year, 'month' => $start->month];
            } else {
                $rawRanges[] = ['start' => $start->toDateString(), 'end' => $start->copy()->endOfMonth()->toDateString()];
            }

            $middleStart = $start->copy()->addMonth()->startOfMonth();
            $middleEnd = $end->copy()->subMonth()->endOfMonth();

            if ($middleStart->lte($middleEnd)) {
                $temp = clone $middleStart;
                while ($temp->lte($middleEnd)) {
                    $aggregateMonths[] = ['year' => $temp->year, 'month' => $temp->month];
                    $temp->addMonth();
                }
            }

            if ($end->day == $end->copy()->endOfMonth()->day) {
                $aggregateMonths[] = ['year' => $end->year, 'month' => $end->month];
            } else {
                $rawRanges[] = ['start' => $end->copy()->startOfMonth()->toDateString(), 'end' => $end->toDateString()];
            }
        }

        return [$rawRanges, $aggregateMonths];
    }

    private function applyKodePenyakitFilters(
        $query,
        string $column,
        array $includePrefixes,
        array $excludePrefixes,
        array $includeCodes,
        array $excludeCodes,
        array $excludeExceptions = []
    ): void
    {
        $normalizedColumn = $column;
        // dd($includeCodes, $excludePrefixes, $normalizedColumn, $excludeCodes); 


        // Gabungkan semua inklusi dan pengecualian khusus sebagai calon bypass filter exclude
        $allExceptions = array_values(array_unique(array_merge($includePrefixes, $includeCodes, $excludeExceptions)));

        if (!empty($includePrefixes) || !empty($includeCodes)) {
            $query->where(function ($q) use ($includePrefixes, $includeCodes, $normalizedColumn) {
                $hasCondition = false;

                if (!empty($includeCodes)) {
                    $q->whereIn(DB::raw($normalizedColumn), $includeCodes);
                    $hasCondition = true;
                }

                foreach ($includePrefixes as $index => $prefix) {
                    $method = ($hasCondition || $index > 0) ? 'orWhereRaw' : 'whereRaw';
                    $q->{$method}($normalizedColumn . ' LIKE ?', [$prefix . '%']);
                }
            });
        }

        if (!empty($excludeCodes)) {
            $query->whereNotIn(DB::raw($normalizedColumn), $excludeCodes);
        }

        foreach ($excludePrefixes as $prefix) {
            // Cari apakah ada exception yang lebih spesifik atau sama dengan prefix pengecualian saat ini
            // $prefixExceptions = [];
            $prefixExceptions = $excludePrefixes;
            foreach ($allExceptions as $exc) {
                if (strpos(strtoupper($exc), strtoupper($prefix)) === 0) {
                    $prefixExceptions[] = $exc;
                }
            }

            if (!empty($prefixExceptions)) {
                $query->where(function ($q) use ($normalizedColumn, $prefix, $prefixExceptions) {
                    $q->whereRaw($normalizedColumn . ' NOT LIKE ?', [$prefix . '%']);
                    foreach ($prefixExceptions as $exc) {
                        $q->orWhereRaw($normalizedColumn . ' LIKE ?', [$exc . '%']);
                    }
                });
            } else {
                $query->whereRaw($normalizedColumn . ' NOT LIKE ?', [$prefix . '%']);
            }
        }
    }

    private function mergePrefixFilters(array $input, ?string $legacyCsv = null): array
    {
        $legacy = $legacyCsv ? explode(',', $legacyCsv) : [];

        return $this->normalizePrefixFilters(array_merge($input, $legacy));
    }

    private function normalizePrefixFilters(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $token = $this->normalizeFilterToken($value);

            if ($token !== '') {
                $normalized[] = $token;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeCodeFilters(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $token = $this->normalizeFilterToken($value);

            if ($token !== '') {
                $normalized[] = $token;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeSelectionFilters(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $token = strtoupper(trim((string) $value));

            if ($token !== '') {
                $normalized[] = $token;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeFilterToken(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }

    // ==========================================
    // RUTE FULL-PAGE DAFTAR PENYAKIT (PUSKESMAS)
    // ==========================================

    public function fullList(Request $request, $puskesmas)
    {
        $year = (int) $request->input('year', date('Y'));
        $periodType = $request->input('period_type', 'month');
        $periodValue = (int) $request->input('period_value', date('n'));
        $search = $request->input('search');
        $sort = $request->input('sort', 'cases_desc');

        $query = RekapPenyakitTop::query()
            ->select('kode_penyakit', 'nama_penyakit', DB::raw('jumlah_kasus as count'))
            ->where('scope', 'puskesmas')
            ->where('kpusk', $puskesmas)
            ->where('period_type', $periodType);

        if ($periodType === 'month') {
            $query->where('year', $year)->where('month', $periodValue);
        } elseif ($periodType === 'quarter') {
            $query->where('year', $year)->where('quarter', $periodValue);
        } elseif ($periodType === 'semester') {
            $query->where('year', $year)->where('semester', $periodValue);
        } else {
            $query->where('year', $year);
        }

        if ($search) {
            $query->where('kode_penyakit', 'LIKE', '%' . $search . '%');
        }

        // Logic Sorting
        if ($sort === 'cases_asc') {
            $query->orderBy('count', 'asc');
        } elseif ($sort === 'alphabet_asc') {
            $query->orderBy('kode_penyakit', 'asc');
        } elseif ($sort === 'alphabet_desc') {
            $query->orderByDesc('kode_penyakit');
        } else {
            $query->orderByDesc('count');
        }

        $penyakits = $query->paginate(10)->withQueryString();

        $icdNames = $penyakits->pluck('nama_penyakit', 'kode_penyakit')->filter()->toArray();
        $missingCodes = $penyakits->pluck('kode_penyakit')
            ->unique()
            ->diff(array_keys($icdNames))
            ->values()
            ->toArray();
        if (!empty($missingCodes)) {
            $icdNames = array_replace($icdNames, \App\Services\RecapLogicService::getIcdNames($missingCodes));
        }

        return view('recap.puskesmas.full_list', compact('puskesmas', 'year', 'periodType', 'periodValue', 'penyakits', 'search', 'sort', 'icdNames'));
    }

    // ==========================================
    // RUTE FULL-PAGE DAFTAR PENYAKIT (KECAMATAN)
    // ==========================================

    public function fullListKecamatan(Request $request, $kecamatan)
    {
        $year = (int) $request->input('year', date('Y'));
        $periodType = $request->input('period_type', 'month');
        $periodValue = (int) $request->input('period_value', date('n'));
        $search = $request->input('search');
        $sort = $request->input('sort', 'cases_desc');

        $mapping = \App\Services\RecapLogicService::getMappingKodeToKecamatan();
        $puskesmasList = array_keys(array_filter($mapping, fn($k) => $k === $kecamatan));

        if (empty($puskesmasList)) {
            abort(404, 'Kecamatan tidak ditemukan.');
        }

        $kodeKecamatan = array_search($kecamatan, RecapLogicService::MAPPING_NAMA_KECAMATAN, true);
        if ($kodeKecamatan === false) {
            abort(404, 'Kecamatan tidak ditemukan.');
        }

        $query = RekapPenyakitTop::query()
            ->select('kode_penyakit', 'nama_penyakit', DB::raw('jumlah_kasus as count'))
            ->where('scope', 'kecamatan')
            ->where('kode_kecamatan', $kodeKecamatan)
            ->where('period_type', $periodType);

        if ($periodType === 'month') {
            $query->where('year', $year)->where('month', $periodValue);
        } elseif ($periodType === 'quarter') {
            $query->where('year', $year)->where('quarter', $periodValue);
        } elseif ($periodType === 'semester') {
            $query->where('year', $year)->where('semester', $periodValue);
        } else {
            $query->where('year', $year);
        }

        if ($search) {
            $query->where('kode_penyakit', 'LIKE', '%' . $search . '%');
        }

        // Logic Sorting
        if ($sort === 'cases_asc') {
            $query->orderBy('count', 'asc');
        } elseif ($sort === 'alphabet_asc') {
            $query->orderBy('kode_penyakit', 'asc');
        } elseif ($sort === 'alphabet_desc') {
            $query->orderByDesc('kode_penyakit');
        } else {
            $query->orderByDesc('count');
        }

        $penyakits = $query->paginate(10)->withQueryString();

        $icdNames = $penyakits->pluck('nama_penyakit', 'kode_penyakit')->filter()->toArray();
        $missingCodes = $penyakits->pluck('kode_penyakit')
            ->unique()
            ->diff(array_keys($icdNames))
            ->values()
            ->toArray();
        if (!empty($missingCodes)) {
            $icdNames = array_replace($icdNames, \App\Services\RecapLogicService::getIcdNames($missingCodes));
        }

        return view('recap.kecamatan.full_list_kecamatan', compact('kecamatan', 'year', 'periodType', 'periodValue', 'penyakits', 'search', 'sort', 'icdNames'));
    }

    private function getRawHistoryBreakdownData(
        string $scope,
        array $topDiseaseCodes,
        array $rawRanges,
        array $aggregateMonths,
        array $includePrefixes,
        array $excludePrefixes,
        array $includeCodes,
        array $excludeCodes,
        array $excludeExceptions = []
    ) {
        $raw = collect();
        if (!empty($rawRanges)) {
            $rawQuery = DB::table('history as h')
                ->leftJoin('ref_puskesmas as rp', 'h.kpusk', '=', 'rp.kode_puskesmas')
                ->leftJoin('bpjs_ref_icd as icd', 'h.kode_penyakit', '=', 'icd.kdDiag')
                ->whereIn('h.kode_penyakit', $topDiseaseCodes);

            if ($scope === 'kecamatan') {
                $rawQuery->whereNotNull('rp.kode_kecamatan')
                    ->where('rp.kode_kecamatan', '<>', '');
            } else {
                $rawQuery->whereNotNull('h.kpusk')
                    ->where('h.kpusk', '<>', '');
            }

            $rawQuery->where(function ($q) use ($rawRanges) {
                foreach ($rawRanges as $range) {
                    $q->orWhereBetween('h.tanggal', [$range['start'], $range['end']]);
                }
            });

            $this->applyKodePenyakitFilters($rawQuery, 'h.kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);

            if ($scope === 'kecamatan') {
                $raw = $rawQuery
                    ->selectRaw("
                        rp.kode_kecamatan,
                        h.kode_penyakit,
                        COALESCE(NULLIF(icd.nmDiag, ''), h.kode_penyakit) as nama_penyakit,
                        COUNT(*) as count
                    ")
                    ->groupBy('rp.kode_kecamatan', 'h.kode_penyakit', 'icd.nmDiag')
                    ->get();
            } else {
                $raw = $rawQuery
                    ->selectRaw("
                        h.kpusk,
                        rp.kode_kecamatan,
                        h.kode_penyakit,
                        COALESCE(NULLIF(icd.nmDiag, ''), h.kode_penyakit) as nama_penyakit,
                        COUNT(*) as count
                    ")
                    ->groupBy('h.kpusk', 'rp.kode_kecamatan', 'h.kode_penyakit', 'icd.nmDiag')
                    ->get();
            }
        }

        $agg = collect();
        if (!empty($aggregateMonths)) {
            $aggQuery = DB::table('rekap_penyakit_top')
                ->where('scope', $scope)
                ->where('period_type', 'month')
                ->whereIn('kode_penyakit', $topDiseaseCodes)
                ->where(function ($q) use ($aggregateMonths) {
                    foreach ($aggregateMonths as $monthData) {
                        $q->orWhere(function ($sub) use ($monthData) {
                            $sub->where('year', $monthData['year'])
                                ->where('month', $monthData['month']);
                        });
                    }
                });

            $this->applyKodePenyakitFilters($aggQuery, 'kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);

            if ($scope === 'kecamatan') {
                $agg = $aggQuery
                    ->selectRaw("
                        kode_kecamatan,
                        kode_penyakit,
                        nama_penyakit,
                        SUM(jumlah_kasus) as count
                    ")
                    ->groupBy('kode_kecamatan', 'kode_penyakit', 'nama_penyakit')
                    ->get();
            } else {
                $agg = $aggQuery
                    ->selectRaw("
                        kpusk,
                        kode_kecamatan,
                        kode_penyakit,
                        nama_penyakit,
                        SUM(jumlah_kasus) as count
                    ")
                    ->groupBy('kpusk', 'kode_kecamatan', 'kode_penyakit', 'nama_penyakit')
                    ->get();
            }
        }

        if ($scope === 'kecamatan') {
            return $raw->concat($agg)
                ->groupBy(function ($item) {
                    return $item->kode_kecamatan . '|' . $item->kode_penyakit;
                })
                ->map(function ($group) {
                    $first = $group->first();
                    return (object)[
                        'kode_kecamatan' => $first->kode_kecamatan,
                        'kode_penyakit' => $first->kode_penyakit,
                        'nama_penyakit' => $first->nama_penyakit,
                        'count' => $group->sum('count')
                    ];
                })
                ->values();
        } else {
            return $raw->concat($agg)
                ->groupBy(function ($item) {
                    return $item->kpusk . '|' . $item->kode_penyakit;
                })
                ->map(function ($group) {
                    $first = $group->first();
                    return (object)[
                        'kpusk' => $first->kpusk,
                        'kode_kecamatan' => $first->kode_kecamatan,
                        'kode_penyakit' => $first->kode_penyakit,
                        'nama_penyakit' => $first->nama_penyakit,
                        'count' => $group->sum('count')
                    ];
                })
                ->values();
        }
    }

    private function getAggregateBreakdownData(
        string $scope,
        array $topDiseaseCodes,
        string $periodType,
        $year,
        $month,
        $semester,
        $quarter,
        array $includePrefixes,
        array $excludePrefixes,
        array $includeCodes,
        array $excludeCodes,
        array $excludeExceptions = []
    ) {
        $query = RekapPenyakitTop::query();
        if ($scope === 'kecamatan') {
            $query->select('kode_kecamatan', 'kode_penyakit', 'nama_penyakit', DB::raw('jumlah_kasus as count'));
        } else {
            $query->select('kpusk', 'kode_kecamatan', 'kode_penyakit', 'nama_penyakit', DB::raw('jumlah_kasus as count'));
        }

        $query->where('scope', $scope)
            ->whereIn('kode_penyakit', $topDiseaseCodes);

        if ($periodType === 'month') {
            $query->where('period_type', 'month')
                ->where('year', $year)
                ->where('month', $month);
        } elseif ($periodType === 'semester') {
            $query->where('period_type', 'semester')
                ->where('year', $year)
                ->where('semester', $semester);
        } elseif ($periodType === 'quarter') {
            $query->where('period_type', 'quarter')
                ->where('year', $year)
                ->where('quarter', $quarter);
        } else {
            $query->where('period_type', 'year')
                ->where('year', $year);
        }

        $this->applyKodePenyakitFilters($query, 'kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);

        return $query->get();
    }

}
