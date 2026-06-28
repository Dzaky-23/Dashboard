<?php

namespace App\Console\Commands;

use App\Services\RekapHarianService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RekapAggregate extends Command
{
    protected $signature = 'rekap:aggregate
                            {--bulan= : Agregasi satu bulan spesifik (Format: YYYY-MM)}
                            {--from= : Tanggal mulai untuk backfill (Format: YYYY-MM-DD)}
                            {--to= : Tanggal selesai untuk backfill (Format: YYYY-MM-DD)}
                            {--all : Agregasi semua data yang tersedia di lb1_penta}';

    protected $description = 'Melakukan agregasi data raw lb1_penta ke rekap_harian';

    public function handle(RekapHarianService $service)
    {
        DB::connection()->disableQueryLog();

        try {
            $bulanOpt = $this->option('bulan');
            $fromOpt = $this->option('from');
            $toOpt = $this->option('to');
            $allOpt = $this->option('all');

            // Run data cleaning first
            if ($allOpt) {
                $this->info('Running full data cleaning...');
                $this->call('rekap:clean-penta', ['--all' => true]);
            } else {
                $this->info('Running incremental data cleaning...');
                $this->call('rekap:clean-penta');
            }

            if ($allOpt) {
                $minDateStr = \Illuminate\Support\Facades\DB::table('lb1_penta_clean')->min('tanggal');
                $maxDateStr = \Illuminate\Support\Facades\DB::table('lb1_penta_clean')->max('tanggal');

                if (!$minDateStr || !$maxDateStr) {
                    $this->info('Tidak ada data di tabel lb1_penta_clean.');
                    return 0;
                }

                $from = Carbon::parse($minDateStr);
                $to = Carbon::parse($maxDateStr);

                $current = $from->copy()->startOfMonth();
                $end = $to->copy()->startOfMonth();
                $months = [];
                while ($current->lte($end)) {
                    $months[] = $current->copy();
                    $current->addMonth();
                }

                $this->info("Memulai agregasi semua data dari {$minDateStr} sampai {$maxDateStr} (" . count($months) . " bulan)...");

                $bar = $this->output->createProgressBar(count($months));
                $bar->start();

                foreach ($months as $month) {
                    $service->aggregateByMonth($month);
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
                $this->info("Agregasi semua data selesai.");
                Log::info("Agregasi semua data sukses dari {$minDateStr} ke {$maxDateStr}.");
                return 0;
            }

            if ($fromOpt || $toOpt) {
                if (!$fromOpt || !$toOpt) {
                    $this->error('Kedua parameter --from dan --to harus diisi secara bersamaan.');
                    return 1;
                }

                $from = Carbon::parse($fromOpt);
                $to = Carbon::parse($toOpt);

                if ($from->gt($to)) {
                    $this->error('Tanggal --from tidak boleh lebih besar dari --to.');
                    return 1;
                }

                $current = $from->copy()->startOfMonth();
                $end = $to->copy()->startOfMonth();
                $months = [];
                while ($current->lte($end)) {
                    $months[] = $current->copy();
                    $current->addMonth();
                }

                $this->info("Memulai backfill data dari {$fromOpt} sampai {$toOpt} (" . count($months) . " bulan)...");

                $bar = $this->output->createProgressBar(count($months));
                $bar->start();

                foreach ($months as $month) {
                    $service->aggregateByMonth($month);
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
                $this->info("Backfill data selesai.");
                Log::info("Agregasi/Backfill sukses dari {$fromOpt} ke {$toOpt}.");
                return 0;
            }

            if ($bulanOpt) {
                $month = Carbon::parse($bulanOpt . '-01');
                $this->info("Mengagregasi data untuk bulan " . $month->format('Y-m') . "...");
                $service->aggregateByMonth($month);
                $this->info("Agregasi bulan selesai.");
                Log::info("Agregasi sukses untuk bulan " . $month->format('Y-m') . ".");
                return 0;
            }

            // Default: bulan sebelumnya
            $month = Carbon::now()->subMonth();
            $this->info("Mengagregasi data default (bulan sebelumnya): " . $month->format('Y-m') . "...");
            $service->aggregateByMonth($month);
            $this->info("Agregasi default selesai.");
            Log::info("Agregasi default sukses untuk bulan " . $month->format('Y-m') . ".");
            return 0;

        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan saat agregasi: ' . $e->getMessage());
            Log::error('Agregasi gagal: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return 1;
        }
    }
}
