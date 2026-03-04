<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PasienController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);
        $q = $request->string('q')->toString();

        $query = Pasien::query()->latest('id');

        if ($q !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('no_reg', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%");
            });
        }

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $pasien = Pasien::create($this->validatePayload($request));

        return response()->json([
            'message' => 'Data pasien berhasil dibuat.',
            'data' => $pasien,
        ], 201);
    }

    public function show(Pasien $pasien): JsonResponse
    {
        return response()->json([
            'data' => $pasien,
        ]);
    }

    public function update(Request $request, Pasien $pasien): JsonResponse
    {
        $pasien->update($this->validatePayload($request, $pasien->id));

        return response()->json([
            'message' => 'Data pasien berhasil diperbarui.',
            'data' => $pasien->fresh(),
        ]);
    }

    public function destroy(Pasien $pasien): JsonResponse
    {
        $pasien->delete();

        return response()->json([
            'message' => 'Data pasien berhasil dihapus.',
        ]);
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'tanggal' => ['nullable', 'date'],
            'kpusk' => ['nullable', 'string', 'max:20'],
            'no_reg' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pasiens', 'no_reg')->ignore($ignoreId),
            ],
            'nik' => ['nullable', 'string', 'max:255'],
            'sapaan' => ['nullable', 'string', 'max:100'],
            'nik_ibu' => ['nullable', 'string', 'max:100'],
            'nama' => ['nullable', 'string', 'max:255'],
            'kk' => ['nullable', 'string', 'max:255'],
            'ibu' => ['nullable', 'string', 'max:255'],
            'rt_rw' => ['nullable', 'string', 'max:255'],
            'kdesa' => ['nullable', 'string', 'max:255'],
            'jalan' => ['nullable', 'string', 'max:255'],
            'domisili' => ['nullable', 'string'],
            'telp' => ['nullable', 'string', 'max:255'],
            't_lahir' => ['nullable', 'string', 'max:255'],
            'tg_lahir' => ['nullable', 'date'],
            'jkl' => ['nullable', 'string', 'max:255'],
            'gd' => ['nullable', 'string', 'size:2'],
            'status' => ['nullable', 'string', 'max:50'],
            'cara_bayar' => ['nullable', 'string', 'max:20'],
            'no_asn' => ['nullable', 'string', 'max:255'],
            'jenis_bpjs' => ['nullable', 'string', 'max:100'],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'berat' => ['nullable', 'integer', 'min:0'],
            'prolanis' => ['nullable', 'string', 'max:255'],
            'alergi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'submited_at' => ['nullable', 'date'],
        ]);
    }
}
