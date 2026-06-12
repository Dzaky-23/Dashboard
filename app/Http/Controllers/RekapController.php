<?php

namespace App\Http\Controllers;

use App\Jobs\AggregateRekapJob;
use App\Models\JobStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    public function dispatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bulan' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $jobStatus = JobStatus::create([
            'type' => 'aggregate',
            'status' => 'pending',
            'payload' => $validated,
        ]);

        AggregateRekapJob::dispatch($jobStatus->id, $validated['bulan']);

        return response()->json([
            'job_id' => $jobStatus->id,
        ]);
    }

    public function checkStatus(string $jobId): JsonResponse
    {
        $jobStatus = JobStatus::find($jobId);
        if (!$jobStatus) {
            return response()->json(['error' => 'Job tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => $jobStatus->status,
        ]);
    }
}
