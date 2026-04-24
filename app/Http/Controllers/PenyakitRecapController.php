<?php

namespace App\Http\Controllers;

use App\Models\RekamMedis;
use App\Models\RekapPenyakitTop;
use App\Services\RecapLogicService;
use Carbon\Carbon;
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

        $puskesmasNames = \App\Services\RecapLogicService::getPuskesmasNames();

        return view('recap.index', compact(
            'groupedByPusk', 'mapping', 'listPuskesmas', 'listKecamatan', 
            'kecamatanDataList', 'totalKasus', 'topPenyakit', 
            'totalPuskesmas', 'totalKecamatan', 'chartData', 'maxChartWidth',
            'availableYears', 'yearInput', 'puskesmasNames', 'icdNames'
        ));
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
        $validated = $request->validate([
            'format' => ['nullable', 'in:pdf,excel'],
            'top_n_umum' => ['nullable', 'integer', 'min:1'],
            'top_n_kecamatan' => ['nullable', 'integer', 'min:1'],
            'top_n_puskesmas' => ['nullable', 'integer', 'min:1'],
            'period_type' => ['nullable', 'in:year,semester,quarter,month,custom_date'],
            'year' => ['nullable', 'integer'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'semester' => ['nullable', 'integer', 'between:1,2'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'export_scope' => ['nullable', 'array'],
            'export_scope.*' => ['in:umum,kecamatan,puskesmas'],
            'include_icd' => ['nullable', 'string'],
            'exclude_icd' => ['nullable', 'string'],
        ]);

        $format = $request->input('format', 'pdf');
        $topNUmum = (int) $request->input('top_n_umum', 10);
        $topNKecamatan = (int) $request->input('top_n_kecamatan', 10);
        $topNPuskesmas = (int) $request->input('top_n_puskesmas', 10);
        
        $periodType = $request->input('period_type', 'year');
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
        $semester = $request->input('semester', '1');
        $quarter = $request->input('quarter', '1');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($periodType === 'custom_date') {
            $request->validate([
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ]);

            $startDate = Carbon::parse($startDate)->toDateString();
            $endDate = Carbon::parse($endDate)->toDateString();
        }
        
        $exportScopes = $request->input('export_scope', []);
        $includeIcdStr = $request->input('include_icd');
        $excludeIcdStr = $request->input('exclude_icd');
        $includeLetters = !empty($includeIcdStr) ? array_values(array_filter(array_map('trim', explode(',', $includeIcdStr)))) : [];
        $excludeLetters = !empty($excludeIcdStr) ? array_values(array_filter(array_map('trim', explode(',', $excludeIcdStr)))) : [];
        
        $mapping = \App\Services\RecapLogicService::getMappingKodeToKecamatan();
        $puskesmasNames = \App\Services\RecapLogicService::getPuskesmasNames();
        $rawData = $periodType === 'custom_date'
            ? $this->getRawHistoryExportData($exportScopes, $startDate, $endDate, $includeLetters, $excludeLetters)
            : $this->getAggregateExportData($periodType, $year, $month, $semester, $quarter, $includeLetters, $excludeLetters);

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
            $groupedPusk = $rawData->where('scope', 'puskesmas')->groupBy('kpusk');
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
                $chart->setBottomRightPosition('Q' . max($endRow, $startRow + 14));
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
            'includeLetters',
            'excludeLetters'
        ));
    }

    private function getAggregateExportData(
        string $periodType,
        string|int|null $year,
        string|int|null $month,
        string|int|null $semester,
        string|int|null $quarter,
        array $includeLetters,
        array $excludeLetters
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

        $this->applyKodePenyakitLetterFilters($query, 'kode_penyakit', $includeLetters, $excludeLetters);

        return $query->get();
    }

    private function getRawHistoryExportData(
        array $exportScopes,
        string $startDate,
        string $endDate,
        array $includeLetters,
        array $excludeLetters
    ) {
        $baseQuery = DB::table('history as h')
            ->leftJoin('ref_puskesmas as rp', DB::raw("TRIM(COALESCE(h.kpusk, ''))"), '=', DB::raw("TRIM(COALESCE(rp.kode_puskesmas, ''))"))
            ->leftJoin('bpjs_ref_icd as icd', DB::raw('TRIM(h.kode_penyakit)'), '=', DB::raw('TRIM(icd.kdDiag)'))
            ->whereNotNull('h.tanggal')
            ->whereBetween('h.tanggal', [$startDate, $endDate])
            ->whereRaw("TRIM(COALESCE(h.kode_penyakit, '')) <> ''");

        $this->applyKodePenyakitLetterFilters($baseQuery, 'h.kode_penyakit', $includeLetters, $excludeLetters);

        $rawData = collect();

        if (in_array('umum', $exportScopes, true)) {
            $rawData = $rawData->concat(
                (clone $baseQuery)
                    ->selectRaw("
                        'global' as scope,
                        '' as kpusk,
                        '' as kode_kecamatan,
                        TRIM(h.kode_penyakit) as kode_penyakit,
                        COALESCE(NULLIF(icd.nmDiag, ''), TRIM(h.kode_penyakit)) as nama_penyakit,
                        COUNT(*) as count,
                        ? as period_type,
                        NULL as year,
                        NULL as month
                    ", ['custom_date'])
                    ->groupByRaw("TRIM(h.kode_penyakit), COALESCE(NULLIF(icd.nmDiag, ''), TRIM(h.kode_penyakit))")
                    ->get()
            );
        }

        if (in_array('kecamatan', $exportScopes, true)) {
            $rawData = $rawData->concat(
                (clone $baseQuery)
                    ->whereRaw("TRIM(COALESCE(rp.kode_kecamatan, '')) <> ''")
                    ->selectRaw("
                        'kecamatan' as scope,
                        '' as kpusk,
                        TRIM(COALESCE(rp.kode_kecamatan, '')) as kode_kecamatan,
                        TRIM(h.kode_penyakit) as kode_penyakit,
                        COALESCE(NULLIF(icd.nmDiag, ''), TRIM(h.kode_penyakit)) as nama_penyakit,
                        COUNT(*) as count,
                        ? as period_type,
                        NULL as year,
                        NULL as month
                    ", ['custom_date'])
                    ->groupByRaw("
                        TRIM(COALESCE(rp.kode_kecamatan, '')),
                        TRIM(h.kode_penyakit),
                        COALESCE(NULLIF(icd.nmDiag, ''), TRIM(h.kode_penyakit))
                    ")
                    ->get()
            );
        }

        if (in_array('puskesmas', $exportScopes, true)) {
            $rawData = $rawData->concat(
                (clone $baseQuery)
                    ->whereRaw("TRIM(COALESCE(h.kpusk, '')) <> ''")
                    ->selectRaw("
                        'puskesmas' as scope,
                        TRIM(COALESCE(h.kpusk, '')) as kpusk,
                        TRIM(COALESCE(rp.kode_kecamatan, '')) as kode_kecamatan,
                        TRIM(h.kode_penyakit) as kode_penyakit,
                        COALESCE(NULLIF(icd.nmDiag, ''), TRIM(h.kode_penyakit)) as nama_penyakit,
                        COUNT(*) as count,
                        ? as period_type,
                        NULL as year,
                        NULL as month
                    ", ['custom_date'])
                    ->groupByRaw("
                        TRIM(COALESCE(h.kpusk, '')),
                        TRIM(COALESCE(rp.kode_kecamatan, '')),
                        TRIM(h.kode_penyakit),
                        COALESCE(NULLIF(icd.nmDiag, ''), TRIM(h.kode_penyakit))
                    ")
                    ->get()
            );
        }

        return $rawData->values();
    }

    private function applyKodePenyakitLetterFilters($query, string $column, array $includeLetters, array $excludeLetters): void
    {
        if (!empty($includeLetters)) {
            $query->where(function ($q) use ($includeLetters, $column) {
                foreach ($includeLetters as $letter) {
                    $q->orWhere($column, 'LIKE', trim($letter) . '%');
                }
            });
        }

        if (!empty($excludeLetters)) {
            $query->where(function ($q) use ($excludeLetters, $column) {
                foreach ($excludeLetters as $letter) {
                    $q->where($column, 'NOT LIKE', trim($letter) . '%');
                }
            });
        }
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
            ->select('kode_penyakit', DB::raw('jumlah_kasus as count'))
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

        return view('recap.puskesmas.full_list', compact('puskesmas', 'year', 'periodType', 'periodValue', 'penyakits', 'search', 'sort'));
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
            ->select('kode_penyakit', DB::raw('jumlah_kasus as count'))
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

        return view('recap.kecamatan.full_list_kecamatan', compact('kecamatan', 'year', 'periodType', 'periodValue', 'penyakits', 'search', 'sort'));
    }

}
