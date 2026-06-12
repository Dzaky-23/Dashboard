<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapPenyakitController extends Controller
{
    public function rekapByTanggal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mulai' => ['required', 'date_format:d/m/y'],
            'sampai' => ['required', 'date_format:d/m/y'],
        ]);

        $mulai = Carbon::createFromFormat('d/m/y', $validated['mulai'])->startOfDay();
        $sampai = Carbon::createFromFormat('d/m/y', $validated['sampai'])->endOfDay();

        if ($mulai->gt($sampai)) {
            return response()->json([
                'status' => false,
                'status_code' => 201,
                'message' => 'Tanggal mulai tidak boleh lebih besar dari tanggal sampai.',
                'data' => [],
            ], 201);
        }

        $data = DB::table('lb1_penta as h')
            ->leftJoin('bpjs_ref_icd as icd', 'h.diagnosa', '=', 'icd.kdDiag')
            ->whereNotNull('h.tanggal')
            ->whereBetween('h.tanggal', [$mulai->toDateString(), $sampai->toDateString()])
            ->where('h.diagnosa', '<>', '')
            ->whereNotNull('h.diagnosa')
            ->selectRaw("
                h.diagnosa as kode_penyakit,
                COALESCE(NULLIF(icd.nmDiag, ''), h.diagnosa) as nama_penyakit,
                COUNT(*) as jumlah_kasus
            ")
            ->groupBy(
                'h.diagnosa',
                DB::raw("COALESCE(NULLIF(icd.nmDiag, ''), h.diagnosa)")
            )
            ->orderByDesc('jumlah_kasus')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'status_code' => 201,
            'message' => 'Rekap 10 penyakit berhasil diambil.',
            'data' => $data,
        ], 201);
    }
}
