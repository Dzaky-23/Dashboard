<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RekapPenyakitTopBuilder
{
    private const CHUNK_SIZE = 5000;
    private const UPSERT_BATCH_SIZE = 500;

    public function build(): void
    {
        // 1. Get watermark
        $log = DB::table('rekap_logs')->orderBy('id', 'desc')->first();
        $lastProcessedId = $log ? $log->last_processed_id : 0;

        $maxProcessedId = $lastProcessedId;

        // 2. Chunk processing from history table
        DB::table('history')
            ->where('id', '>', $lastProcessedId)
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($records) use (&$maxProcessedId) {
                
                $this->processChunk($records);

                $lastRecord = $records->last();
                if ($lastRecord) {
                    $maxProcessedId = max($maxProcessedId, $lastRecord->id);
                }
            });

        // 3. Update watermark
        if ($maxProcessedId > $lastProcessedId) {
            DB::table('rekap_logs')->insert([
                'last_processed_id' => $maxProcessedId,
                'last_processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
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

        $aggGlobal = [];
        $aggKecamatan = [];
        $aggPuskesmas = [];

        $mapping = RecapLogicService::getMappingKodeToKecamatan();
        $namaToKodeKecamatan = array_flip(RecapLogicService::MAPPING_NAMA_KECAMATAN);

        $kodePenyakitSet = [];
        $kpuskSet = [];
        foreach ($records as $record) {
            if (!empty($record->kode_penyakit)) {
                $kodePenyakitSet[$record->kode_penyakit] = true;
            }
            if (!empty($record->kpusk)) {
                $kpuskSet[trim((string) $record->kpusk)] = true;
            }
        }

        $icdNames = [];
        if (!empty($kodePenyakitSet)) {
            $icdNames = DB::table('bpjs_ref_icd')
                ->whereIn('kdDiag', array_keys($kodePenyakitSet))
                ->pluck('nmDiag', 'kdDiag')
                ->toArray();
        }

        $puskesmasKecMap = [];
        if (!empty($kpuskSet)) {
            $puskesmasKecMap = DB::table('ref_puskesmas')
                ->whereIn('kode_puskesmas', array_keys($kpuskSet))
                ->pluck('kode_kecamatan', 'kode_puskesmas')
                ->map(fn ($kode) => trim((string) $kode))
                ->toArray();
        }

        foreach ($records as $r) {
            if (!$r->kode_penyakit) {
                continue;
            }

            $kodePenyakit = $r->kode_penyakit;
            $namaPenyakit = $icdNames[$kodePenyakit] ?? $kodePenyakit;
            $dt = $r->tanggal ? Carbon::parse($r->tanggal) : null;
            $year = $dt ? $dt->year : 0;
            $month = $dt ? $dt->month : 0;
            $quarter = $dt ? (int) ceil($dt->month / 3) : 0;
            $semester = $dt ? ($dt->month <= 6 ? 1 : 2) : 0;

            $kpusk = trim((string) ($r->kpusk ?? ''));
            $kecName = $mapping[$kpusk] ?? '';
            $kodeKecamatan = $kecName ? ($namaToKodeKecamatan[$kecName] ?? '') : '';
            if (!$kodeKecamatan && $kpusk) {
                $kodeKecamatan = $puskesmasKecMap[$kpusk] ?? '';
            }

            // GLOBAL
            $this->pushAggregate($aggGlobal, 'global', 'all', 0, 0, 0, 0, '', '', $kodePenyakit, $namaPenyakit);
            if ($year > 0) {
                $this->pushAggregate($aggGlobal, 'global', 'year', $year, 0, 0, 0, '', '', $kodePenyakit, $namaPenyakit);
                $this->pushAggregate($aggGlobal, 'global', 'semester', $year, 0, 0, $semester, '', '', $kodePenyakit, $namaPenyakit);
                $this->pushAggregate($aggGlobal, 'global', 'quarter', $year, 0, $quarter, 0, '', '', $kodePenyakit, $namaPenyakit);
                $this->pushAggregate($aggGlobal, 'global', 'month', $year, $month, 0, 0, '', '', $kodePenyakit, $namaPenyakit);
            }

            // KECAMATAN
            if ($kodeKecamatan) {
                $this->pushAggregate($aggKecamatan, 'kecamatan', 'all', 0, 0, 0, 0, '', $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                if ($year > 0) {
                    $this->pushAggregate($aggKecamatan, 'kecamatan', 'year', $year, 0, 0, 0, '', $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $this->pushAggregate($aggKecamatan, 'kecamatan', 'semester', $year, 0, 0, $semester, '', $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $this->pushAggregate($aggKecamatan, 'kecamatan', 'quarter', $year, 0, $quarter, 0, '', $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $this->pushAggregate($aggKecamatan, 'kecamatan', 'month', $year, $month, 0, 0, '', $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                }
            }

            // PUSKESMAS
            if ($kpusk) {
                $this->pushAggregate($aggPuskesmas, 'puskesmas', 'all', 0, 0, 0, 0, $kpusk, $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                if ($year > 0) {
                    $this->pushAggregate($aggPuskesmas, 'puskesmas', 'year', $year, 0, 0, 0, $kpusk, $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $this->pushAggregate($aggPuskesmas, 'puskesmas', 'semester', $year, 0, 0, $semester, $kpusk, $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $this->pushAggregate($aggPuskesmas, 'puskesmas', 'quarter', $year, 0, $quarter, 0, $kpusk, $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $this->pushAggregate($aggPuskesmas, 'puskesmas', 'month', $year, $month, 0, 0, $kpusk, $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                }
            }
        }

        $upserts = array_merge(array_values($aggGlobal), array_values($aggKecamatan), array_values($aggPuskesmas));
        if (empty($upserts)) {
            return;
        }

        $now = now();
        $upserts = array_map(function (array $item) use ($now) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;

            return $item;
        }, $upserts);

        foreach (array_chunk($upserts, self::UPSERT_BATCH_SIZE) as $batch) {
            $this->upsertAggregateBatch($batch);
        }
    }

    /**
     * Tambahkan hitungan untuk kombinasi agregasi tertentu.
     *
     * @param array<string, array<string, int|string|null>> $aggArray
     */
    private function pushAggregate(
        array &$aggArray,
        string $scope,
        string $periodType,
        int $year,
        int $month,
        int $quarter,
        int $semester,
        string $kpusk,
        string $kodeKecamatan,
        string $kodePenyakit,
        string $namaPenyakit
    ): void {
        $kpusk = trim($kpusk);
        $kodeKecamatan = trim($kodeKecamatan);
        $key = "{$scope}|{$periodType}|{$year}|{$month}|{$quarter}|{$semester}|{$kpusk}|{$kodeKecamatan}|{$kodePenyakit}";

        if (!isset($aggArray[$key])) {
            $aggArray[$key] = [
                'scope' => $scope,
                'period_type' => $periodType,
                'year' => $year,
                'month' => $month,
                'quarter' => $quarter,
                'semester' => $semester,
                'kpusk' => $kpusk,
                'kode_kecamatan' => $kodeKecamatan,
                'kode_penyakit' => $kodePenyakit,
                'nama_penyakit' => $namaPenyakit,
                'jumlah_kasus' => 0,
            ];
        }

        $aggArray[$key]['jumlah_kasus']++;
    }

    /**
     * Lakukan batch upsert menggunakan sintaks ON DUPLICATE KEY UPDATE
     * agar jumlah kasus tetap diakumulasi secara incremental.
     *
     * @param array<int, array<string, int|string|null>> $batch
     */
    private function upsertAggregateBatch(array $batch): void
    {
        $columns = [
            'scope',
            'period_type',
            'year',
            'month',
            'quarter',
            'semester',
            'kpusk',
            'kode_kecamatan',
            'kode_penyakit',
            'nama_penyakit',
            'jumlah_kasus',
            'created_at',
            'updated_at',
        ];

        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $placeholders = implode(', ', array_fill(0, count($batch), $rowPlaceholder));
        $quotedColumns = implode(', ', array_map(fn (string $column) => "`{$column}`", $columns));

        $sql = "INSERT INTO `rekap_penyakit_top` ({$quotedColumns}) VALUES {$placeholders} "
            . "ON DUPLICATE KEY UPDATE "
            . "`nama_penyakit` = VALUES(`nama_penyakit`), "
            . "`jumlah_kasus` = `jumlah_kasus` + VALUES(`jumlah_kasus`), "
            . "`updated_at` = VALUES(`updated_at`)";

        $bindings = [];
        foreach ($batch as $item) {
            foreach ($columns as $column) {
                $bindings[] = $item[$column];
            }
        }

        DB::statement($sql, $bindings);
    }
}
