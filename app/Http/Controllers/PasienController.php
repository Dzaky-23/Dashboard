<?php

namespace App\Http\Controllers;

use App\Models\Pasien;
use Illuminate\Http\Request;

class PasienController extends Controller
{
    public function index()
    {
        $pasiens = Pasien::orderBy('submited_at', 'desc')->paginate(10);
        return view('pasiens.index', compact('pasiens'));
    }

    public function create()
    {
        return view('pasiens.create');
    }

    public function show(Pasien $pasien)
    {
        return view('pasiens.show', compact('pasien'));
    }

    public function rekamMedis(Pasien $pasien)
    {
        return view('pasiens.rekam_medis', compact('pasien'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_reg' => 'required|string',
            'nama' => 'required|string',
            'nik' => 'nullable|string',
            'tanggal' => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        Pasien::create($request->all());

        return redirect()->route('pasiens.index')->with('success', 'Data Pasien berhasil ditambahkan.');
    }
}
