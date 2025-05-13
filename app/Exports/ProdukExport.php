<?php

namespace App\Exports;

use App\Models\Produk;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProdukExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Produk::with(['produkSatuan.masterSatuan','kategori'])
            ->get()
            ->map(function ($item) {
                $eceran = $item->produkSatuan->map(fn($ps) =>
                    $ps->masterSatuan->nama . ' : ' . $ps->harga_jual_eceran
                )->join(', ');

                $borongan = $item->produkSatuan->map(fn($ps) =>
                    $ps->masterSatuan->nama . ' : ' . $ps->harga_jual_borongan
                )->join(', ');

                return [
                    'kode produk'            => $item->kode_produk,
                    'nama produk'            => $item->nama_produk,
                    'nama kategori'          => $item->kategori->nama_kategori,
                    'merk'                   => $item->merk,
                    'harga beli'             => $item->harga_beli,
                    'produk satuan eceran'   => $eceran,
                    'produk satuan borongan' => $borongan,
                    'stok'                   => $item->stok,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'kode produk',
            'nama produk',
            'nama kategori',
            'merk',
            'harga beli',
            'produk satuan eceran',
            'produk satuan borongan',
            'stok',
        ];
    }
}
