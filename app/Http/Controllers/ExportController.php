<?php

namespace App\Http\Controllers;

use App\Jobs\ExportRekapJob;
use App\Models\JobStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function dispatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'scopes' => ['required', 'array'],
            'scopes.*' => ['in:umum,kecamatan,puskesmas'],
            'top_n_umum' => ['nullable', 'integer', 'min:1'],
            'top_n_kecamatan' => ['nullable', 'integer', 'min:1'],
            'top_n_puskesmas' => ['nullable', 'integer', 'min:1'],
            'kecamatan_filter_mode' => ['nullable', 'in:all,selected'],
            'selected_kecamatan' => ['nullable', 'array'],
            'selected_kecamatan.*' => ['string'],
            'puskesmas_filter_mode' => ['nullable', 'in:all,selected'],
            'selected_puskesmas' => ['nullable', 'array'],
            'selected_puskesmas.*' => ['string'],
            'format' => ['required', 'in:excel,pdf,csv'],
            'filters' => ['nullable', 'array'],
        ]);

        $jobStatus = JobStatus::create([
            'type' => 'export',
            'status' => 'pending',
            'payload' => $validated,
            'user_id' => auth()->id(),
        ]);

        ExportRekapJob::dispatch(
            $jobStatus->id,
            $validated['from'],
            $validated['to'],
            $validated['scopes'],
            (int) ($validated['top_n_umum'] ?? 10),
            (int) ($validated['top_n_kecamatan'] ?? 10),
            (int) ($validated['top_n_puskesmas'] ?? 10),
            (($validated['kecamatan_filter_mode'] ?? 'all') === 'selected') ? ($validated['selected_kecamatan'] ?? null) : null,
            (($validated['puskesmas_filter_mode'] ?? 'all') === 'selected') ? ($validated['selected_puskesmas'] ?? null) : null,
            $validated['format'],
            auth()->id(),
            $validated['filters'] ?? []
        );

        return response()->json([
            'job_id' => $jobStatus->id,
        ]);
    }

    public function checkStatus(string $jobId): JsonResponse
    {
        $jobStatus = JobStatus::where('id', $jobId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$jobStatus) {
            return response()->json(['error' => 'Job tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => $jobStatus->status,
        ]);
    }

    public function download(string $jobId)
    {
        $jobStatus = JobStatus::where('id', $jobId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$jobStatus) {
            abort(404, 'Job tidak ditemukan');
        }

        if ($jobStatus->status !== 'done' || !$jobStatus->output_path) {
            abort(400, 'File belum siap untuk diunduh');
        }

        $filePath = storage_path('app/exports/' . $jobStatus->output_path);
        if (!file_exists($filePath)) {
            abort(404, 'File tidak ditemukan di server');
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
