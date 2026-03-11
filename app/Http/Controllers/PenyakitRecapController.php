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
        $mapping = RecapLogicService::MAPPING_KECAMATAN;
        
        $filterPuskesmas = $request->input('puskesmas');
        $filterKecamatan = $request->input('kecamatan');

        $queryRekap = RekamMedis::select('kpusk', 'kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit');

        $kecamatanSummary = null;

        if ($filterPuskesmas) {
            $queryRekap->where('kpusk', $filterPuskesmas);
        } elseif ($filterKecamatan) {
            $puskesmasInKecamatan = array_keys(array_filter($mapping, function ($kecamatan) use ($filterKecamatan) {
                return $kecamatan === $filterKecamatan;
            }));
            $queryRekap->whereIn('kpusk', $puskesmasInKecamatan);

            // Hitung data ringkasan level Kecamatan khusus
            $kecamatanPuskesmasCount = count($puskesmasInKecamatan);
            
            $kecamatanPenyakitData = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
                ->whereNotNull('kode_penyakit')
                ->whereIn('kpusk', $puskesmasInKecamatan)
                ->groupBy('kode_penyakit')
                ->orderByDesc('count')
                ->get();

            $kecamatanTotalKasus = $kecamatanPenyakitData->sum('count');
            
            $kecamatanSummary = [
                'nama' => $filterKecamatan,
                'total_puskesmas' => $kecamatanPuskesmasCount,
                'total_kasus' => $kecamatanTotalKasus,
                'penyakit_teratas' => $kecamatanPenyakitData->take(5) // Ambil Top 5 penyakit
            ];
        }

        $rekapData = $queryRekap->groupBy('kpusk', 'kode_penyakit')
            ->orderBy('kpusk')
            ->orderByDesc('count')
            ->get();
            
        $groupedByPusk = $rekapData->groupBy('kpusk');
        
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

        // Data for dropdowns
        $listPuskesmas = array_keys($mapping);
        sort($listPuskesmas);
        $listKecamatan = array_unique(array_values($mapping));
        sort($listKecamatan);

        return view('recap.index', compact(
            'groupedByPusk', 'mapping', 'listPuskesmas', 'listKecamatan', 
            'filterPuskesmas', 'filterKecamatan', 'kecamatanSummary',
            'totalKasus', 'topPenyakit', 'totalPuskesmas', 'totalKecamatan'
        ));
    }

    public function show($puskesmas)
    {
        $mapping = RecapLogicService::MAPPING_KECAMATAN;
        $kecamatan = $mapping[$puskesmas] ?? 'Tidak Diketahui';

        $rekapData = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->where('kpusk', $puskesmas)
            ->groupBy('kode_penyakit')
            ->orderByDesc('count')
            ->get();

        $totalKasus = $rekapData->sum('count');

        return view('recap.show', compact('puskesmas', 'kecamatan', 'rekapData', 'totalKasus'));
    }

    public function showKecamatan($kecamatan)
    {
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

        $totalKasus = $rekapData->sum('count');
        $totalPuskesmas = count($puskesmasInKecamatan);

        return view('recap.show_kecamatan', compact('kecamatan', 'rekapData', 'totalKasus', 'totalPuskesmas'));
    }
}
