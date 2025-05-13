<?php

namespace App\Imports;

use App\Models\Produk;
use App\Models\Kategori;
use App\Models\ProdukSatuan;
use App\Models\SatuanProduk;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class ProdukImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $kategori = Kategori::firstOrCreate([
            'nama_kategori' => $row['nama kategori']
        ]);

        $produk = Produk::updateOrCreate(
            ['kode_produk' => $row['kode produk']],
            [
                'nama_produk' => $row['nama produk'],
                'id_kategori' => $kategori->id_kategori,
                'merk'        => $row['merk'] ?? null,
                'harga_beli'  => $this->parseCurrency($row['harga beli']),
                'stok'        => $this->parseCurrency($row['stok']),
            ]
        );

        // import satuan eceran & borongan
        $this->importProdukSatuan($produk->id_produk, $row['produk satuan eceran'], 'eceran');
        $this->importProdukSatuan($produk->id_produk, $row['produk satuan borongan'], 'borongan');

        return $produk;
    }

    private function importProdukSatuan(int $idProduk, ?string $data, string $jenis)
    {
        if (empty($data)) {
            return;
        }

        // pisah entri berdasarkan koma
        $entries = array_filter(array_map('trim', explode(',', $data)));

        foreach ($entries as $entry) {
            // hanya proses jika ada ":" di string
            if (! str_contains($entry, ':')) {
                continue;
            }

            [$satuanNama, $hargaStr] = array_map('trim', explode(':', $entry, 2));

            // skip jika nama atau harga kosong
            if ($satuanNama === '' || $hargaStr === '') {
                continue;
            }

            // cari atau buat master satuan
            $master = SatuanProduk::firstOrCreate(['nama' => $satuanNama]);

            // jika gagal membuat/ambil id, skip
            if (! $master->id) {
                continue;
            }

            // tentukan kolom harga
            $field = $jenis === 'eceran'
                ? 'harga_jual_eceran'
                : 'harga_jual_borongan';

            // simpan atau update ke produk_satuan
            ProdukSatuan::updateOrCreate(
                [
                    'id_produk' => $idProduk,
                    'id_satuan' => $master->id,
                ],
                [
                    $field => $this->parseCurrency($hargaStr),
                ]
            );
        }
    }

    private function parseCurrency(string $value): int
    {
        // hilangkan semua non цифра
        $clean = preg_replace('/[^\d]/', '', $value);
        return $clean === '' ? 0 : (int) $clean;
    }

    public function rules(): array
    {
        return [
            'kode produk' => [
                'required',
                Rule::unique('produk', 'kode_produk')
            ],
            'nama produk' => 'required|max:255',
            'harga beli'  => 'required|numeric',
            'stok'        => 'required|numeric|min:0',
        ];
    }
}
