<?php

namespace App\Http\Controllers;

use App\Models\RekamMedis;
use App\Services\RecapLogicService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        // Menghitung Data Kecamatan secara Kolektif untuk UI Tampilan Grid Card
        $listKecamatanUnik = array_unique(array_values($mapping));
        sort($listKecamatanUnik);
        $kecamatanDataList = [];

        foreach ($listKecamatanUnik as $kecName) {
            $puskesmasDiKecamatan = array_keys(array_filter($mapping, function ($kec) use ($kecName) {
                return $kec === $kecName;
            }));

            if (!empty($puskesmasDiKecamatan)) {
                $kecPenyakitData = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
                    ->whereNotNull('kode_penyakit')
                    ->whereIn('kpusk', $puskesmasDiKecamatan)
                    ->groupBy('kode_penyakit')
                    ->orderByDesc('count')
                    ->get();

                $kecamatanDataList[$kecName] = [
                    'nama' => $kecName,
                    'total_puskesmas' => count($puskesmasDiKecamatan),
                    'total_kasus' => $kecPenyakitData->sum('count'),
                    'top_penyakit' => $kecPenyakitData->first(),
                    'list_puskesmas' => $puskesmasDiKecamatan
                ];
            }
        }

        $rekapData = $queryRekap->groupBy('kpusk', 'kode_penyakit')
            ->orderBy('kpusk')
            ->orderByDesc('count')
            ->get();
            
        $groupedByPusk = $rekapData->groupBy('kpusk');
        
        // Data for dropdowns
        $listPuskesmas = array_keys($mapping);
        sort($listPuskesmas);
        $listKecamatan = array_unique(array_values($mapping));
        sort($listKecamatan);
        
        // Data Global Stats Overview untuk Recap.Index
        // Data Global Stats Overview untuk Recap.Index
        $queryTotalKasus = RekamMedis::whereNotNull('kode_penyakit');
        $queryTopPenyakit = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->groupBy('kode_penyakit')
            ->orderByDesc('count');

        if ($yearInput) {
            $queryTotalKasus->whereYear('tanggal', $yearInput);
            $queryTopPenyakit->whereYear('tanggal', $yearInput);
        }

        $totalKasus = $queryTotalKasus->count();
        $topPenyakitData = $queryTopPenyakit->first();
        $topPenyakit = $topPenyakitData ? $topPenyakitData->kode_penyakit . ' (' . $topPenyakitData->count . ' Kasus)' : 'Tidak Ada';

        $totalPuskesmas = count(array_keys($mapping));
        $totalKecamatan = count(array_unique(array_values($mapping)));

        // --- GRAFIK GLOBAL UMUM & SMART ANALYSIS ---
        $queryRawData = RekamMedis::select('kpusk', 'kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->groupBy('kpusk', 'kode_penyakit');

        if ($yearInput) {
            $queryRawData->whereYear('tanggal', $yearInput);
        }

        $rawDataSemua = $queryRawData->get()
            ->map(function ($item) use ($mapping) {
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

        $query = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->where('kpusk', $puskesmas);

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        $rekapData = $query->groupBy('kode_penyakit')
            ->orderByDesc('count')
            ->get();
            
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

        $query = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->whereIn('kpusk', $puskesmasInKecamatan);
            
        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        $rekapData = $query->groupBy('kode_penyakit')
            ->orderByDesc('count')
            ->get();
            
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
            $queryPusk = RekamMedis::where('kpusk', $puskName)
                ->whereNotNull('kode_penyakit')
                ->select('kode_penyakit', DB::raw('count(*) as count'));
                
            if ($startDate && $endDate) {
                $queryPusk->whereBetween('tanggal', [$startDate, $endDate]);
            }

            $puskData = $queryPusk->groupBy('kode_penyakit')
                ->orderByDesc('count')
                ->get();
            
            if ($puskData->isNotEmpty()) {
                $puskesmasStats[] = (object)[
                    'nama' => $puskName,
                    'total_kasus' => $puskData->sum('count'),
                    'top_penyakit' => $puskData->first()
                ];
            }
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
        $filterMode = $request->input('export_filter_mode', 'include');
        $lettersStr = $request->input('export_letters', '');
        $letters = $lettersStr !== '' ? explode(',', $lettersStr) : [];
        
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
        if (!empty($letters)) {
            $query->where(function ($q) use ($letters, $filterMode) {
                foreach ($letters as $letter) {
                    if ($filterMode === 'include') {
                        $q->orWhere('kode_penyakit', 'LIKE', $letter . '%');
                    } else {
                        $q->where('kode_penyakit', 'NOT LIKE', $letter . '%');
                    }
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

        // ====== GENERATE EXCEL (CSV) ======
        if ($format === 'excel') {
            $filename = "Laporan_Rekap_Penyakit_" . date('Ymd_His') . ".csv";
            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $callback = function() use ($topUmum, $kecamatanData, $puskesmasData) {
                $file = fopen('php://output', 'w');
                // CSV Header / Section 1
                fputcsv($file, ['SECTION: TOP PENYAKIT UMUM (KESELURUHAN WILAYAH)']);
                fputcsv($file, ['Peringkat', 'Kode Penyakit (ICD-X)', 'Jumlah Kasus']);
                foreach ($topUmum as $index => $row) {
                    fputcsv($file, [$index + 1, $row->kode_penyakit, $row->count]);
                }
                fputcsv($file, []);

                // Section 2
                fputcsv($file, ['SECTION: TOP PENYAKIT PER KECAMATAN']);
                foreach ($kecamatanData as $kecName => $data) {
                    fputcsv($file, ["Kecamatan: $kecName"]);
                    fputcsv($file, ['Peringkat', 'Kode Penyakit (ICD-X)', 'Jumlah Kasus']);
                    foreach ($data as $index => $row) {
                        fputcsv($file, [$index + 1, $row->kode_penyakit, $row->count]);
                    }
                    fputcsv($file, []);
                }

                // Section 3
                fputcsv($file, ['SECTION: TOP PENYAKIT PER PUSKESMAS']);
                foreach ($puskesmasData as $puskName => $data) {
                    fputcsv($file, ["Puskesmas: $puskName"]);
                    fputcsv($file, ['Peringkat', 'Kode Penyakit (ICD-X)', 'Jumlah Kasus']);
                    foreach ($data as $index => $row) {
                        fputcsv($file, [$index + 1, $row->kode_penyakit, $row->count]);
                    }
                    fputcsv($file, []);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
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
            'filterMode',
            'letters'
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

        $query = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->where('kpusk', $puskesmas)
            ->whereYear('tanggal', $year);

        if ($periodType === 'month') {
            $query->whereMonth('tanggal', $periodValue);
        } elseif ($periodType === 'quarter') {
            $startMonth = ($periodValue - 1) * 3 + 1;
            $endMonth = $startMonth + 2;
            $query->whereMonth('tanggal', '>=', $startMonth)
                  ->whereMonth('tanggal', '<=', $endMonth);
        } elseif ($periodType === 'semester') {
            $startMonth = ($periodValue - 1) * 6 + 1;
            $endMonth = $startMonth + 5;
            $query->whereMonth('tanggal', '>=', $startMonth)
                  ->whereMonth('tanggal', '<=', $endMonth);
        }

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

        $query = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->whereIn('kpusk', $puskesmasList)
            ->whereYear('tanggal', $year);

        if ($periodType === 'month') {
            $query->whereMonth('tanggal', $periodValue);
        } elseif ($periodType === 'quarter') {
            $startMonth = ($periodValue - 1) * 3 + 1;
            $endMonth = $startMonth + 2;
            $query->whereMonth('tanggal', '>=', $startMonth)
                  ->whereMonth('tanggal', '<=', $endMonth);
        } elseif ($periodType === 'semester') {
            $startMonth = ($periodValue - 1) * 6 + 1;
            $endMonth = $startMonth + 5;
            $query->whereMonth('tanggal', '>=', $startMonth)
                  ->whereMonth('tanggal', '<=', $endMonth);
        }

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
