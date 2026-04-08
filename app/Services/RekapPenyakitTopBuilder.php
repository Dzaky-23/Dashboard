<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RekapPenyakitTopBuilder
{
    private const TOP_LIMIT = 20;

    public function build(): void
    {
        DB::table('rekap_penyakit_top')->truncate();

        $this->buildGlobal();
        $this->buildKecamatan();
        $this->buildPuskesmas();
    }

    private function baseQuery(): Builder
    {
        return DB::table('history as h')
            ->leftJoin('bpjs_ref_icd as i', 'h.kode_penyakit', '=', 'i.kdDiag')
            ->leftJoin('ref_puskesmas as r', 'h.kpusk', '=', 'r.kode_puskesmas')
            ->whereNotNull('h.kode_penyakit');
    }

    private function buildGlobal(): void
    {
        $this->buildForScope('global', [], function (Builder $query) {
            return $query->selectRaw("
                'global' as scope,
                'all' as period_type,
                NULL as year,
                NULL as month,
                NULL as quarter,
                NULL as semester,
                NULL as kpusk,
                NULL as kode_kecamatan,
                h.kode_penyakit,
                COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                COUNT(*) as jumlah_kasus
            ")->groupBy('h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('global', ['year'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'global' as scope,
                    'year' as period_type,
                    YEAR(h.tanggal) as year,
                    NULL as month,
                    NULL as quarter,
                    NULL as semester,
                    NULL as kpusk,
                    NULL as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('global', ['year', 'semester'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'global' as scope,
                    'semester' as period_type,
                    YEAR(h.tanggal) as year,
                    NULL as month,
                    NULL as quarter,
                    IF(MONTH(h.tanggal) <= 6, 1, 2) as semester,
                    NULL as kpusk,
                    NULL as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'semester', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('global', ['year', 'quarter'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'global' as scope,
                    'quarter' as period_type,
                    YEAR(h.tanggal) as year,
                    NULL as month,
                    QUARTER(h.tanggal) as quarter,
                    NULL as semester,
                    NULL as kpusk,
                    NULL as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'quarter', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('global', ['year', 'month'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'global' as scope,
                    'month' as period_type,
                    YEAR(h.tanggal) as year,
                    MONTH(h.tanggal) as month,
                    NULL as quarter,
                    NULL as semester,
                    NULL as kpusk,
                    NULL as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'month', 'h.kode_penyakit', 'i.nmDiag');
        });
    }

    private function buildKecamatan(): void
    {
        $this->buildForScope('kecamatan', ['kode_kecamatan'], function (Builder $query) {
            return $query->selectRaw("
                'kecamatan' as scope,
                'all' as period_type,
                NULL as year,
                NULL as month,
                NULL as quarter,
                NULL as semester,
                NULL as kpusk,
                r.kode_kecamatan as kode_kecamatan,
                h.kode_penyakit,
                COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                COUNT(*) as jumlah_kasus
            ")->groupBy('r.kode_kecamatan', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('kecamatan', ['year', 'kode_kecamatan'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'kecamatan' as scope,
                    'year' as period_type,
                    YEAR(h.tanggal) as year,
                    NULL as month,
                    NULL as quarter,
                    NULL as semester,
                    NULL as kpusk,
                    r.kode_kecamatan as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'r.kode_kecamatan', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('kecamatan', ['year', 'semester', 'kode_kecamatan'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'kecamatan' as scope,
                    'semester' as period_type,
                    YEAR(h.tanggal) as year,
                    NULL as month,
                    NULL as quarter,
                    IF(MONTH(h.tanggal) <= 6, 1, 2) as semester,
                    NULL as kpusk,
                    r.kode_kecamatan as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'semester', 'r.kode_kecamatan', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('kecamatan', ['year', 'quarter', 'kode_kecamatan'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'kecamatan' as scope,
                    'quarter' as period_type,
                    YEAR(h.tanggal) as year,
                    NULL as month,
                    QUARTER(h.tanggal) as quarter,
                    NULL as semester,
                    NULL as kpusk,
                    r.kode_kecamatan as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'quarter', 'r.kode_kecamatan', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('kecamatan', ['year', 'month', 'kode_kecamatan'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'kecamatan' as scope,
                    'month' as period_type,
                    YEAR(h.tanggal) as year,
                    MONTH(h.tanggal) as month,
                    NULL as quarter,
                    NULL as semester,
                    NULL as kpusk,
                    r.kode_kecamatan as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'month', 'r.kode_kecamatan', 'h.kode_penyakit', 'i.nmDiag');
        });
    }

    private function buildPuskesmas(): void
    {
        $this->buildForScope('puskesmas', ['kpusk'], function (Builder $query) {
            return $query->selectRaw("
                'puskesmas' as scope,
                'all' as period_type,
                NULL as year,
                NULL as month,
                NULL as quarter,
                NULL as semester,
                h.kpusk as kpusk,
                r.kode_kecamatan as kode_kecamatan,
                h.kode_penyakit,
                COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                COUNT(*) as jumlah_kasus
            ")->groupBy('h.kpusk', 'r.kode_kecamatan', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('puskesmas', ['year', 'kpusk'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'puskesmas' as scope,
                    'year' as period_type,
                    YEAR(h.tanggal) as year,
                    NULL as month,
                    NULL as quarter,
                    NULL as semester,
                    h.kpusk as kpusk,
                    r.kode_kecamatan as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'h.kpusk', 'r.kode_kecamatan', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('puskesmas', ['year', 'semester', 'kpusk'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'puskesmas' as scope,
                    'semester' as period_type,
                    YEAR(h.tanggal) as year,
                    NULL as month,
                    NULL as quarter,
                    IF(MONTH(h.tanggal) <= 6, 1, 2) as semester,
                    h.kpusk as kpusk,
                    r.kode_kecamatan as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'semester', 'h.kpusk', 'r.kode_kecamatan', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('puskesmas', ['year', 'quarter', 'kpusk'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'puskesmas' as scope,
                    'quarter' as period_type,
                    YEAR(h.tanggal) as year,
                    NULL as month,
                    QUARTER(h.tanggal) as quarter,
                    NULL as semester,
                    h.kpusk as kpusk,
                    r.kode_kecamatan as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'quarter', 'h.kpusk', 'r.kode_kecamatan', 'h.kode_penyakit', 'i.nmDiag');
        });

        $this->buildForScope('puskesmas', ['year', 'month', 'kpusk'], function (Builder $query) {
            return $query->whereNotNull('h.tanggal')
                ->selectRaw("
                    'puskesmas' as scope,
                    'month' as period_type,
                    YEAR(h.tanggal) as year,
                    MONTH(h.tanggal) as month,
                    NULL as quarter,
                    NULL as semester,
                    h.kpusk as kpusk,
                    r.kode_kecamatan as kode_kecamatan,
                    h.kode_penyakit,
                    COALESCE(i.nmDiag, h.kode_penyakit) as nama_penyakit,
                    COUNT(*) as jumlah_kasus
                ")->groupBy('year', 'month', 'h.kpusk', 'r.kode_kecamatan', 'h.kode_penyakit', 'i.nmDiag');
        });
    }

    private function buildForScope(string $scope, array $groupKeyFields, callable $builder): void
    {
        $query = $this->baseQuery();
        $query = $builder($query);

        $rows = $query
            ->orderBy($groupKeyFields[0] ?? DB::raw('1'))
            ->when(count($groupKeyFields) > 1, function (Builder $q) use ($groupKeyFields) {
                foreach (array_slice($groupKeyFields, 1) as $field) {
                    $q->orderBy($field);
                }
            })
            ->orderByDesc('jumlah_kasus')
            ->get();

        $this->insertTopRows($rows, $groupKeyFields);
    }

    private function insertTopRows(Collection $rows, array $groupKeyFields): void
    {
        $buffer = [];
        $chunkSize = 1000;
        $now = now();

        $currentKey = null;
        $currentTop = [];
        $currentTotal = 0;
        $currentRank = 0;

        $flushGroup = function () use (&$buffer, &$currentTop, &$currentTotal, &$chunkSize, $now) {
            foreach ($currentTop as $item) {
                $item['total_kasus'] = $currentTotal;
                $item['created_at'] = $now;
                $item['updated_at'] = $now;
                $buffer[] = $item;

                if (count($buffer) >= $chunkSize) {
                    DB::table('rekap_penyakit_top')->insert($buffer);
                    $buffer = [];
                }
            }
            $currentTop = [];
            $currentTotal = 0;
        };

        foreach ($rows as $row) {
            $rowArr = (array) $row;
            $keyParts = [];
            foreach ($groupKeyFields as $field) {
                $keyParts[] = $rowArr[$field] ?? '';
            }
            $groupKey = implode('|', $keyParts);

            if ($currentKey !== null && $groupKey !== $currentKey) {
                $flushGroup();
                $currentRank = 0;
            }

            $currentKey = $groupKey;
            $currentTotal += (int) $rowArr['jumlah_kasus'];

            if ($currentRank < self::TOP_LIMIT) {
                $currentRank++;
                $currentTop[] = [
                    'scope' => $rowArr['scope'],
                    'period_type' => $rowArr['period_type'],
                    'year' => $rowArr['year'] ?? null,
                    'month' => $rowArr['month'] ?? null,
                    'quarter' => $rowArr['quarter'] ?? null,
                    'semester' => $rowArr['semester'] ?? null,
                    'kpusk' => $rowArr['kpusk'] ?? null,
                    'kode_kecamatan' => $rowArr['kode_kecamatan'] ?? null,
                    'rank' => $currentRank,
                    'kode_penyakit' => $rowArr['kode_penyakit'],
                    'nama_penyakit' => $rowArr['nama_penyakit'] ?? null,
                    'jumlah_kasus' => (int) $rowArr['jumlah_kasus'],
                ];
            }
        }

        if ($currentKey !== null) {
            $flushGroup();
        }

        if (!empty($buffer)) {
            DB::table('rekap_penyakit_top')->insert($buffer);
        }
    }
}
