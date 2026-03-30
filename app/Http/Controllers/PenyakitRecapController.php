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
        $yearInput = $request->input('year', date('Y'));
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
            $startDate = Carbon::create($yearInput)->startOfYear();
            $endDate = Carbon::create($yearInput)->endOfYear();
            $queryRekap->whereBetween('tanggal', [$startDate, $endDate]);
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
        })->sortByDesc('total')->values();

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

        return view('recap.puskesmas.show', compact('puskesmas', 'kecamatan', 'rekapData', 'totalKasus', 'limit', 'rekapChartData', 'maxChartWidth', 'totalDiagnosaUnik', 'warningLimit', 'isNotFinished', 'periodType', 'year', 'month', 'semester', 'quarter'));
    }

    public function showKecamatan(Request $request, $kecamatan)
    {
        $limitInput = $request->input('limit');
        $limit = $limitInput === null ? 10 : (int) $limitInput;
        $mapping = RecapLogicService::MAPPING_KECAMATAN;
        
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

        return view('recap.kecamatan.show_kecamatan', compact('kecamatan', 'rekapData', 'totalKasus', 'totalPuskesmas', 'limit', 'rekapChartData', 'maxChartWidth', 'totalDiagnosaUnik', 'warningLimit', 'puskesmasStats', 'isNotFinished', 'periodType', 'year', 'month', 'semester', 'quarter'));
    }

    public function export(Request $request)
    {
        $format = $request->input('format', 'pdf');
        $topNUmum = (int) $request->input('top_n_umum', 10);
        $topNKecamatan = (int) $request->input('top_n_kecamatan', 10);
        $topNPuskesmas = (int) $request->input('top_n_puskesmas', 10);
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $includeIcdStr = $request->input('include_icd');
        $excludeIcdStr = $request->input('exclude_icd');
        $includeLetters = !empty($includeIcdStr) ? explode(',', $includeIcdStr) : [];
        $excludeLetters = !empty($excludeIcdStr) ? explode(',', $excludeIcdStr) : [];
        
        $mapping = RecapLogicService::MAPPING_KECAMATAN;

        // Base Query
        $query = RekamMedis::select('kpusk', 'kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit');

        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }

        // Terapkan Filter Kategori Huruf A-Z (pada tingkat SQL)
        if (!empty($includeLetters)) {
            $query->where(function ($q) use ($includeLetters) {
                foreach ($includeLetters as $letter) {
                    $q->orWhere('kode_penyakit', 'LIKE', trim($letter) . '%');
                }
            });
        }

        if (!empty($excludeLetters)) {
            $query->where(function ($q) use ($excludeLetters) {
                foreach ($excludeLetters as $letter) {
                    $q->where('kode_penyakit', 'NOT LIKE', trim($letter) . '%');
                }
            });
        }

        $rawData = $query->groupBy('kpusk', 'kode_penyakit')->get();

        // 1. Data Top N Umum
        $topUmum = collect();
        $groupedUmum = $rawData->groupBy('kode_penyakit');
        foreach ($groupedUmum as $kode => $items) {
            $topUmum->push((object)[
                'kode_penyakit' => $kode,
                'count' => $items->sum('count')
            ]);
        }
        $topUmum = $topUmum->sortByDesc('count')->take($topNUmum)->values();

        // 2. Data Top N Per Kecamatan
        $kecamatanData = [];
        $listKecamatan = array_unique(array_values($mapping));
        foreach ($listKecamatan as $kecName) {
            $puskInKec = array_keys(array_filter($mapping, fn($k) => $k === $kecName));
            $dataKec = $rawData->whereIn('kpusk', $puskInKec);
            
            $groupedKec = $dataKec->groupBy('kode_penyakit');
            $topKec = collect();
            foreach ($groupedKec as $kode => $items) {
                $topKec->push((object)[
                    'kode_penyakit' => $kode,
                    'count' => $items->sum('count')
                ]);
            }
            $kecamatanData[$kecName] = $topKec->sortByDesc('count')->take($topNKecamatan)->values();
        }

        // 3. Data Top N Per Puskesmas
        $puskesmasData = [];
        $groupedPusk = $rawData->groupBy('kpusk');
        foreach ($groupedPusk as $puskName => $items) {
            $topPusk = collect();
            $groupedPenyakit = $items->groupBy('kode_penyakit');
            foreach ($groupedPenyakit as $kode => $penyakits) {
                $topPusk->push((object)[
                    'kode_penyakit' => $kode,
                    'count' => $penyakits->sum('count')
                ]);
            }
            $puskesmasData[$puskName] = $topPusk->sortByDesc('count')->take($topNPuskesmas)->values();
        }

        // ====== GENERATE EXCEL (BINARY XLSX) ======
        if ($format === 'excel') {
            $filename = "Laporan_Rekap_Penyakit_" . date('Ymd_His') . ".xlsx";
            $data = [];
            
            // Section 1
            $data[] = ['<b>SECTION: TOP PENYAKIT UMUM (KESELURUHAN WILAYAH)</b>', '', ''];
            $data[] = ['<b>Peringkat</b>', '<b>Kode Penyakit (ICD-X)</b>', '<b>Jumlah Kasus</b>'];
            foreach ($topUmum as $index => $row) {
                $data[] = [$index + 1, $row->kode_penyakit, $row->count];
            }
            $data[] = ['', '', ''];

            // Section 2
            $data[] = ['<b>SECTION: TOP PENYAKIT PER KECAMATAN</b>', '', ''];
            foreach ($kecamatanData as $kecName => $kecData) {
                $data[] = ["<b>Kecamatan: $kecName</b>", '', ''];
                $data[] = ['<b>Peringkat</b>', '<b>Kode Penyakit (ICD-X)</b>', '<b>Jumlah Kasus</b>'];
                foreach ($kecData as $index => $row) {
                    $data[] = [$index + 1, $row->kode_penyakit, $row->count];
                }
                $data[] = ['', '', ''];
            }

            // Section 3
            $data[] = ['<b>SECTION: TOP PENYAKIT PER PUSKESMAS</b>', '', ''];
            foreach ($puskesmasData as $puskName => $puskData) {
                $data[] = ["<b>Puskesmas: $puskName</b>", '', ''];
                $data[] = ['<b>Peringkat</b>', '<b>Kode Penyakit (ICD-X)</b>', '<b>Jumlah Kasus</b>'];
                foreach ($puskData as $index => $row) {
                    $data[] = [$index + 1, $row->kode_penyakit, $row->count];
                }
                $data[] = ['', '', ''];
            }

            $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
            $xlsx->setColWidth(1, 12);
            $xlsx->setColWidth(2, 35);
            $xlsx->setColWidth(3, 18);
            
            $content = (string) $xlsx;
            
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
            'startDate',
            'endDate',
            'includeLetters',
            'excludeLetters'
        ));
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
            $startMonth = ($periodValue - 1) * 6 + 1;
            $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
            $endDate = Carbon::create($year, $startMonth + 5, 1)->endOfMonth();
        } else {
            $startDate = Carbon::create($year)->startOfYear();
            $endDate = Carbon::create($year)->endOfYear();
        }

        $query = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->where('kpusk', $puskesmas)
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($search) {
            $query->where('kode_penyakit', 'LIKE', '%' . $search . '%');
        }

        $query->groupBy('kode_penyakit');

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

        $mapping = RecapLogicService::MAPPING_KECAMATAN;
        $puskesmasList = array_keys(array_filter($mapping, fn($k) => $k === $kecamatan));

        if (empty($puskesmasList)) {
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
            $startMonth = ($periodValue - 1) * 6 + 1;
            $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
            $endDate = Carbon::create($year, $startMonth + 5, 1)->endOfMonth();
        } else {
            $startDate = Carbon::create($year)->startOfYear();
            $endDate = Carbon::create($year)->endOfYear();
        }

        $query = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->whereIn('kpusk', $puskesmasList)
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($search) {
            $query->where('kode_penyakit', 'LIKE', '%' . $search . '%');
        }

        $query->groupBy('kode_penyakit');

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
