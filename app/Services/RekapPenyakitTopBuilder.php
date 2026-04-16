<?php

namespace App\Services;

use App\Models\RekamMedis;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RekapPenyakitTopBuilder
{
    private const CHUNK_SIZE = 5000;

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
        $aggGlobal = [];
        $aggKecamatan = [];
        $aggPuskesmas = [];

        $mapping = RecapLogicService::getMappingKodeToKecamatan();
        $mappingKecNameId = RecapLogicService::MAPPING_NAMA_KECAMATAN;

        // Fetch ICD names mapping for the chunk to minimize queries
        $kodePenyakitSet = [];
        foreach ($records as $r) {
            if ($r->kode_penyakit) {
                $kodePenyakitSet[$r->kode_penyakit] = true;
            }
        }
        
        $icdNames = [];
        if (!empty($kodePenyakitSet)) {
            $icdNames = DB::table('bpjs_ref_icd')
                ->whereIn('kdDiag', array_keys($kodePenyakitSet))
                ->pluck('nmDiag', 'kdDiag')
                ->toArray();
        }

        foreach ($records as $r) {
            if (!$r->kode_penyakit) continue;

            $kodePenyakit = $r->kode_penyakit;
            $namaPenyakit = $icdNames[$kodePenyakit] ?? $kodePenyakit;
            
            $dt = $r->tanggal ? Carbon::parse($r->tanggal) : null;
            $year = $dt ? $dt->year : 0;
            $month = $dt ? $dt->month : 0;
            $quarter = $dt ? ceil($dt->month / 3) : 0;
            $semester = $dt ? ($dt->month <= 6 ? 1 : 2) : 0;

            $kpusk = $r->kpusk ?? '';
            $kecName = $mapping[$kpusk] ?? '';
            $kodeKecamatan = $kecName ? (array_search($kecName, $mappingKecNameId) ?: '') : '';
            if (!$kodeKecamatan && $kpusk) { 
               $puskRow = DB::table('ref_puskesmas')->where('kode_puskesmas', $kpusk)->first();
               if ($puskRow && isset($puskRow->kode_kecamatan)) {
                   $kodeKecamatan = $puskRow->kode_kecamatan;
               }
            }

            $pushAgg = function(&$aggArray, $scope, $periodType, $y, $m, $q, $s, $puskesmas, $kec, $icd, $nama) {
                $puskesmas = ltrim(rtrim($puskesmas));
                $kec = ltrim(rtrim($kec));
                $key = "{$scope}|{$periodType}|{$y}|{$m}|{$q}|{$s}|{$puskesmas}|{$kec}|{$icd}";
                
                if (!isset($aggArray[$key])) {
                    $aggArray[$key] = [
                        'scope' => $scope,
                        'period_type' => $periodType,
                        'year' => $y,
                        'month' => $m,
                        'quarter' => $q,
                        'semester' => $s,
                        'kpusk' => $puskesmas,
                        'kode_kecamatan' => $kec,
                        'kode_penyakit' => $icd,
                        'nama_penyakit' => $nama,
                        'jumlah_kasus' => 0,
                    ];
                }
                $aggArray[$key]['jumlah_kasus']++;
            };

            // GLOBAL
            $pushAgg($aggGlobal, 'global', 'all', 0, 0, 0, 0, '', '', $kodePenyakit, $namaPenyakit);
            if ($year > 0) {
                $pushAgg($aggGlobal, 'global', 'year', $year, 0, 0, 0, '', '', $kodePenyakit, $namaPenyakit);
                $pushAgg($aggGlobal, 'global', 'semester', $year, 0, 0, $semester, '', '', $kodePenyakit, $namaPenyakit);
                $pushAgg($aggGlobal, 'global', 'quarter', $year, 0, $quarter, 0, '', '', $kodePenyakit, $namaPenyakit);
                $pushAgg($aggGlobal, 'global', 'month', $year, $month, 0, 0, '', '', $kodePenyakit, $namaPenyakit);
            }

            // KECAMATAN
            if ($kodeKecamatan) {
                $pushAgg($aggKecamatan, 'kecamatan', 'all', 0, 0, 0, 0, '', $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                if ($year > 0) {
                    $pushAgg($aggKecamatan, 'kecamatan', 'year', $year, 0, 0, 0, '', $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $pushAgg($aggKecamatan, 'kecamatan', 'semester', $year, 0, 0, $semester, '', $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $pushAgg($aggKecamatan, 'kecamatan', 'quarter', $year, 0, $quarter, 0, '', $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $pushAgg($aggKecamatan, 'kecamatan', 'month', $year, $month, 0, 0, '', $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                }
            }

            // PUSKESMAS
            if ($kpusk) {
                $pushAgg($aggPuskesmas, 'puskesmas', 'all', 0, 0, 0, 0, $kpusk, $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                if ($year > 0) {
                    $pushAgg($aggPuskesmas, 'puskesmas', 'year', $year, 0, 0, 0, $kpusk, $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $pushAgg($aggPuskesmas, 'puskesmas', 'semester', $year, 0, 0, $semester, $kpusk, $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $pushAgg($aggPuskesmas, 'puskesmas', 'quarter', $year, 0, $quarter, 0, $kpusk, $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                    $pushAgg($aggPuskesmas, 'puskesmas', 'month', $year, $month, 0, 0, $kpusk, $kodeKecamatan, $kodePenyakit, $namaPenyakit);
                }
            }
        }

        $upserts = array_merge(array_values($aggGlobal), array_values($aggKecamatan), array_values($aggPuskesmas));
        
        // Execute manual upsert loop to prevent complex SQL syntax issues
        // and because unique records per batch is usually small (e.g. 200).
        foreach ($upserts as $item) {
            $existing = DB::table('rekap_penyakit_top')
                ->where('scope', $item['scope'])
                ->where('period_type', $item['period_type'])
                ->where('year', $item['year'])
                ->where('month', $item['month'])
                ->where('quarter', $item['quarter'])
                ->where('semester', $item['semester'])
                ->where('kpusk', $item['kpusk'])
                ->where('kode_kecamatan', $item['kode_kecamatan'])
                ->where('kode_penyakit', $item['kode_penyakit'])
                ->first();

            if ($existing) {
                DB::table('rekap_penyakit_top')
                    ->where('id', $existing->id)
                    ->update([
                        'jumlah_kasus' => $existing->jumlah_kasus + $item['jumlah_kasus'],
                        'updated_at' => now()
                    ]);
            } else {
                $item['created_at'] = now();
                $item['updated_at'] = now();
                $item['total_kasus'] = 0; // Legacy unused column
                DB::table('rekap_penyakit_top')->insert($item);
            }
        }
    }
}

