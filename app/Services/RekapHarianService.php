<?php

namespace App\Services;

use App\Models\RekapHarian;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RekapHarianService
{
    /**
     * Agregasi satu bulan penuh dari lb1_penta ke rekap_harian (idempotent).
     */
    public function aggregateByMonth(Carbon $month): void
    {
        $startOfMonth = $month->copy()->startOfMonth()->toDateString();
        $endOfMonth = $month->copy()->endOfMonth()->toDateString();

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $sql = "INSERT INTO rekap_harian (tanggal, kode_puskesmas, kode_penyakit, jumlah_kasus, created_at, updated_at)
                    SELECT 
                        tanggal, 
                        kpusk AS kode_puskesmas, 
                        diagnosa AS kode_penyakit, 
                        COUNT(*) AS jumlah_kasus,
                        datetime('now') AS created_at,
                        datetime('now') AS updated_at
                    FROM lb1_penta_clean
                    WHERE tanggal BETWEEN ? AND ?
                      AND kpusk IS NOT NULL AND kpusk <> ''
                      AND diagnosa IS NOT NULL AND diagnosa <> ''
                    GROUP BY tanggal, kpusk, diagnosa
                    ON CONFLICT(tanggal, kode_puskesmas, kode_penyakit) DO UPDATE SET 
                        jumlah_kasus = excluded.jumlah_kasus,
                        updated_at = excluded.updated_at";
        } else {
            $sql = "INSERT INTO rekap_harian (tanggal, kode_puskesmas, kode_penyakit, jumlah_kasus, created_at, updated_at)
                    SELECT 
                        tanggal, 
                        kpusk AS kode_puskesmas, 
                        diagnosa AS kode_penyakit, 
                        COUNT(*) AS jumlah_kasus,
                        NOW() AS created_at,
                        NOW() AS updated_at
                    FROM lb1_penta_clean
                    WHERE tanggal BETWEEN ? AND ?
                      AND kpusk IS NOT NULL AND kpusk <> ''
                      AND diagnosa IS NOT NULL AND diagnosa <> ''
                    GROUP BY tanggal, kpusk, diagnosa
                    ON DUPLICATE KEY UPDATE 
                        jumlah_kasus = VALUES(jumlah_kasus),
                        updated_at = VALUES(updated_at)";
        }

        DB::statement($sql, [$startOfMonth, $endOfMonth]);

        // Invalidate cache
        $this->invalidateCache();
    }

    /**
     * Agregasi rentang tanggal secara iteratif per bulan (idempotent).
     */
    public function aggregateByRange(Carbon $from, Carbon $to): void
    {
        $current = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();

        while ($current->lte($end)) {
            $this->aggregateByMonth($current);
            $current->addMonth();
        }
    }

    /**
     * Invalidate cache by updating the global cache version.
     */
    public function invalidateCache(): void
    {
        Cache::forever('rekap_cache_version', time());
    }

    /**
     * Query Top N Penyakit Umum (Keseluruhan Wilayah)
     */
    public function resolveRekapSource(Carbon $from, Carbon $to): string
    {
        // Tahun penuh (1 Jan s.d. 31 Des)
        if ($from->month === 1 && $from->day === 1 && $to->month === 12 && $to->day === 31) {
            return 'tahunan';
        }

        // Bulan/Triwulan/Semester penuh (Tgl 1 s.d. Akhir Bulan)
        if ($from->day === 1 && $to->day === $to->copy()->endOfMonth()->day) {
            return 'bulanan';
        }

        return 'harian';
    }

    public function applyBulananDateFilter($query, Carbon $from, Carbon $to): void
    {
        $query->where(function($q) use ($from, $to) {
            $fromYear = $from->year;
            $fromMonth = $from->month;
            $toYear = $to->year;
            $toMonth = $to->month;

            if ($fromYear === $toYear) {
                $q->where('rh.tahun', $fromYear)
                  ->whereBetween('rh.bulan', [$fromMonth, $toMonth]);
            } else {
                $q->where(function($sub) use ($fromYear, $fromMonth, $toYear, $toMonth) {
                    $sub->where(function($q1) use ($fromYear, $fromMonth) {
                        $q1->where('rh.tahun', $fromYear)->where('rh.bulan', '>=', $fromMonth);
                    })->orWhere(function($q2) use ($toYear, $toMonth) {
                        $q2->where('rh.tahun', $toYear)->where('rh.bulan', '<=', $toMonth);
                    });
                    if ($toYear - $fromYear > 1) {
                        $sub->orWhere(function($q3) use ($fromYear, $toYear) {
                            $q3->whereBetween('rh.tahun', [$fromYear + 1, $toYear - 1]);
                        });
                    }
                });
            }
        });
    }

    /**
     * Query Top N Penyakit Umum (Keseluruhan Wilayah)
     */
    public function queryTopUmum(
        Carbon $from,
        Carbon $to,
        int $topN = 10,
        array $includePrefixes = [],
        array $excludePrefixes = [],
        array $includeCodes = [],
        array $excludeCodes = [],
        array $excludeExceptions = []
    ) {
        $params = compact('from', 'to', 'topN', 'includePrefixes', 'excludePrefixes', 'includeCodes', 'excludeCodes', 'excludeExceptions');
        $cacheKey = $this->generateCacheKey('umum', $params);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($from, $to, $topN, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions) {
            $source = $this->resolveRekapSource($from, $to);

            if ($source === 'tahunan') {
                $query = DB::table('rekap_tahunan as rh')
                    ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
                    ->select([
                        'rh.kode_penyakit',
                        DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"),
                        DB::raw("SUM(rh.jumlah_kasus) as total")
                    ])
                    ->whereBetween('rh.tahun', [$from->year, $to->year]);
            } elseif ($source === 'bulanan') {
                $query = DB::table('rekap_bulanan as rh')
                    ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
                    ->select([
                        'rh.kode_penyakit',
                        DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"),
                        DB::raw("SUM(rh.jumlah_kasus) as total")
                    ]);
                $this->applyBulananDateFilter($query, $from, $to);
            } else {
                $query = DB::table('rekap_harian as rh')
                    ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
                    ->select([
                        'rh.kode_penyakit',
                        DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"),
                        DB::raw("SUM(rh.jumlah_kasus) as total")
                    ])
                    ->whereBetween('rh.tanggal', [$from->toDateString(), $to->toDateString()]);
            }

            $this->applyKodePenyakitFilters($query, 'rh.kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);

            return $query->groupBy('rh.kode_penyakit', 'icd.nmDiag')
                ->orderByDesc('total')
                ->limit($topN)
                ->get();
        });
    }

    /**
     * Query Top N Penyakit Per Kecamatan
     */
    public function queryTopPerKecamatan(
        Carbon $from,
        Carbon $to,
        int $topN = 10,
        ?array $kodeKc = null,
        array $includePrefixes = [],
        array $excludePrefixes = [],
        array $includeCodes = [],
        array $excludeCodes = [],
        array $excludeExceptions = []
    ) {
        $params = compact('from', 'to', 'topN', 'kodeKc', 'includePrefixes', 'excludePrefixes', 'includeCodes', 'excludeCodes', 'excludeExceptions');
        $cacheKey = $this->generateCacheKey('kecamatan', $params);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($from, $to, $topN, $kodeKc, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions) {
            $source = $this->resolveRekapSource($from, $to);

            if ($source === 'tahunan') {
                $subquery = DB::table('rekap_tahunan as rh')
                    ->join('kecamatan as k', 'rh.kode_kecamatan', '=', 'k.kode_kc')
                    ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
                    ->select([
                        'k.kode_kc',
                        'k.kecamatan',
                        'rh.kode_penyakit',
                        DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"),
                        DB::raw("SUM(rh.jumlah_kasus) as count"),
                        DB::raw("ROW_NUMBER() OVER (PARTITION BY k.kode_kc ORDER BY SUM(rh.jumlah_kasus) DESC) as rnk")
                    ])
                    ->whereBetween('rh.tahun', [$from->year, $to->year]);
            } elseif ($source === 'bulanan') {
                $subquery = DB::table('rekap_bulanan as rh')
                    ->join('kecamatan as k', 'rh.kode_kecamatan', '=', 'k.kode_kc')
                    ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
                    ->select([
                        'k.kode_kc',
                        'k.kecamatan',
                        'rh.kode_penyakit',
                        DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"),
                        DB::raw("SUM(rh.jumlah_kasus) as count"),
                        DB::raw("ROW_NUMBER() OVER (PARTITION BY k.kode_kc ORDER BY SUM(rh.jumlah_kasus) DESC) as rnk")
                    ]);
                $this->applyBulananDateFilter($subquery, $from, $to);
            } else {
                $subquery = DB::table('rekap_harian as rh')
                    ->join('puskesmas as p', 'rh.kode_puskesmas', '=', 'p.kode_p')
                    ->join('kecamatan as k', 'p.kode_kc', '=', 'k.kode_kc')
                    ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
                    ->select([
                        'k.kode_kc',
                        'k.kecamatan',
                        'rh.kode_penyakit',
                        DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"),
                        DB::raw("SUM(rh.jumlah_kasus) as count"),
                        DB::raw("ROW_NUMBER() OVER (PARTITION BY k.kode_kc ORDER BY SUM(rh.jumlah_kasus) DESC) as rnk")
                    ])
                    ->whereBetween('rh.tanggal', [$from->toDateString(), $to->toDateString()]);
            }

            if ($kodeKc !== null && count($kodeKc) > 0) {
                $subquery->whereIn('k.kode_kc', $kodeKc);
            }

            $this->applyKodePenyakitFilters($subquery, 'rh.kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);

            $subquery->groupBy('k.kode_kc', 'k.kecamatan', 'rh.kode_penyakit', 'icd.nmDiag');

            return DB::query()
                ->fromSub($subquery, 'ranked')
                ->where('rnk', '<=', $topN)
                ->orderBy('kode_kc')
                ->orderBy('rnk', 'asc')
                ->get();
        });
    }

    /**
     * Query Top N Penyakit Per Puskesmas
     */
    public function queryTopPerPuskesmas(
        Carbon $from,
        Carbon $to,
        int $topN = 10,
        ?array $kodeP = null,
        array $includePrefixes = [],
        array $excludePrefixes = [],
        array $includeCodes = [],
        array $excludeCodes = [],
        array $excludeExceptions = []
    ) {
        $params = compact('from', 'to', 'topN', 'kodeP', 'includePrefixes', 'excludePrefixes', 'includeCodes', 'excludeCodes', 'excludeExceptions');
        $cacheKey = $this->generateCacheKey('puskesmas', $params);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($from, $to, $topN, $kodeP, $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions) {
            $source = $this->resolveRekapSource($from, $to);

            if ($source === 'tahunan') {
                $subquery = DB::table('rekap_tahunan as rh')
                    ->join('puskesmas as p', 'rh.kode_puskesmas', '=', 'p.kode_p')
                    ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
                    ->select([
                        'p.kode_p as kpusk',
                        'p.nama as nama_puskesmas',
                        'rh.kode_kecamatan',
                        'rh.kode_penyakit',
                        DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"),
                        DB::raw("SUM(rh.jumlah_kasus) as count"),
                        DB::raw("ROW_NUMBER() OVER (PARTITION BY p.kode_p ORDER BY SUM(rh.jumlah_kasus) DESC) as rnk")
                    ])
                    ->whereBetween('rh.tahun', [$from->year, $to->year]);
            } elseif ($source === 'bulanan') {
                $subquery = DB::table('rekap_bulanan as rh')
                    ->join('puskesmas as p', 'rh.kode_puskesmas', '=', 'p.kode_p')
                    ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
                    ->select([
                        'p.kode_p as kpusk',
                        'p.nama as nama_puskesmas',
                        'rh.kode_kecamatan',
                        'rh.kode_penyakit',
                        DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"),
                        DB::raw("SUM(rh.jumlah_kasus) as count"),
                        DB::raw("ROW_NUMBER() OVER (PARTITION BY p.kode_p ORDER BY SUM(rh.jumlah_kasus) DESC) as rnk")
                    ]);
                $this->applyBulananDateFilter($subquery, $from, $to);
            } else {
                $subquery = DB::table('rekap_harian as rh')
                    ->join('puskesmas as p', 'rh.kode_puskesmas', '=', 'p.kode_p')
                    ->leftJoin('bpjs_ref_icd as icd', 'rh.kode_penyakit', '=', 'icd.kdDiag')
                    ->select([
                        'p.kode_p as kpusk',
                        'p.nama as nama_puskesmas',
                        'p.kode_kc as kode_kecamatan',
                        'rh.kode_penyakit',
                        DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), rh.kode_penyakit) as nama_penyakit"),
                        DB::raw("SUM(rh.jumlah_kasus) as count"),
                        DB::raw("ROW_NUMBER() OVER (PARTITION BY p.kode_p ORDER BY SUM(rh.jumlah_kasus) DESC) as rnk")
                    ])
                    ->whereBetween('rh.tanggal', [$from->toDateString(), $to->toDateString()]);
            }

            if ($kodeP !== null && count($kodeP) > 0) {
                $subquery->whereIn('p.kode_p', $kodeP);
            }

            $this->applyKodePenyakitFilters($subquery, 'rh.kode_penyakit', $includePrefixes, $excludePrefixes, $includeCodes, $excludeCodes, $excludeExceptions);

            if ($source === 'tahunan' || $source === 'bulanan') {
                $subquery->groupBy('p.kode_p', 'p.nama', 'rh.kode_kecamatan', 'rh.kode_penyakit', 'icd.nmDiag');
            } else {
                $subquery->groupBy('p.kode_p', 'p.nama', 'p.kode_kc', 'rh.kode_penyakit', 'icd.nmDiag');
            }

            return DB::query()
                ->fromSub($subquery, 'ranked')
                ->where('rnk', '<=', $topN)
                ->orderBy('kpusk')
                ->orderBy('rnk', 'asc')
                ->get();
        });
    }

    /**
     * Generate unique cache key with cache versioning.
     */
    private function generateCacheKey(string $scope, array $params): string
    {
        $version = Cache::rememberForever('rekap_cache_version', fn() => time());

        $normalized = [];
        foreach ($params as $key => $val) {
            if ($val instanceof Carbon) {
                $normalized[$key] = $val->toDateString();
            } else {
                $normalized[$key] = $val;
            }
        }

        $hash = md5(json_encode($normalized));

        return "rekap_v{$version}_{$scope}_{$hash}";
    }

    /**
     * Helper to apply ICD filters.
     */
    private function applyKodePenyakitFilters(
        $query,
        string $column,
        array $includePrefixes,
        array $excludePrefixes,
        array $includeCodes,
        array $excludeCodes,
        array $excludeExceptions = []
    ): void {
        $allExceptions = array_values(array_unique(array_merge($includePrefixes, $includeCodes, $excludeExceptions)));

        if (!empty($includePrefixes) || !empty($includeCodes)) {
            $query->where(function ($q) use ($includePrefixes, $includeCodes, $column) {
                $hasCondition = false;

                if (!empty($includeCodes)) {
                    $q->whereIn(DB::raw($column), $includeCodes);
                    $hasCondition = true;
                }

                foreach ($includePrefixes as $index => $prefix) {
                    $method = ($hasCondition || $index > 0) ? 'orWhereRaw' : 'whereRaw';
                    $q->{$method}($column . ' LIKE ?', [$prefix . '%']);
                }
            });
        }

        if (!empty($excludeCodes)) {
            $query->whereNotIn(DB::raw($column), $excludeCodes);
        }

        foreach ($excludePrefixes as $prefix) {
            $prefixExceptions = [];
            foreach ($allExceptions as $exc) {
                if (strpos(strtoupper($exc), strtoupper($prefix)) === 0) {
                    $prefixExceptions[] = $exc;
                }
            }

            if (!empty($prefixExceptions)) {
                $query->where(function ($q) use ($column, $prefix, $prefixExceptions) {
                    $q->whereRaw($column . ' NOT LIKE ?', [$prefix . '%']);
                    foreach ($prefixExceptions as $exc) {
                        $q->orWhereRaw($column . ' LIKE ?', [$exc . '%']);
                    }
                });
            } else {
                $query->whereRaw($column . ' NOT LIKE ?', [$prefix . '%']);
            }
        }
    }
}
