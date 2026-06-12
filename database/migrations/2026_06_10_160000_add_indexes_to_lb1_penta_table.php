<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lb1_penta', function (Blueprint $table) {
            // Composite covering index for high-performance daily aggregation queries
            $table->index(['tanggal', 'kpusk', 'diagnosa'], 'lb1_penta_tanggal_kpusk_diagnosa_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lb1_penta', function (Blueprint $table) {
            $table->dropIndex('lb1_penta_tanggal_kpusk_diagnosa_index');
        });
    }
};
