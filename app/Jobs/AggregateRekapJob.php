<?php

namespace App\Jobs;

use App\Models\JobStatus;
use App\Services\RekapHarianService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AggregateRekapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $jobId;
    protected string $month;

    public function __construct(string $jobId, string $month)
    {
        $this->jobId = $jobId;
        $this->month = $month;
    }

    public function handle(RekapHarianService $service): void
    {
        $jobStatus = JobStatus::find($this->jobId);
        if (!$jobStatus) {
            Log::error("JobStatus record not found for aggregate job: " . $this->jobId);
            return;
        }

        $jobStatus->update(['status' => 'processing']);

        try {
            $monthCarbon = Carbon::parse($this->month . '-01');
            $service->aggregateByMonth($monthCarbon);

            $jobStatus->update(['status' => 'done']);
        } catch (\Exception $e) {
            Log::error("AggregateRekapJob failed: " . $e->getMessage());
            $jobStatus->update([
                'status' => 'failed',
                'error' => $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);
        }
    }
}
