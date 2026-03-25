<?php

namespace App\Http\Controllers;

use App\Models\RekamMedis;
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
        $yearInput = $request->input('year');
        $mapping = RecapLogicService::MAPPING_KECAMATAN;

        // Ambil daftar tahun unik yang ada datanya
        $availableYears = RekamMedis::selectRaw('YEAR(tanggal) as year')
            ->whereNotNull('tanggal')
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year');

        $queryRekap = RekamMedis::select('kpusk', 'kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit');

        if ($yearInput) {
            $queryRekap->whereYear('tanggal', $yearInput);
        }

        // Menghitung Data Kecamatan secara Kolektif untuk UI Tampilan Grid Card
        $listKecamatanUnik = array_unique(array_values($mapping));
        sort($listKecamatanUnik);

        $cacheKeyRekap = 'rekap:index:' . ($yearInput ?: 'all');
        $rekapData = collect(Cache::remember($cacheKeyRekap, now()->addMinutes(10), function () use ($queryRekap) {
            return $queryRekap->groupBy('kpusk', 'kode_penyakit')
                ->orderBy('kpusk')
                ->orderByDesc('count')
                ->get()
                ->map(function ($row) {
                    return [
                        'kpusk' => $row->kpusk,
                        'kode_penyakit' => $row->kode_penyakit,
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
        $topPenyakitAgg = $rekapData->groupBy('kode_penyakit')->map(function ($items) {
            return $items->sum('count');
        })->sortDesc();
        $topPenyakitData = $topPenyakitAgg->isNotEmpty()
            ? (object)['kode_penyakit' => $topPenyakitAgg->keys()->first(), 'count' => $topPenyakitAgg->first()]
            : null;
        $topPenyakit = $topPenyakitData ? $topPenyakitData->kode_penyakit . ' (' . $topPenyakitData->count . ' Kasus)' : 'Tidak Ada';

        $totalPuskesmas = count(array_keys($mapping));
        $totalKecamatan = count(array_unique(array_values($mapping)));

        // --- GRAFIK GLOBAL UMUM & SMART ANALYSIS ---
        $rawDataSemua = $rekapData->map(function ($item) use ($mapping) {
                return [
                    'Puskesmas' => $item->kpusk,
                    'Kecamatan' => $mapping[$item->kpusk] ?? 'Tidak Diketahui',
                    'Jenis Penyakit' => $item->kode_penyakit,
                    'ICD X' => $item->kode_penyakit,
                    'Total_Kasus' => $item->count
                ];
            });
        
        $recapService = new RecapLogicService();
        $rankingsGlobal = $recapService->calculateRankings($rawDataSemua, ['Kecamatan'], max($limit, 10));
        $chartData = $recapService->findCommonDiseases($rankingsGlobal, 'Kecamatan', $limit)->map(function ($item) {
            return (object)[
                'label' => $item['ICD X'],
                'total' => $item['Total_Kasus'],
                'status' => $item['Status'],
            ];
        });

        $maxChartWidth = $chartData->max('total') ?: 1;

        return view('recap.index', compact(
            'groupedByPusk', 'mapping', 'listPuskesmas', 'listKecamatan', 
            'kecamatanDataList', 'totalKasus', 'topPenyakit', 
            'totalPuskesmas', 'totalKecamatan', 'chartData', 'maxChartWidth',
            'availableYears', 'yearInput'
        ));
    }

    public function show(Request $request, $puskesmas)
    {
        $limitInput = $request->input('limit');
        $limit = $limitInput === null ? 10 : (int) $limitInput;
        $mapping = RecapLogicService::MAPPING_KECAMATAN;
        $kecamatan = $mapping[$puskesmas] ?? 'Tidak Diketahui';

        $periodType = $request->input('period_type', 'all');
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
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

        $rekapData = collect(Cache::remember($cacheKey, now()->addMinutes(10), function () use ($puskesmas, $startDate, $endDate) {
            $query = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
                ->whereNotNull('kode_penyakit')
                ->where('kpusk', $puskesmas);

            if ($startDate && $endDate) {
                $query->whereBetween('tanggal', [$startDate, $endDate]);
            }

            return $query->groupBy('kode_penyakit')
                ->orderByDesc('count')
                ->get()
                ->map(function ($row) {
                    return [
                        'kode_penyakit' => $row->kode_penyakit,
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

        return view('recap.show', compact('puskesmas', 'kecamatan', 'rekapData', 'totalKasus', 'limit', 'rekapChartData', 'maxChartWidth', 'totalDiagnosaUnik', 'warningLimit', 'isNotFinished', 'periodType', 'year', 'month', 'semester', 'quarter'));
    }

    public function showKecamatan(Request $request, $kecamatan)
    {
        $limitInput = $request->input('limit');
        $limit = $limitInput === null ? 10 : (int) $limitInput;
        $mapping = RecapLogicService::MAPPING_KECAMATAN;
        
        $periodType = $request->input('period_type', 'all');
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
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

        $rekapByPusk = collect(Cache::remember($cacheKey, now()->addMinutes(10), function () use ($puskesmasInKecamatan, $startDate, $endDate) {
            $query = RekamMedis::select('kpusk', 'kode_penyakit', DB::raw('count(*) as count'))
                ->whereNotNull('kode_penyakit')
                ->whereIn('kpusk', $puskesmasInKecamatan);

            if ($startDate && $endDate) {
                $query->whereBetween('tanggal', [$startDate, $endDate]);
            }

            return $query->groupBy('kpusk', 'kode_penyakit')
                ->get()
                ->map(function ($row) {
                    return [
                        'kpusk' => $row->kpusk,
                        'kode_penyakit' => $row->kode_penyakit,
                        'count' => (int) $row->count,
                    ];
                })
                ->all();
        }))->groupBy('kpusk')->map(function ($items) {
            return collect($items)->map(function ($row) {
                return (object) $row;
            });
        });

        $rekapAgg = collect();
        foreach ($rekapByPusk as $items) {
            foreach ($items as $row) {
                $key = $row->kode_penyakit;
                $rekapAgg[$key] = ($rekapAgg[$key] ?? 0) + $row->count;
            }
        }

        $rekapData = $rekapAgg->map(function ($count, $kode) {
            return (object)[
                'kode_penyakit' => $kode,
                'count' => $count,
            ];
        })->sortByDesc('count')->values();

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

        return view('recap.show_kecamatan', compact('kecamatan', 'rekapData', 'totalKasus', 'totalPuskesmas', 'limit', 'rekapChartData', 'maxChartWidth', 'totalDiagnosaUnik', 'warningLimit', 'puskesmasStats', 'isNotFinished', 'periodType', 'year', 'month', 'semester', 'quarter'));
    }

}
