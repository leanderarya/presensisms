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
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->char("nama_lengkap");
            $table->char("email")->unique();
            $table->char("posisi")->default('operator');
            $table->char("no_hp")->nullable();
            $table->char("foto")->nullable();
            $table->char("tempat_lahir")->nullable();
            $table->date("tanggal_lahir")->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
