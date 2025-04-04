<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukSatuan extends Model
{
    protected $table = 'produk_satuan';
    protected $fillable = ['id_produk', 'satuan', 'harga_jual_eceran', 'harga_jual_borongan'];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}

