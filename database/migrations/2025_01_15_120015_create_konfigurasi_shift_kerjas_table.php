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
        Schema::create('konfigurasi_shift_kerja', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->char('kode_jamkerja');
            $table->char('nama_jamkerja');
            $table->time('awal_jam_masuk');
            $table->time('jam_masuk');
            $table->time('jam_pulang');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_shift_kerjas');
    }
};
