<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\PasienController as ApiPasienController;
use App\Http\Controllers\Api\RekamMedisController as ApiRekamMedisController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PenyakitRecapController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/', function () {
        return redirect()->route('recap.index');
    })->name('home');

    // Route::get('/dashboard', function () {
    //     return redirect()->route('home');
    // })->name('dashboard');

    // Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Route::name('admin.api.')->prefix('/admin/api')->group(function () {
    //     Route::apiResource('pasiens', ApiPasienController::class);
    //     Route::apiResource('rekam-medis', ApiRekamMedisController::class);
    // });

    // Route::get('pasiens/{pasien}/rekam-medis', [PasienController::class, 'rekamMedis'])->name('pasiens.rekam_medis');
    // Route::get('pasiens/{pasien}/rekam-medis/{rekam_medis}', [PasienController::class, 'rekamMedisDetail'])->name('pasiens.rekam_medis.show');
    // Route::resource('pasiens', PasienController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    
    Route::get('/recap-penyakit', [PenyakitRecapController::class, 'index'])->name('recap.index');
    Route::get('/recap-penyakit/export', [PenyakitRecapController::class, 'export'])->name('recap.export');
    Route::get('/recap-penyakit/kecamatan/{kecamatan}', [PenyakitRecapController::class, 'showKecamatan'])->name('recap.kecamatan.show');
    Route::get('/recap-penyakit/{puskesmas}', [PenyakitRecapController::class, 'show'])->name('recap.show');
    
    // Rute Tabel Daftar Penyakit (Full Page & Filter)
    Route::get('/recap-penyakit/kecamatan/{kecamatan}/full-list', [PenyakitRecapController::class, 'fullListKecamatan'])->name('recap.kecamatan.full_list');
    Route::get('/recap-penyakit/{puskesmas}/full-list', [PenyakitRecapController::class, 'fullList'])->name('recap.full_list');
});

require __DIR__.'/auth.php';
