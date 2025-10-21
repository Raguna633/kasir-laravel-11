<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'penjualan';
    protected $primaryKey = 'id_penjualan';
    protected $fillable = [
        'id_member',
        'id_pelayan',
        'total_item',
        'total_harga',
        'diskon',
        'bayar',
        'diterima',
        'hutang',
        'tipe_pembeli',
        'nama_pembeli',
        'status',
        'ishutang',
        'id_user',
    ];

    // Constants for status values
    const STATUS_DRAFT = 0;
    const STATUS_FINAL = 1;

    public function member()
    {
        return $this->hasOne(Member::class, 'id_member', 'id_member');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function details()
    {
        return $this->hasMany(PenjualanDetail::class, 'id_penjualan', 'id_penjualan');
    }

    public function produkSatuan()
    {
        return $this->belongsTo(ProdukSatuan::class, 'id_produk_satuan', 'id');
    }

    public function pelayan()
    {
        return $this->belongsTo(Pelayan::class, 'id_pelayan', 'id');
    }

    // public function satuan()
    // {
    //     return $this->belongsTo(ProdukSatuan::class, 'id_produk_satuan');
    // }
}
