<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MiniJurnalItem extends Model
{
    protected $fillable = ['mini_jurnal_id', 'akun_id', 'debit', 'kredit'];

    public function jurnal()
    {
        return $this->belongsTo(MiniJurnal::class);
    }

    public function akun()
    {
        return $this->belongsTo(Akun::class);
    }
}
