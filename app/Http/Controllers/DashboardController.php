<?php

namespace App\Http\Controllers;

use App\Models\Pasien;
use App\Models\RekamMedis;
use App\Services\RecapLogicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $currentYear = date('Y');

        $pasienStats = Cache::remember('dashboard_pasien_stats', now()->addMinutes(10), function () {
            $stats = Pasien::selectRaw("
                COUNT(*) as totalPasien,
                SUM(CASE WHEN submited_at >= ? AND submited_at <= ? THEN 1 ELSE 0 END) as pasienBaruToday,
                SUM(CASE WHEN cara_bayar = 'BPJS' THEN 1 ELSE 0 END) as totalBPJS,
                SUM(CASE WHEN cara_bayar = 'Umum' THEN 1 ELSE 0 END) as totalUmum
            ", [today()->startOfDay(), today()->endOfDay()])->first();

            return [
                'totalPasien' => (int) $stats->totalPasien,
                'pasienBaruToday' => (int) $stats->pasienBaruToday,
                'totalBPJS' => (int) $stats->totalBPJS,
                'totalUmum' => (int) $stats->totalUmum,
            ];
        });

        $totalPasien = $pasienStats['totalPasien'];
        $pasienBaruToday = $pasienStats['pasienBaruToday'];
        $totalBPJS = $pasienStats['totalBPJS'];
        $totalUmum = $pasienStats['totalUmum'];

        // 4 Metrik Rekap Penyakit Global
        $penyakitStats = Cache::remember('dashboard_penyakit_stats_' . $currentYear, now()->addMinutes(10), function () use ($currentYear) {
            $startDate = Carbon::create($currentYear)->startOfYear();
            $endDate = Carbon::create($currentYear)->endOfYear();

            $totalKasusPenyakit = RekamMedis::whereNotNull('kode_penyakit')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->count();
            
            $topPenyakitData = RekamMedis::select('kode_penyakit', DB::raw('count(*) as count'))
                ->whereNotNull('kode_penyakit')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->groupBy('kode_penyakit')
                ->orderByDesc('count')
                ->first();

            return [
                'totalKasusPenyakit' => $totalKasusPenyakit,
                'topPenyakitData' => $topPenyakitData
            ];
        });

        $totalKasusPenyakit = $penyakitStats['totalKasusPenyakit'];
        $topPenyakitData = $penyakitStats['topPenyakitData'];
            
        $topIcdName = $topPenyakitData ? (\App\Services\RecapLogicService::getIcdNames([$topPenyakitData->kode_penyakit])[$topPenyakitData->kode_penyakit] ?? $topPenyakitData->kode_penyakit) : '';
        $topPenyakitGlobal = $topPenyakitData ? $topIcdName . ' (' . $topPenyakitData->count . ' Kasus)' : 'Tidak Ada';

        $totalPuskesmas = \App\Models\RefPuskesmas::count();
        $totalKecamatan = \App\Models\RefPuskesmas::distinct('kode_kecamatan')->count();

        // Data Tren 12 Bulan
        $trendStats = Cache::remember('dashboard_trend_stats_' . $currentYear, now()->addMinutes(10), function () use ($currentYear) {
            $startDate = Carbon::create($currentYear)->startOfYear();
            $endDate = Carbon::create($currentYear)->endOfYear();

            $monthlyCases = RekamMedis::selectRaw('MONTH(tanggal) as month, count(*) as total')
                ->whereNotNull('kode_penyakit')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->groupBy('month')
                ->pluck('total', 'month')
                ->all();

            $trendData = [];
            for ($i = 1; $i <= 12; $i++) {
                $trendData[] = $monthlyCases[$i] ?? 0;
            }
            return $trendData;
        });

        return view('dashboard', compact(
            'totalPasien', 'pasienBaruToday', 'totalBPJS', 'totalUmum',
            'totalKasusPenyakit', 'topPenyakitGlobal', 'totalPuskesmas', 'totalKecamatan', 'trendStats', 'currentYear'
        ));
    }
}
