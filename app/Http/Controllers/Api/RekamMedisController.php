<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RekamMedis;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekamMedisController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);
        $q = $request->string('q')->toString();

        $query = RekamMedis::query()
            ->select([
                'id',
                'tanggal',
                'kpusk',
                'no_reg',
                'kode_penyakit',
                'status',
                'unit',
                'diisi_pada',
            ])
            ->latest('id');

        if ($q !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('no_reg', 'like', "%{$q}%")
                    ->orWhere('kpusk', 'like', "%{$q}%")
                    ->orWhere('kode_penyakit', 'like', "%{$q}%");
            });
        }

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $rekamMedis = RekamMedis::create($this->validatePayload($request));

        return response()->json([
            'message' => 'Data rekam medis berhasil dibuat.',
            'data' => $rekamMedis,
        ], 201);
    }

    public function show(RekamMedis $rekamMedi): JsonResponse
    {
        return response()->json([
            'data' => $rekamMedi,
        ]);
    }

    public function update(Request $request, RekamMedis $rekamMedi): JsonResponse
    {
        $rekamMedi->update($this->validatePayload($request));

        return response()->json([
            'message' => 'Data rekam medis berhasil diperbarui.',
            'data' => $rekamMedi->fresh(),
        ]);
    }

    public function destroy(RekamMedis $rekamMedi): JsonResponse
    {
        $rekamMedi->delete();

        return response()->json([
            'message' => 'Data rekam medis berhasil dihapus.',
        ]);
    }

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

        $data = DB::table('history as h')
            ->leftJoin('bpjs_ref_icd as icd', DB::raw('TRIM(h.kode_penyakit)'), '=', DB::raw('TRIM(icd.kdDiag)'))
            ->whereNotNull('h.tanggal')
            ->whereBetween('h.tanggal', [$mulai->toDateString(), $sampai->toDateString()])
            ->whereRaw("TRIM(COALESCE(h.kode_penyakit, '')) <> ''")
            ->selectRaw("
                TRIM(h.kode_penyakit) as kode_penyakit,
                COALESCE(NULLIF(icd.nmDiag, ''), TRIM(h.kode_penyakit)) as nama_penyakit,
                COUNT(*) as jumlah_kasus
            ")
            ->groupByRaw("
                TRIM(h.kode_penyakit),
                COALESCE(NULLIF(icd.nmDiag, ''), TRIM(h.kode_penyakit))
            ")
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

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'tanggal' => ['nullable', 'date'],
            'kpusk' => ['nullable', 'string', 'max:255'],
            'no_reg' => ['nullable', 'string', 'max:255'],
            'kdSadar' => ['nullable', 'string', 'max:255'],
            'alergiMakan' => ['nullable', 'string', 'max:255'],
            'alergiUdara' => ['nullable', 'string', 'max:255'],
            'alergiObat' => ['nullable', 'string', 'max:255'],
            'alergiMakananSS' => ['nullable', 'string', 'max:255'],
            'alergiLingkunganSS' => ['nullable', 'string', 'max:255'],
            'alergiObatSS' => ['nullable', 'string', 'max:255'],
            'kdPrognosa' => ['nullable', 'string', 'max:255'],
            'respRate' => ['nullable', 'string', 'max:255'],
            'heartRate' => ['nullable', 'string', 'max:255'],
            'suhu' => ['nullable', 'string', 'max:255'],
            'bb' => ['nullable', 'string', 'max:255'],
            'tb' => ['nullable', 'string', 'max:255'],
            'sistole' => ['nullable', 'string', 'max:255'],
            'diastole' => ['nullable', 'string', 'max:255'],
            'lingkarPerut' => ['nullable', 'integer', 'min:0'],
            'anamnesa' => ['nullable', 'string'],
            'fisik' => ['nullable', 'string'],
            'kode_penyakit' => ['nullable', 'string', 'max:255', 'exists:bpjs_ref_icd,kdDiag'],
            'status' => ['nullable', 'string', 'max:255'],
            'kode_obat' => ['nullable', 'string'],
            'jumlah' => ['nullable', 'string', 'max:255'],
            'dosis' => ['nullable', 'string', 'max:255'],
            'racikan' => ['nullable', 'string', 'max:255'],
            'kode_tindakan' => ['nullable', 'string', 'max:255'],
            'kode_tindakan_icd' => ['nullable', 'string', 'max:255'],
            'edukasi' => ['nullable', 'string'],
            'jenis_perawatan' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
            'rujukan' => ['nullable', 'string', 'max:255'],
            'poli_rs' => ['nullable', 'string'],
            'cara_bayar' => ['nullable', 'string', 'max:255'],
            'kode_pemeriksa' => ['nullable', 'string', 'max:255'],
            'diisi_pada' => ['nullable', 'date'],
            'rekomendasi_diet' => ['nullable', 'string'],
        ]);
    }
}
