<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Satuan extends Model
{
    protected $fillable = [
        'nama_satuan',
        'inisial',
    ];

    public function konversi()
    {
        return $this->hasMany(KonversiSatuan::class, 'satuan_id');
    }
}
