<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\PasienController as ApiPasienController;
use App\Http\Controllers\Api\RekamMedisController as ApiRekamMedisController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// This was added from default Laravel setup, but the old Dashboard is what the user was using.
Route::get('/', [DashboardController::class, 'index'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('admin.dashboard');
    })->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return view('dashboard', [
                'title' => 'Admin Dashboard',
                'message' => 'Selamat datang, admin.',
            ]);
        })->name('admin.dashboard');

        Route::prefix('/admin/api')->group(function () {
            Route::apiResource('pasiens', ApiPasienController::class);
            Route::apiResource('rekam-medis', ApiRekamMedisController::class);
        });
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Original Web Routes
Route::get('pasiens/{pasien}/rekam-medis', [PasienController::class, 'rekamMedis'])->name('pasiens.rekam_medis');
Route::resource('pasiens', PasienController::class);

require __DIR__.'/auth.php';
