<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RekapCleanPenta extends Command
{
    protected $signature = 'rekap:clean-penta {--all : Re-clean all records from scratch}';
    protected $description = 'Clean and migrate filtered records from lb1_penta to lb1_penta_clean';

    public function handle(): int
    {
        $this->info('Starting data cleaning process (lb1_penta -> lb1_penta_clean)...');

        // Disable query log to prevent memory leaks during large dataset insertion
        DB::connection()->disableQueryLog();

        try {
            $query = DB::table('lb1_penta')
                ->whereNotNull('tanggal')
                ->where('tanggal', '>=', '2010-01-01')
                ->whereNotNull('kdesa')
                ->where('kdesa', '<>', '')
                ->whereRaw('LENGTH(TRIM(nik)) = 16');

            if (!$this->option('all')) {
                $lastCleanId = DB::table('lb1_penta_clean')->max('id_lb1') ?: 0;
                $query->where('id_lb1', '>', $lastCleanId);
            } else {
                // Truncate first if performing full clean
                DB::table('lb1_penta_clean')->truncate();
            }

            $query->chunkById(2000, function ($rows) {
                $data = $rows->map(function ($row) {
                    return [
                        'id_lb1' => $row->id_lb1,
                        'tanggal' => $row->tanggal,
                        'nik' => trim($row->nik),
                        'kpusk' => $row->kpusk ? trim($row->kpusk) : null,
                        'no_reg' => $row->no_reg ? trim($row->no_reg) : null,
                        'diagnosa' => $row->diagnosa ? trim($row->diagnosa) : null,
                        'status' => $row->status ? trim($row->status) : 'Baru',
                        'kdesa' => trim($row->kdesa),
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ];
                })->toArray();

                DB::table('lb1_penta_clean')->insertOrIgnore($data);
            }, 'id_lb1');

            $this->info('Data cleaning completed successfully.');
            return 0;
        } catch (\Exception $e) {
            Log::error('Data cleaning failed: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
