<?php

use App\Services\RekapPenyakitTopBuilder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('recap-top:build {--reset}', function () {
    $this->info('Command lama tidak digunakan. Gunakan rekap:aggregate.');
})->purpose('Membangun ulang tabel rekap (deprecated).');

Schedule::command('rekap:aggregate')
    // ->cron('0 2 6 * *')
    ->dailyAt('10:00')
    ->onFailure(function () {
        Illuminate\Support\Facades\Log::error('Fallback cron rekap:aggregate failed.');
    });
