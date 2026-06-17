<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PenyakitRecapController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('recap.index');
    })->name('home');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    
    Route::get('/recap-penyakit', [PenyakitRecapController::class, 'index'])->name('recap.index');
    Route::get('/recap-penyakit/export', [PenyakitRecapController::class, 'export'])->name('recap.export');
    Route::get('/recap-penyakit/icd/search', [PenyakitRecapController::class, 'searchIcd'])->name('recap.icd.search');
    Route::get('/recap-penyakit/api/trend-data', [PenyakitRecapController::class, 'trendChartData'])->name('recap.api.trend_data');
    Route::get('/recap-penyakit/api/pie-data', [PenyakitRecapController::class, 'pieChartData'])->name('recap.api.pie_data');
    Route::get('/recap-penyakit/kecamatan/{kecamatan}', [PenyakitRecapController::class, 'showKecamatan'])->name('recap.kecamatan.show');
    Route::get('/recap-penyakit/{puskesmas}', [PenyakitRecapController::class, 'show'])->name('recap.show');
    
    // Rute Agregasi Manual (Mekanisme 1)
    Route::post('/rekap/aggregate', [\App\Http\Controllers\RekapController::class, 'dispatch'])->name('recap.aggregate.dispatch');
    Route::get('/rekap/aggregate/status/{jobId}', [\App\Http\Controllers\RekapController::class, 'checkStatus'])->name('recap.aggregate.status');

    // Rute Ekspor Asinkron (Mekanisme 5)
    Route::post('/rekap/export/dispatch', [\App\Http\Controllers\ExportController::class, 'dispatch'])->name('recap.export.dispatch');
    Route::get('/rekap/export/status/{jobId}', [\App\Http\Controllers\ExportController::class, 'checkStatus'])->name('recap.export.status');
    Route::get('/rekap/export/download/{jobId}', [\App\Http\Controllers\ExportController::class, 'download'])->name('recap.export.download');
    
    // Rute Tabel Daftar Penyakit (Full Page & Filter)
    Route::get('/recap-penyakit/kecamatan/{kecamatan}/full-list', [PenyakitRecapController::class, 'fullListKecamatan'])->name('recap.kecamatan.full_list');
    Route::get('/recap-penyakit/{puskesmas}/full-list', [PenyakitRecapController::class, 'fullList'])->name('recap.full_list');
});

require __DIR__.'/auth.php';
