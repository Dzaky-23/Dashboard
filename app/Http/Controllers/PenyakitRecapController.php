<?php

namespace App\Http\Controllers;

use App\Models\RekamMedis;
use App\Services\RecapLogicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenyakitRecapController extends Controller
{
    public function index(Request $request)
    {
        $limit = 10;
        $mapping = RecapLogicService::MAPPING_KECAMATAN;

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
        $totalKasus = RekamMedis::whereNotNull('kode_penyakit')->count();
        $topPenyakitData = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->groupBy('kode_penyakit')
            ->orderByDesc('count')
            ->first();
        $topPenyakit = $topPenyakitData ? $topPenyakitData->kode_penyakit . ' (' . $topPenyakitData->count . ' Kasus)' : 'Tidak Ada';

        $totalPuskesmas = count(array_keys($mapping));
        $totalKecamatan = count(array_unique(array_values($mapping)));

        // --- GRAFIK GLOBAL UMUM & SMART ANALYSIS ---
        $rawDataSemua = RekamMedis::select('kpusk', 'kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')->groupBy('kpusk', 'kode_penyakit')->get()
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
            'totalPuskesmas', 'totalKecamatan', 'chartData', 'maxChartWidth'
        ));
    }

    public function show(Request $request, $puskesmas)
    {
        $limitInput = $request->input('limit');
        $limit = $limitInput === null ? 10 : (int) $limitInput;
        $mapping = RecapLogicService::MAPPING_KECAMATAN;
        $kecamatan = $mapping[$puskesmas] ?? 'Tidak Diketahui';

        $rekapData = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->where('kpusk', $puskesmas)
            ->groupBy('kode_penyakit')
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

        return view('recap.show', compact('puskesmas', 'kecamatan', 'rekapData', 'totalKasus', 'limit', 'rekapChartData', 'maxChartWidth', 'totalDiagnosaUnik', 'warningLimit'));
    }

    public function showKecamatan(Request $request, $kecamatan)
    {
        $limitInput = $request->input('limit');
        $limit = $limitInput === null ? 10 : (int) $limitInput;
        $mapping = RecapLogicService::MAPPING_KECAMATAN;
        
        $puskesmasInKecamatan = array_keys(array_filter($mapping, function ($val) use ($kecamatan) {
            return $val === $kecamatan;
        }));

        if (empty($puskesmasInKecamatan)) {
            abort(404, 'Kecamatan tidak ditemukan.');
        }

        $rekapData = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->whereIn('kpusk', $puskesmasInKecamatan)
            ->groupBy('kode_penyakit')
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
            $puskData = RekamMedis::where('kpusk', $puskName)
                ->whereNotNull('kode_penyakit')
                ->select('kode_penyakit', DB::raw('count(*) as count'))
                ->groupBy('kode_penyakit')
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

        return view('recap.show_kecamatan', compact('kecamatan', 'rekapData', 'totalKasus', 'totalPuskesmas', 'limit', 'rekapChartData', 'maxChartWidth', 'totalDiagnosaUnik', 'warningLimit', 'puskesmasStats'));
    }
}
