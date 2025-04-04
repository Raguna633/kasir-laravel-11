<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';
    protected $primaryKey = 'id_produk';
    protected $guarded = [];

    public function produkSatuan()
    {
        return $this->hasMany(ProdukSatuan::class, 'id_produk', 'id_produk');
    }

    public function satuan()
    {
        return $this->belongsTo(ProdukSatuan::class, 'id_produk_satuan');
    }
}
