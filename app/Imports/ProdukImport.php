<?php

namespace App\Imports;

use App\Models\Produk;
use App\Models\Kategori;
use App\Models\ProdukSatuan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class ProdukImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $kategori = Kategori::firstOrCreate(['nama_kategori' => $row['nama kategori']]);

        $produk = Produk::updateOrCreate(
            ['kode_produk' => $row['kode produk']],
            [
                'nama_produk' => $row['nama produk'],
                'id_kategori' => $kategori->id_kategori,
                'merk' => $row['merk'],
                'harga_beli' => $this->parseCurrency($row['harga beli']),
                'stok' => $this->parseCurrency($row['stok']),
            ]
        );

        $this->importProdukSatuan($produk->id_produk, $row['produk satuan eceran'], 'eceran');
        $this->importProdukSatuan($produk->id_produk, $row['produk satuan borongan'], 'borongan');

        return $produk;
    }

    private function importProdukSatuan($idProduk, $data, $jenis)
    {
        if (empty($data) || trim($data) === '') return;

        $satuanArray = explode(',', $data);

        foreach ($satuanArray as $item) {
            $item = trim($item);
            if (empty($item)) continue;

            list($satuan, $harga) = explode(':', $item);
            $satuan = trim($satuan);
            $harga = trim($harga);

            $field = ($jenis == 'eceran') ? 'harga_jual_eceran' : 'harga_jual_borongan';

            ProdukSatuan::updateOrCreate(
                [
                    'id_produk' => $idProduk,
                    'satuan' => $satuan
                ],
                [
                    $field => $this->parseCurrency($harga)
                ]
            );
        }
    }

    private function parseCurrency($value): int
    {
        // Hilangkan semua karakter non-numerik termasuk titik dan spasi
        $value = str_replace(['.', 'Rp', ' '], '', trim($value));

        // Jika nilai kosong, kembalikan 0
        if ($value === '') {
            return 0;
        }
        return (int) $value;
    }

    public function rules(): array
    {
        return [
            'kode produk' => [
                'required',
                Rule::unique('produk', 'kode_produk')
            ],
            'nama produk' => 'required|max:255',
            'harga beli' => 'required|numeric',
            'stok' => 'required|integer|min:0',
            'produk satuan eceran' => 'nullable',
            'produk satuan borongan' => 'nullable'
        ];
    }

    public function prepareForValidation($data)
    {
        $data['harga beli'] = $this->parseCurrency($data['harga beli']);
        $data['stok'] = $this->parseCurrency($data['stok']);

        return $data;
    }
}
