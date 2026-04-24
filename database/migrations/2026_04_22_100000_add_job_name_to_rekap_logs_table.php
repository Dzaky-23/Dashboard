<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const JOB_NAME = 'recap-top-build';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rekap_logs', function (Blueprint $table) {
            $table->string('job_name', 100)->nullable()->after('id');
        });

        $latestLog = DB::table('rekap_logs')
            ->orderByDesc('last_processed_id')
            ->orderByDesc('id')
            ->first();

        DB::table('rekap_logs')->delete();

        $now = now();
        DB::table('rekap_logs')->insert([
            'job_name' => self::JOB_NAME,
            'last_processed_id' => $latestLog?->last_processed_id ?? 0,
            'last_processed_at' => $latestLog?->last_processed_at,
            'created_at' => $latestLog?->created_at ?? $now,
            'updated_at' => $now,
        ]);

        Schema::table('rekap_logs', function (Blueprint $table) {
            $table->unique('job_name', 'rekap_logs_job_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_logs', function (Blueprint $table) {
            $table->dropUnique('rekap_logs_job_name_unique');
            $table->dropColumn('job_name');
        });
    }
};
