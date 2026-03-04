<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PasienController;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('pasiens/{pasien}/rekam-medis', [PasienController::class, 'rekamMedis'])->name('pasiens.rekam_medis');
Route::resource('pasiens', PasienController::class);
