<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class konfigurasi_shift_kerja extends Model
{
    /** @use HasFactory<\Database\Factories\KonfigurasiShiftKerjaFactory> */
    use HasFactory;

    protected $table = 'konfigurasi_shift_kerja';


    protected $fillable = [
        'id',
        'kode_jamkerja',
        'nama_jamkerja',
        'awal_jam_masuk',
        'jam_masuk',
        'jam_pulang',
    ];
}
