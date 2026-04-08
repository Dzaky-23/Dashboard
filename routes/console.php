<?php

use App\Services\RekapPenyakitTopBuilder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('recap-top:build', function (RekapPenyakitTopBuilder $builder) {
    $builder->build();
    $this->info('Rekap penyakit top-N berhasil dibuat ulang.');
})->purpose('Membangun ulang tabel rekap penyakit top-N.');

Schedule::command('recap-top:build')->dailyAt('10:23');
