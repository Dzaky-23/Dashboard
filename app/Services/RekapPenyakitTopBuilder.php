<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RekapPenyakitTopBuilder
{
    private const CHUNK_SIZE = 5000;
    private const JOB_NAME = 'recap-top-build';

    public function build(): void
    {
        // 1. Get watermark
        $log = DB::table('rekap_logs')
            ->where('job_name', self::JOB_NAME)
            ->first();
        $lastProcessedId = $log ? $log->last_processed_id : 0;

        // 2. Chunk processing from history table
        DB::table('history')
            ->select('id')
            ->where('id', '>', $lastProcessedId)
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($records) {
                $this->processChunk($records);

                $lastId = $records->last()?->id;
                if ($lastId) {
                    $now = now();
                    DB::table('rekap_logs')->updateOrInsert(
                        ['job_name' => self::JOB_NAME],
                        [
                            'last_processed_id' => $lastId,
                            'last_processed_at' => $now,
                            'updated_at'        => $now,
                            'created_at'        => $now,
                        ]
                    );
                }
            });
    }

    private function processChunk(iterable $records): void
    {
        if ($records instanceof Collection) {
            $records = $records->values();
        } else {
            $records = collect($records)->values();
        }

        if ($records->isEmpty()) {
            return;
        }

        $minId = (int) $records->first()->id;
        $maxId = (int) $records->last()->id;

        foreach ($this->aggregateDefinitions() as $definition) {
            $this->upsertAggregateRange($minId, $maxId, $definition);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aggregateDefinitions(): array
    {
        $types = [
            ['period_type' => 'all', 'year' => '0', 'month' => '0', 'quarter' => '0', 'semester' => '0', 'where' => [], 'group_by' => []],
            ['period_type' => 'year', 'year' => 'YEAR(h.tanggal)', 'month' => '0', 'quarter' => '0', 'semester' => '0', 'where' => ['h.tanggal IS NOT NULL'], 'group_by' => ['YEAR(h.tanggal)']],
            ['period_type' => 'semester', 'year' => 'YEAR(h.tanggal)', 'month' => '0', 'quarter' => '0', 'semester' => 'CASE WHEN MONTH(h.tanggal) <= 6 THEN 1 ELSE 2 END', 'where' => ['h.tanggal IS NOT NULL'], 'group_by' => ['YEAR(h.tanggal)', 'CASE WHEN MONTH(h.tanggal) <= 6 THEN 1 ELSE 2 END']],
            ['period_type' => 'quarter', 'year' => 'YEAR(h.tanggal)', 'month' => '0', 'quarter' => 'QUARTER(h.tanggal)', 'semester' => '0', 'where' => ['h.tanggal IS NOT NULL'], 'group_by' => ['YEAR(h.tanggal)', 'QUARTER(h.tanggal)']],
            ['period_type' => 'month', 'year' => 'YEAR(h.tanggal)', 'month' => 'MONTH(h.tanggal)', 'quarter' => '0', 'semester' => '0', 'where' => ['h.tanggal IS NOT NULL'], 'group_by' => ['YEAR(h.tanggal)', 'MONTH(h.tanggal)']],
        ];

        $scopes = [
            [
                'scope' => 'global',
                'kpusk' => "''",
                'kode_kecamatan' => "''",
                'where' => [],
                'group_by' => [],
            ],
            [
                'scope' => 'kecamatan',
                'kpusk' => "''",
                'kode_kecamatan' => "TRIM(COALESCE(rp.kode_kecamatan, ''))",
                'where' => ["TRIM(COALESCE(rp.kode_kecamatan, '')) <> ''"],
                'group_by' => ["TRIM(COALESCE(rp.kode_kecamatan, ''))"],
            ],
            [
                'scope' => 'puskesmas',
                'kpusk' => "TRIM(COALESCE(h.kpusk, ''))",
                'kode_kecamatan' => "TRIM(COALESCE(rp.kode_kecamatan, ''))",
                'where' => ["TRIM(COALESCE(h.kpusk, '')) <> ''"],
                'group_by' => ["TRIM(COALESCE(h.kpusk, ''))", "TRIM(COALESCE(rp.kode_kecamatan, ''))"],
            ],
        ];

        $definitions = [];
        foreach ($scopes as $scope) {
            foreach ($types as $type) {
                $definitions[] = [
                    'scope' => $scope['scope'],
                    'period_type' => $type['period_type'],
                    'year' => $type['year'],
                    'month' => $type['month'],
                    'quarter' => $type['quarter'],
                    'semester' => $type['semester'],
                    'kpusk' => $scope['kpusk'],
                    'kode_kecamatan' => $scope['kode_kecamatan'],
                    'where' => array_merge($scope['where'], $type['where']),
                    'group_by' => array_merge(
                        $scope['group_by'],
                        $type['group_by'],
                        ['TRIM(h.kode_penyakit)', "COALESCE(NULLIF(icd.nmDiag, ''), TRIM(h.kode_penyakit))"]
                    ),
                ];
            }
        }

        return $definitions;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function upsertAggregateRange(int $minId, int $maxId, array $definition): void
    {
        $selectColumns = [
            "'{$definition['scope']}' AS scope",
            "'{$definition['period_type']}' AS period_type",
            "{$definition['year']} AS year",
            "{$definition['month']} AS month",
            "{$definition['quarter']} AS quarter",
            "{$definition['semester']} AS semester",
            "{$definition['kpusk']} AS kpusk",
            "{$definition['kode_kecamatan']} AS kode_kecamatan",
            "TRIM(h.kode_penyakit) AS kode_penyakit",
            "COALESCE(NULLIF(icd.nmDiag, ''), TRIM(h.kode_penyakit)) AS nama_penyakit",
            'COUNT(*) AS jumlah_kasus',
            'NOW() AS created_at',
            'NOW() AS updated_at',
        ];

        $where = array_merge(
            [
                'h.id >= ?',
                'h.id <= ?',
                "TRIM(COALESCE(h.kode_penyakit, '')) <> ''",
            ],
            $definition['where']
        );

        $sql = "INSERT INTO `rekap_penyakit_top` "
            . "(`scope`, `period_type`, `year`, `month`, `quarter`, `semester`, `kpusk`, `kode_kecamatan`, `kode_penyakit`, `nama_penyakit`, `jumlah_kasus`, `created_at`, `updated_at`) "
            . "SELECT " . implode(",\n                ", $selectColumns) . "\n"
            . "FROM `history` h\n"
            . "LEFT JOIN `ref_puskesmas` rp ON TRIM(COALESCE(h.kpusk, '')) = TRIM(COALESCE(rp.kode_puskesmas, ''))\n"
            . "LEFT JOIN `bpjs_ref_icd` icd ON TRIM(h.kode_penyakit) = TRIM(icd.kdDiag)\n"
            . "WHERE " . implode("\n  AND ", $where) . "\n"
            . "GROUP BY " . implode(",\n                ", $definition['group_by']) . "\n"
            . "ON DUPLICATE KEY UPDATE "
            . "`nama_penyakit` = VALUES(`nama_penyakit`), "
            . "`jumlah_kasus` = `jumlah_kasus` + VALUES(`jumlah_kasus`), "
            . "`updated_at` = VALUES(`updated_at`)";

        DB::statement($sql, [$minId, $maxId]);
    }
}
