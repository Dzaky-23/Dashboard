<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Lb1PentaLargeSeeder extends Seeder
{
    private const DEFAULT_TOTAL = 500_000;
    private const DEFAULT_CHUNK_SIZE = 1_000;
    private const MAX_MEMORY_SAFE_CHUNK_SIZE = 1_000;
    private const INSERT_COLUMNS = 9;
    private const MAX_QUERY_PLACEHOLDERS = 65_000;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::connection()->disableQueryLog();

        $total = max(1, (int) env('LB1_PENTA_DUMMY_TOTAL', self::DEFAULT_TOTAL));
        $requestedChunkSize = max(1, (int) env('LB1_PENTA_DUMMY_CHUNK', self::DEFAULT_CHUNK_SIZE));
        $maxSafeChunkSize = intdiv(self::MAX_QUERY_PLACEHOLDERS, self::INSERT_COLUMNS);
        $chunkSize = min($requestedChunkSize, $maxSafeChunkSize, self::MAX_MEMORY_SAFE_CHUNK_SIZE);

        $kpuskCodes = DB::table('puskesmas')
            ->pluck('kode_p')
            ->filter()
            ->values()
            ->all();

        $icdCodes = DB::table('bpjs_ref_icd')
            ->whereNotNull('kdDiag')
            ->where('kdDiag', '!=', '')
            ->limit(1_000)
            ->pluck('kdDiag')
            ->filter()
            ->values()
            ->all();

        $kdesaCodes = DB::table('desa')
            ->pluck('kode')
            ->filter()
            ->values()
            ->all();

        if (empty($kpuskCodes) || empty($icdCodes) || empty($kdesaCodes)) {
            throw new RuntimeException('Data master puskesmas, ICD, atau desa masih kosong.');
        }

        $startDate = Carbon::create(2026, 5, 1);
        $endDate = Carbon::create(2026, 5, 31);
        $daysRange = (int) $startDate->diffInDays($endDate);
        $inserted = 0;

        while ($inserted < $total) {
            $limit = min($chunkSize, $total - $inserted);
            $rows = [];
            $now = now();

            for ($i = 0; $i < $limit; $i++) {
                $sequence = $inserted + $i + 1;
                
                // Randomly generate tanggal inconsistency
                $randTanggalType = random_int(1, 100);
                $tanggal = null;
                $tanggalStr = null;
                if ($randTanggalType > 2) { // 2% null
                    if ($randTanggalType <= 4) { // 2% before 2010
                        $tanggal = Carbon::create(random_int(2000, 2009), random_int(1, 12), random_int(1, 28));
                    } else {
                        $tanggal = $startDate->copy()->addDays(random_int(0, $daysRange));
                    }
                    $tanggalStr = $tanggal->toDateString();
                }

                // Randomly generate NIK inconsistency
                $randNikType = random_int(1, 100);
                $nik = $this->generateNik($sequence, $randNikType);

                // Randomly generate kdesa inconsistency
                $randKdesaType = random_int(1, 100);
                $kdesa = null;
                if ($randKdesaType > 2) { // 2% null
                    if ($randKdesaType <= 4) { // 2% empty string
                        $kdesa = '';
                    } elseif ($randKdesaType <= 7) { // 3% untrimmed
                        $kdesa = ' ' . $kdesaCodes[array_rand($kdesaCodes)] . ' ';
                    } else {
                        $kdesa = $kdesaCodes[array_rand($kdesaCodes)];
                    }
                }

                // Randomly untrim kpusk & diagnosa
                $kpusk = $kpuskCodes[array_rand($kpuskCodes)];
                if (random_int(1, 100) <= 5) {
                    $kpusk = ' ' . $kpusk . ' ';
                }

                $diagnosa = $icdCodes[array_rand($icdCodes)];
                if (random_int(1, 100) <= 5) {
                    $diagnosa = ' ' . $diagnosa . ' ';
                }

                $rows[] = [
                    'tanggal' => $tanggalStr,
                    'nik' => $nik,
                    'kpusk' => $kpusk,
                    'no_reg' => $this->generateNoReg($tanggal, $sequence),
                    'diagnosa' => $diagnosa,
                    'status' => $this->generateStatus(),
                    'kdesa' => $kdesa,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('lb1_penta')->insert($rows);
            unset($rows);

            $inserted += $limit;
            $this->command?->info("Inserted {$inserted} / {$total}");
        }
    }

    private function generateNik(int $sequence, int $randType): ?string
    {
        if ($randType <= 2) {
            // Null NIK (Invalid)
            return null;
        }
        if ($randType <= 4) {
            // Short NIK - 15 digits (Invalid)
            return str_pad((string) $sequence, 15, '0', STR_PAD_LEFT);
        }
        if ($randType <= 6) {
            // Long NIK - 17 digits (Invalid)
            return str_pad((string) $sequence, 17, '0', STR_PAD_LEFT);
        }
        if ($randType <= 10) {
            // Valid NIK but with trailing spaces (Valid after trim)
            return ' ' . str_pad((string) $sequence, 16, '0', STR_PAD_LEFT) . ' ';
        }
        // Fully valid NIK
        return str_pad((string) $sequence, 16, '0', STR_PAD_LEFT);
    }

    private function generateNoReg(?Carbon $tanggal, int $sequence): string
    {
        $prefix = $tanggal ? $tanggal->format('Ymd') : '20260101';
        return $prefix . str_pad((string) $sequence, 10, '0', STR_PAD_LEFT);
    }

    private function generateStatus(): string
    {
        return random_int(1, 100) <= 70 ? 'Baru' : 'Lama';
    }
}
