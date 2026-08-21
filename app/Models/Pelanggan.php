<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $table = 'pelanggans';

    protected $fillable = [
        'nama',
        'phone',
        'email',
        'alamat',
        'bank',
        'no_rekening',
        'slug',
        'status',
        'saldo',
    ];
    public function saldo()
    {
        return $this->hasOne(MoneyNote::class, 'pelanggal_id');
    }
}
