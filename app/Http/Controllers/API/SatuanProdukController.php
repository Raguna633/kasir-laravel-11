<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SatuanProduk;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SatuanProdukController extends Controller
{
    /**
     * List semua satuan.
     */
    public function index()
    {
        return response()->json(SatuanProduk::orderBy('nama')->get());
    }

    /**
     * Tambah satuan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => ['required', 'string', 'max:50', 'unique:satuan_produk,nama'],
        ]);

        $satuan = SatuanProduk::create(['nama' => $request->nama]);

        return response()->json([
            'message' => 'Satuan berhasil ditambah',
            'data'    => $satuan,
        ], 201);
    }

    /**
     * Update nama satuan.
     */
    public function update(Request $request, $id)
    {
        $satuan = SatuanProduk::findOrFail($id);

        $request->validate([
            'nama' => [
                'required', 'string', 'max:50',
                Rule::unique('satuan_produk', 'nama')->ignore($satuan->id),
            ],
        ]);

        $satuan->nama = $request->nama;
        $satuan->save();

        return response()->json([
            'message' => 'Satuan berhasil diubah',
            'data'    => $satuan,
        ]);
    }

    /**
     * Hapus satuan.
     */
    public function destroy($id)
    {
        $satuan = SatuanProduk::findOrFail($id);
        $satuan->delete();

        return response()->json(['message' => 'Satuan berhasil dihapus'], 204);
    }
}
