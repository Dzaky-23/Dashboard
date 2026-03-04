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
        Schema::create('pasiens', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->nullable();
            $table->string('kpusk', 20)->nullable();
            $table->string('no_reg');
            $table->string('nik')->nullable();
            $table->string('sapaan', 100)->nullable();
            $table->string('nik_ibu', 100)->nullable();
            $table->string('nama')->nullable();
            $table->string('kk')->nullable();
            $table->string('ibu')->nullable();
            $table->string('rt_rw')->nullable();
            $table->string('kdesa')->nullable();
            $table->string('jalan')->nullable();
            $table->text('domisili')->nullable();
            $table->string('telp')->nullable();
            $table->string('t_lahir')->nullable();
            $table->date('tg_lahir')->nullable();
            $table->string('jkl')->nullable();
            $table->char('gd', 2)->nullable();
            $table->string('status', 50)->nullable()->default('Lama');
            $table->string('cara_bayar', 20)->nullable();
            $table->string('no_asn')->nullable();
            $table->string('jenis_bpjs', 100)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->integer('berat')->nullable()->default(0);
            $table->string('prolanis')->nullable();
            $table->string('alergi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('submited_at')->useCurrent();
            
            $table->index(['nik', 'kpusk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasiens');
    }
};
