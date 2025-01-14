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
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->char('Nama');
            $table->string('email');
            $table->date('Tanggal_presensi');
            $table->time('jam_in');
            $table->time('jam_out')->nullable();
            $table->char('foto_in');
            $table->char('foto_out')->nullable();
            $table->text('lokasi_in');
            $table->text('lokasi_out')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};
