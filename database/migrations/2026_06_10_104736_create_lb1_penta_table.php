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
        Schema::create('lb1_penta', function (Blueprint $table) {
            $table->increments('id_lb1');
            $table->date('tanggal')->nullable();
            $table->string('nik', 255)->nullable();
            $table->string('kpusk', 255)->nullable();
            $table->string('no_reg', 50)->nullable();
            $table->string('diagnosa', 255)->nullable();
            $table->string('status', 255)->nullable()->default('Baru');
            $table->string('kdesa', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lb1_penta');
    }
};
