<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Lb1PentaLargeSeeder extends Seeder
{
    private const DEFAULT_TOTAL = 1_000_000;
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

        $startDate = Carbon::create(2024, 1, 1);
        $endDate = Carbon::create(2026, 5, 31);
        $daysRange = (int) $startDate->diffInDays($endDate);
        $inserted = 0;

        while ($inserted < $total) {
            $limit = min($chunkSize, $total - $inserted);
            $rows = [];
            $now = now();

            for ($i = 0; $i < $limit; $i++) {
                $sequence = $inserted + $i + 1;
                $tanggal = $startDate->copy()->addDays(random_int(0, $daysRange));

                $rows[] = [
                    'tanggal' => $tanggal->toDateString(),
                    'nik' => $this->generateNik($sequence),
                    'kpusk' => $kpuskCodes[array_rand($kpuskCodes)],
                    'no_reg' => $this->generateNoReg($tanggal, $sequence),
                    'diagnosa' => $icdCodes[array_rand($icdCodes)],
                    'status' => $this->generateStatus(),
                    'kdesa' => $kdesaCodes[array_rand($kdesaCodes)],
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

    private function generateNik(int $sequence): string
    {
        return str_pad((string) $sequence, 16, '0', STR_PAD_LEFT);
    }

    private function generateNoReg(Carbon $tanggal, int $sequence): string
    {
        return $tanggal->format('Ymd') . str_pad((string) $sequence, 10, '0', STR_PAD_LEFT);
    }

    private function generateStatus(): string
    {
        return random_int(1, 100) <= 70 ? 'Baru' : 'Lama';
    }
}
