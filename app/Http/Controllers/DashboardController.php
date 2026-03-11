<?php

namespace App\Http\Controllers;

use App\Models\Pasien;
use App\Models\RekamMedis;
use App\Services\RecapLogicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPasien = Pasien::count();
        $pasienBaruToday = Pasien::whereDate('submited_at', today())->count();
        $totalBPJS = Pasien::where('cara_bayar', 'BPJS')->count();
        $totalUmum = Pasien::where('cara_bayar', 'Umum')->count();

        // 4 Metrik Rekap Penyakit Global
        $totalKasusPenyakit = RekamMedis::whereNotNull('kode_penyakit')->count();
        
        $topPenyakitData = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
            ->whereNotNull('kode_penyakit')
            ->groupBy('kode_penyakit')
            ->orderByDesc('count')
            ->first();
            
        $topPenyakitGlobal = $topPenyakitData ? $topPenyakitData->kode_penyakit . ' (' . $topPenyakitData->count . ' Kasus)' : 'Tidak Ada';

        $mapping = RecapLogicService::MAPPING_KECAMATAN;
        $totalPuskesmas = count(array_keys($mapping));
        $totalKecamatan = count(array_unique(array_values($mapping)));

        return view('dashboard', compact(
            'totalPasien', 'pasienBaruToday', 'totalBPJS', 'totalUmum',
            'totalKasusPenyakit', 'topPenyakitGlobal', 'totalPuskesmas', 'totalKecamatan'
        ));
    }
}
