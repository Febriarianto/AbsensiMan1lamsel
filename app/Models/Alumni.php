<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    use HasFactory;

    protected $table = 'alumni';

    protected $fillable = [
        'nama',
        'nisn',
        'jenis_kelamin',
        'tanggal_lahir',
        'agama',
        'nama_ayah',
        'nama_ibu',
        'kelas_terakhir',
        'tahun_lulus',
        'kontak',
        'alamat',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];
}
