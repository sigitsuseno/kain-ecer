<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penerimaan extends Model
{
    protected $table = 'penerimaans';

    protected $fillable = [
        'nomor_surat',
        'tanggal_surat',
        'supplier_id',
        'keterangan',
        'total_terima_roll',
        'total_terima_panjang',
    ];
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function barang()
    {
        return $this->hasMany(PenerimaanBarang::class, 'penerimaan_id');
    }
}
