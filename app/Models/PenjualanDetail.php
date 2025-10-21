<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanDetail extends Model
{
    use HasFactory;

    protected $table = 'penjualan_detail';
    protected $primaryKey = 'id_penjualan_detail';
    protected $guarded = [];

    protected $casts = [
        'jumlah' => 'float',
        'harga_jual_eceran' => 'float',
        'harga_jual_borongan' => 'float',
    ];

    public function produk()
    {
        return $this->hasOne(Produk::class, 'id_produk', 'id_produk');
    }
    public function produkSatuan()
    {
        return $this->belongsTo(ProdukSatuan::class, 'id_produk_satuan', 'id');
    }

    public function kategori()
    {
        return $this->hasMany(Kategori::class, 'id_kategori', 'id_kategori');
    }
}
