<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;

class KategoriController extends Controller
{
    public function index()
    {
        return view('kategori.index');
    }

    public function data()
    {
        $kategori = Kategori::orderBy('id_kategori', 'desc')->get();

        return datatables()
            ->of($kategori)
            ->addIndexColumn()
            ->addColumn('aksi', function ($kategori) {
                return '
                <div class="btn-group">
                    <button onclick="showDetail(`' . route('kategori.show', $kategori->id_kategori) . '`)" class="btn btn-xs btn-success btn-flat"><i class="fa fa-eye"></i></button>
                    <button onclick="editForm(`' . route('kategori.update', $kategori->id_kategori) . '`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-pencil"></i></button>
                    <button onclick="deleteData(`' . route('kategori.destroy', $kategori->id_kategori) . '`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $kategori = new Kategori();
        $kategori->nama_kategori = $request->nama_kategori;
        $kategori->save();

        return response()->json('Data berhasil disimpan', 200);
    }

    public function show($id)
    {
        $kategori = Kategori::with('produk')->findOrFail($id);
        $produk = $kategori->produk;
        return response()->json([
            'nama_kategori' => $kategori->nama_kategori,
            'produk'        => $produk->map(fn($p) => [
                'kode'  => $p->kode_produk,
                'nama'  => $p->nama_produk,
                'stok'  => $p->stok,
            ]),
        ]);
    }

    public function showDetail($id)
    {
        $kategori = Kategori::with('produk')->findOrFail($id);
        return response()->json([
            'nama_kategori' => $kategori->nama_kategori,
            'produk'        => $kategori->produk->map(fn($p) => [
                'kode'  => $p->kode_produk,
                'nama'  => $p->nama_produk,
                'stok'  => $p->stok,
            ]),
        ]);
    }


    public function update(Request $request, $id)
    {
        $kategori = Kategori::find($id);
        $kategori->nama_kategori = $request->nama_kategori;
        $kategori->update();

        return response()->json('Data berhasil disimpan', 200);
    }

    public function destroy($id)
    {
        $kategori = Kategori::find($id);
        $kategori->delete();

        return response(null, 204);
    }
}
