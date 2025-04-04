<?php

namespace App\Exports;

use App\Models\Produk;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProdukExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $produk = Produk::with('produkSatuan')
            ->leftJoin('kategori', 'kategori.id_kategori', 'produk.id_kategori')
            ->select('produk.*', 'nama_kategori')
            ->orderBy('kode_produk', 'asc')
            ->get();

        $produk = $produk->map(function ($item) {
            $satuanEceran = collect($item->produkSatuan)->map(function ($satuan) {
                return $satuan->satuan . ' : ' . $satuan->harga_jual_eceran; // Format tanpa separator
            })->join(', ');

            $satuanBorongan = collect($item->produkSatuan)->map(function ($satuan) {
                return $satuan->satuan . ' : ' . $satuan->harga_jual_borongan; // Format tanpa separator
            })->join(', ');

            return [
                'kode produk'              => $item->kode_produk,
                'nama produk'              => $item->nama_produk,
                'nama kategori'            => $item->nama_kategori,
                'merk'                     => $item->merk,
                'harga beli'               => $item->harga_beli, // Nilai asli (tanpa format)
                'produk satuan eceran'     => $satuanEceran,
                'produk satuan borongan'   => $satuanBorongan,
                'stok'                     => $item->stok, // Nilai asli (tanpa format)
            ];
        });

        return $produk;
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
