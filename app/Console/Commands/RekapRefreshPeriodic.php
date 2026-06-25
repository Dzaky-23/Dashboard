<?php

namespace App\Console\Commands;

use App\Models\JobStatus;
use App\Services\RekapHarianService;
use App\Services\RekapPeriodikService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RekapRefreshPeriodic extends Command
{
    protected $signature = 'rekap:refresh-periodic
        {--bulan= : Refresh satu bulan spesifik, format YYYY-MM}
        {--from= : Bulan mulai, format YYYY-MM}
        {--to= : Bulan selesai, format YYYY-MM}
        {--all : Refresh semua data berdasarkan min/max tanggal lb1_penta}';

    protected $description = 'Refresh pipeline rekap bertingkat (harian -> bulanan -> tahunan)';

    public function handle(RekapHarianService $harianService, RekapPeriodikService $periodicService)
    {
        $bulanOpt = $this->option('bulan');
        $fromOpt = $this->option('from');
        $toOpt = $this->option('to');
        $allOpt = $this->option('all');

        // 1. Buat JobStatus record
        $jobStatus = JobStatus::create([
            'type' => 'periodic_refresh',
            'status' => 'processing',
            'payload' => [
                'bulan' => $bulanOpt,
                'from' => $fromOpt,
                'to' => $toOpt,
                'all' => $allOpt,
            ],
        ]);

        try {
            $months = [];

            if ($allOpt) {
                $minDate = DB::table('lb1_penta')->min('tanggal');
                $maxDate = DB::table('lb1_penta')->max('tanggal');

                if (!$minDate || !$maxDate) {
                    $this->info('Tidak ada data di tabel lb1_penta.');
                    $jobStatus->update(['status' => 'done']);
                    return 0;
                }

                $current = Carbon::parse($minDate)->startOfMonth();
                $end = Carbon::parse($maxDate)->startOfMonth();
                while ($current->lte($end)) {
                    $months[] = $current->copy();
                    $current->addMonth();
                }
            } elseif ($fromOpt || $toOpt) {
                if (!$fromOpt || !$toOpt) {
                    throw new \Exception('Kedua parameter --from dan --to harus diisi bersamaan.');
                }
                $current = Carbon::parse($fromOpt . '-01')->startOfMonth();
                $end = Carbon::parse($toOpt . '-01')->startOfMonth();
                if ($current->gt($end)) {
                    throw new \Exception('Bulan --from tidak boleh lebih besar dari --to.');
                }
                while ($current->lte($end)) {
                    $months[] = $current->copy();
                    $current->addMonth();
                }
            } elseif ($bulanOpt) {
                $months[] = Carbon::parse($bulanOpt . '-01')->startOfMonth();
            } else {
                // Default: bulan sebelumnya
                $months[] = Carbon::now()->subMonth()->startOfMonth();
            }

            $yearsAffected = [];
            $logMsg = "";

            foreach ($months as $month) {
                $monthStr = $month->format('Y-m');

                // Step 1: Harian
                $msgStartHarian = "START harian {$monthStr}";
                $this->info($msgStartHarian);
                $logMsg .= $msgStartHarian . "\n";
                $harianService->aggregateByMonth($month);
                $msgDoneHarian = "DONE harian {$monthStr}";
                $this->info($msgDoneHarian);
                $logMsg .= $msgDoneHarian . "\n";

                // Step 2: Bulanan
                $msgStartBulanan = "START bulanan {$monthStr}";
                $this->info($msgStartBulanan);
                $logMsg .= $msgStartBulanan . "\n";
                $periodicService->aggregateMonthlyByMonth($month);
                $msgDoneBulanan = "DONE bulanan {$monthStr}";
                $this->info($msgDoneBulanan);
                $logMsg .= $msgDoneBulanan . "\n";

                $yearsAffected[$month->year] = true;
            }

            // Step 3: Tahunan
            foreach (array_keys($yearsAffected) as $year) {
                $msgStartTahunan = "START tahunan {$year}";
                $this->info($msgStartTahunan);
                $logMsg .= $msgStartTahunan . "\n";
                $periodicService->aggregateYearlyByYear($year);
                $msgDoneTahunan = "DONE tahunan {$year}";
                $this->info($msgDoneTahunan);
                $logMsg .= $msgDoneTahunan . "\n";
            }

            // Step 4: Invalidate Cache
            $periodicService->invalidateCache();

            $jobStatus->update([
                'status' => 'done',
                'output_path' => $logMsg,
            ]);

            $this->info('Pipeline refresh periodik selesai dengan sukses.');
            return 0;

        } catch (\Exception $e) {
            $errorMsg = "Gagal pada pipeline refresh: " . $e->getMessage() . "\n" . $e->getTraceAsString();
            $this->error($errorMsg);
            Log::error($errorMsg);

            $jobStatus->update([
                'status' => 'failed',
                'error' => $errorMsg,
            ]);

            return 1;
        }
    }
}
