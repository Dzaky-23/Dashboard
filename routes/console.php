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
    ->dailyAt('09:11')
    ->onFailure(function () {
        Illuminate\Support\Facades\Log::error('Fallback cron rekap:aggregate failed.');
    });

Schedule::call(function () {
    $directory = storage_path('app/exports');
    if (file_exists($directory)) {
        $files = Illuminate\Support\Facades\File::files($directory);
        $now = time();
        foreach ($files as $file) {
            if ($now - $file->getMTime() > 86400) {
                $filename = $file->getFilename();
                Illuminate\Support\Facades\File::delete($file->getPathname());
                App\Models\JobStatus::where('output_path', $filename)
                    ->update(['status' => 'expired']);
            }
        }
    }
})->dailyAt('08:15');

