<?php

use App\Services\RekapPenyakitTopBuilder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('recap-top:build {--reset}', function (RekapPenyakitTopBuilder $builder) {
    if ($this->option('reset')) {
        $this->info('Mengosongkan tabel rekapitulasi...');
        Illuminate\Support\Facades\DB::table('rekap_penyakit_top')->truncate();
        Illuminate\Support\Facades\DB::table('rekap_logs')->where('job_name', 'recap-top-build')->delete();
        $this->info('Tabel rekapitulasi berhasil dikosongkan.');
    }
    $builder->build();
    $this->info('Rekap penyakit top-N berhasil dibuat ulang.');
})->purpose('Membangun ulang tabel rekap penyakit top-N.');

Schedule::command('recap-top:build')->dailyAt('11:00')->withoutOverlapping();
