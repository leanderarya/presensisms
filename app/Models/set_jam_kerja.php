<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class set_jam_kerja extends Model
{
    protected $table = 'set_jam_kerja';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'nama',
        'hari',
        'kode_jamkerja',
    ];

     public function konfigurasi_shift_kerja()
    {
        return $this->belongsTo(konfigurasi_shift_kerja::class, 'kode_jamkerja', 'kode_jamkerja');
    }
}
