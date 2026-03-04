<?php

namespace App\Http\Controllers;

use App\Models\Pasien;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPasien = Pasien::count();
        $pasienBaruToday = Pasien::whereDate('submited_at', today())->count();
        $totalBPJS = Pasien::where('cara_bayar', 'BPJS')->count();
        $totalUmum = Pasien::where('cara_bayar', 'Umum')->count();

        return view('dashboard', compact('totalPasien', 'pasienBaruToday', 'totalBPJS', 'totalUmum'));
    }
}
