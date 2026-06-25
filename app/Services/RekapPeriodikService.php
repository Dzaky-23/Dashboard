<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RekapPeriodikService
{
    public function aggregateMonthlyByMonth(Carbon $month): void
    {
        $startOfMonth = $month->copy()->startOfMonth()->toDateString();
        $endOfMonth = $month->copy()->endOfMonth()->toDateString();
        $year = $month->year;
        $monthNum = $month->month;

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $sql = "INSERT INTO rekap_bulanan (tahun, bulan, kode_kecamatan, kode_puskesmas, kode_penyakit, jumlah_kasus, created_at, updated_at)
                    SELECT 
                        ? AS tahun,
                        ? AS bulan,
                        p.kode_kc AS kode_kecamatan,
                        rh.kode_puskesmas,
                        rh.kode_penyakit,
                        SUM(rh.jumlah_kasus) AS jumlah_kasus,
                        datetime('now') AS created_at,
                        datetime('now') AS updated_at
                    FROM rekap_harian rh
                    JOIN puskesmas p ON rh.kode_puskesmas = p.kode_p
                    WHERE rh.tanggal BETWEEN ? AND ?
                      AND rh.kode_puskesmas IS NOT NULL AND rh.kode_puskesmas <> ''
                      AND rh.kode_penyakit IS NOT NULL AND rh.kode_penyakit <> ''
                    GROUP BY p.kode_kc, rh.kode_puskesmas, rh.kode_penyakit
                    ON CONFLICT(tahun, bulan, kode_puskesmas, kode_penyakit) DO UPDATE SET 
                        kode_kecamatan = excluded.kode_kecamatan,
                        jumlah_kasus = excluded.jumlah_kasus,
                        updated_at = excluded.updated_at";
        } else {
            $sql = "INSERT INTO rekap_bulanan (tahun, bulan, kode_kecamatan, kode_puskesmas, kode_penyakit, jumlah_kasus, created_at, updated_at)
                    SELECT 
                        ? AS tahun,
                        ? AS bulan,
                        p.kode_kc AS kode_kecamatan,
                        rh.kode_puskesmas,
                        rh.kode_penyakit,
                        SUM(rh.jumlah_kasus) AS jumlah_kasus,
                        NOW() AS created_at,
                        NOW() AS updated_at
                    FROM rekap_harian rh
                    JOIN puskesmas p ON rh.kode_puskesmas = p.kode_p
                    WHERE rh.tanggal BETWEEN ? AND ?
                      AND rh.kode_puskesmas IS NOT NULL AND rh.kode_puskesmas <> ''
                      AND rh.kode_penyakit IS NOT NULL AND rh.kode_penyakit <> ''
                    GROUP BY p.kode_kc, rh.kode_puskesmas, rh.kode_penyakit
                    ON DUPLICATE KEY UPDATE 
                        kode_kecamatan = VALUES(kode_kecamatan),
                        jumlah_kasus = VALUES(jumlah_kasus),
                        updated_at = VALUES(updated_at)";
        }

        DB::statement($sql, [$year, $monthNum, $startOfMonth, $endOfMonth]);
    }

    public function aggregateMonthlyByRange(Carbon $from, Carbon $to): void
    {
        $current = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();

        while ($current->lte($end)) {
            $this->aggregateMonthlyByMonth($current);
            $current->addMonth();
        }
    }

    public function aggregateYearlyByYear(int $year): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $sql = "INSERT INTO rekap_tahunan (tahun, kode_kecamatan, kode_puskesmas, kode_penyakit, jumlah_kasus, created_at, updated_at)
                    SELECT 
                        tahun,
                        kode_kecamatan,
                        kode_puskesmas,
                        kode_penyakit,
                        SUM(jumlah_kasus) AS jumlah_kasus,
                        datetime('now') AS created_at,
                        datetime('now') AS updated_at
                    FROM rekap_bulanan
                    WHERE tahun = ?
                    GROUP BY tahun, kode_kecamatan, kode_puskesmas, kode_penyakit
                    ON CONFLICT(tahun, kode_puskesmas, kode_penyakit) DO UPDATE SET 
                        kode_kecamatan = excluded.kode_kecamatan,
                        jumlah_kasus = excluded.jumlah_kasus,
                        updated_at = excluded.updated_at";
        } else {
            $sql = "INSERT INTO rekap_tahunan (tahun, kode_kecamatan, kode_puskesmas, kode_penyakit, jumlah_kasus, created_at, updated_at)
                    SELECT 
                        tahun,
                        kode_kecamatan,
                        kode_puskesmas,
                        kode_penyakit,
                        SUM(jumlah_kasus) AS jumlah_kasus,
                        NOW() AS created_at,
                        NOW() AS updated_at
                    FROM rekap_bulanan
                    WHERE tahun = ?
                    GROUP BY tahun, kode_kecamatan, kode_puskesmas, kode_penyakit
                    ON DUPLICATE KEY UPDATE 
                        kode_kecamatan = VALUES(kode_kecamatan),
                        jumlah_kasus = VALUES(jumlah_kasus),
                        updated_at = VALUES(updated_at)";
        }

        DB::statement($sql, [$year]);
    }

    public function aggregateYearlyByRange(int $fromYear, int $toYear): void
    {
        for ($y = $fromYear; $y <= $toYear; $y++) {
            $this->aggregateYearlyByYear($y);
        }
    }

    public function invalidateCache(): void
    {
        Cache::forever('rekap_cache_version', time());
    }
}
