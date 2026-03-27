<?php

namespace App\Http\Controllers;

use App\Models\Pasien;
use Illuminate\Http\Request;

class PasienController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $yearInput = $request->input('year', date('Y'));
        
        $startDate = \Carbon\Carbon::create($yearInput)->startOfYear();
        $endDate = \Carbon\Carbon::create($yearInput)->endOfYear();

        $pasiens = Pasien::when($search, function($query, $search) {
            return $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        })
        ->whereBetween('submited_at', [$startDate, $endDate])
        ->orderBy('submited_at', 'desc')
        ->paginate(10);
        
        $availableYears = range(date('Y'), 2024);
        
        return view('pasiens.index', compact('pasiens', 'search', 'yearInput', 'availableYears'));
    }

    public function create()
    {
        return view('pasiens.create');
    }

    public function show(Pasien $pasien)
    {
        return view('pasiens.show', compact('pasien'));
    }

    public function rekamMedis(Pasien $pasien, Request $request)
    {
        $searchDate = $request->input('search_date');
        
        // Get rekam medis ordered by tanggal descending with optional date filter
        $rekamMedis = $pasien->rekamMedis()
            ->when($searchDate, function($query, $searchDate) {
                return $query->whereDate('tanggal', $searchDate);
            })
            ->orderBy('tanggal', 'desc')->get();
            
        return view('pasiens.rekam_medis', compact('pasien', 'rekamMedis', 'searchDate'));
    }

    public function rekamMedisDetail(Pasien $pasien, $rekam_medis_id)
    {
        $rm = \App\Models\RekamMedis::where('id', $rekam_medis_id)->where('no_reg', $pasien->no_reg)->firstOrFail();
        return view('pasiens.rekam_medis_detail', compact('pasien', 'rm'));
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
