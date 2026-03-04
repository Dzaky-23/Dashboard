<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\PasienController;
use App\Http\Controllers\Api\RekamMedisController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
            Route::apiResource('pasiens', PasienController::class);
            Route::apiResource('rekam-medis', RekamMedisController::class);
        });
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
