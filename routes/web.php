<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\PasienController as ApiPasienController;
use App\Http\Controllers\Api\RekamMedisController as ApiRekamMedisController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');

    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::name('admin.api.')->prefix('/admin/api')->group(function () {
        Route::apiResource('pasiens', ApiPasienController::class);
        Route::apiResource('rekam-medis', ApiRekamMedisController::class);
    });

    Route::get('pasiens/{pasien}/rekam-medis', [PasienController::class, 'rekamMedis'])->name('pasiens.rekam_medis');
    Route::resource('pasiens', PasienController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
