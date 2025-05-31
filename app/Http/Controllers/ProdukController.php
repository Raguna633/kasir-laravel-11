<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\ProdukSatuan;
use App\Models\SatuanProduk;
use Illuminate\Http\Request;
use App\Exports\ProdukExport;
use App\Imports\ProdukImport;
use Maatwebsite\Excel\Facades\Excel;

class ProdukController extends Controller
{
    public function index()
    {
        $kategori = Kategori::all()->pluck('nama_kategori', 'id_kategori');
        $allSatuan   = SatuanProduk::orderBy('nama')->pluck('nama');

        return view('produk.index', compact('kategori', 'allSatuan'));
    }

    public function data()
    {
        // Eager‐load relasi satuan.masterSatuan
        $all = Produk::with(['satuan.masterSatuan', 'kategori'])
            ->orderBy('kode_produk', 'asc')
            ->get();

        return datatables()
            ->of($all)
            ->addIndexColumn()
            ->addColumn('select_all', function ($prod) {
                return '<input type="checkbox" name="id_produk[]" value="' . $prod->id_produk . '">';
            })
            ->addColumn('kode_produk', function ($prod) {
                return '<span class="label label-success">' . $prod->kode_produk . '</span>';
            })
            ->addColumn('nama_kategori', function ($prod) {
                return $prod->kategori->nama_kategori ?? '-';
            })
            ->addColumn('harga_beli', function ($prod) {
                return format_uang($prod->harga_beli);
            })
            ->addColumn('produk_satuan_eceran', function ($prod) {
                return $prod->satuan
                    ->map(
                        fn($ps) =>
                        "{$ps->nama}: " . format_uang($ps->pivot->harga_jual_eceran)
                    )
                    ->join('<br>');   // kalau kosong, join akan mengembalikan ''
            })
            ->addColumn('produk_satuan_borongan', function ($prod) {
                return $prod->satuan
                    ->map(
                        fn($ps) =>
                        "{$ps->nama}: " . format_uang($ps->pivot->harga_jual_borongan)
                    )
                    ->join('<br>');
            })
            ->addColumn('stok', function ($prod) {
                return format_uang($prod->stok);
            })
            ->addColumn('aksi', function ($prod) {
                return '
            <div class="btn-group">
                <button type="button" onclick="editForm(`' . route('produk.update', $prod->id_produk) . '`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-pencil"></i></button>
                <button type="button" onclick="deleteData(`' . route('produk.destroy', $prod->id_produk) . '`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
            </div>
            ';
            })
            ->rawColumns([
                'select_all',
                'kode_produk',
                'produk_satuan_eceran',
                'produk_satuan_borongan',
                'aksi'
            ])
            ->make(true);
    }


    public function show($id)
    {
        $produk = Produk::with('satuan')->find($id);

        $satuanArr = $produk->satuan->map(function ($s) {
            return [
                'pivot_id'            => $s->pivot->id,           // id di tabel produk_satuan
                'satuan'              => $s->nama,                // nama satuan dari tabel satuan_produk
                'harga_jual_eceran'   => $s->pivot->harga_jual_eceran,
                'harga_jual_borongan' => $s->pivot->harga_jual_borongan,
            ];
        })->toArray();

        return response()->json([
            'id_produk'    => $produk->id_produk,
            'kode_produk'  => $produk->kode_produk,
            'nama_produk'  => $produk->nama_produk,
            'id_kategori'  => $produk->id_kategori,
            'merk'         => $produk->merk,
            'harga_beli'   => $produk->harga_beli,
            'diskon'       => $produk->diskon,
            'stok'         => $produk->stok,
            'produk_satuan' => $satuanArr,
        ]);
    }

    public function store(Request $request)
    {
        // 1) Generate kode jika kosong
        $last = Produk::latest('id_produk')->first();
        if (! $request->filled('kode_produk')) {
            $request->merge([
                'kode_produk' => tambah_nol_didepan(($last->id_produk ?? 0) + 1, 6)
            ]);
        }

        // 2) Validasi
        $request->validate([
            'kode_produk'                => 'required|unique:produk,kode_produk',
            'nama_produk'                => 'required|string|max:255',
            'harga_beli'                 => 'required|numeric|min:0',
            'id_kategori'                => 'required|exists:kategori,id_kategori',
            'produk_satuan'              => 'required|array|min:1',
            'produk_satuan.*.satuan'     => 'required|string|max:50',
            'produk_satuan.*.harga_jual_eceran'  => 'required|numeric|min:0',
            'produk_satuan.*.harga_jual_borongan' => 'nullable|numeric|min:0',
        ]);

        // 3) Buat Produk
        $produk = Produk::create($request->only([
            'kode_produk',
            'nama_produk',
            'id_kategori',
            'merk',
            'harga_beli',
            'diskon',
            'stok'
        ]));

        // 4) Persiapkan relasi pivot
        $attachData = [];
        foreach ($request->input('produk_satuan') as $row) {
            // a) Temukan atau buat master satuan
            $ms = SatuanProduk::firstOrCreate(['nama' => $row['satuan']]);
            // b) Siapkan array untuk attach()
            $attachData[$ms->id] = [
                'harga_jual_eceran'  => $row['harga_jual_eceran'],
                'harga_jual_borongan' => $row['harga_jual_borongan'] ?? 0,
            ];
        }

        // 5) Attach pivot sekaligus
        $produk->satuan()->attach($attachData);

        return response()->json('Data berhasil disimpan', 200);
    }

    public function update(Request $request, $id)
    {
        // 1) Validasi
        $request->validate([
            'kode_produk'                => "required|unique:produk,kode_produk,{$id},id_produk",
            'nama_produk'                => 'required|string|max:255',
            'harga_beli'                 => 'required|numeric|min:0',
            'id_kategori'                => 'required|exists:kategori,id_kategori',
            'produk_satuan'              => 'required|array|min:1',
            'produk_satuan.*.satuan'     => 'required|string|max:50',
            'produk_satuan.*.harga_jual_eceran'  => 'required|numeric|min:0',
            'produk_satuan.*.harga_jual_borongan' => 'nullable|numeric|min:0',
        ]);

        // 2) Cari produk
        $produk = Produk::findOrFail($id);

        // 3) Update data utama
        $produk->update($request->only([
            'kode_produk',
            'nama_produk',
            'id_kategori',
            'merk',
            'harga_beli',
            'diskon',
            'stok'
        ]));

        // 4) Persiapkan data sync pivot
        $syncData = [];
        foreach ($request->input('produk_satuan') as $row) {
            $ms = SatuanProduk::firstOrCreate(['nama' => $row['satuan']]);
            $syncData[$ms->id] = [
                'harga_jual_eceran'  => $row['harga_jual_eceran'],
                'harga_jual_borongan' => $row['harga_jual_borongan'] ?? 0,
            ];
        }

        // 5) Sync pivot: otomatis menambah, update, dan hapus yang tidak ada
        $produk->satuan()->sync($syncData);

        return response()->json('Data berhasil diperbarui', 200);
    }


    public function destroy($id)
    {
        $produk = Produk::find($id);
        $produk->delete();

        return response(null, 204);
    }

    public function deleteSelected(Request $request)
    {
        foreach ($request->id_produk as $id) {
            $produk = Produk::find($id);
            $produk->delete();
        }

        return response(null, 204);
    }

    public function cetakBarcode(Request $request)
    {
        $dataproduk = array();
        foreach ($request->id_produk as $id) {
            $produk = Produk::find($id);
            $dataproduk[] = $produk;
        }

        $no  = 1;
        $pdf = Pdf::loadView('produk.barcode', compact('dataproduk', 'no'));
        $pdf->setPaper('a4', 'potrait');
        return $pdf->stream('produk.pdf');
    }

    public function exportProduk()
    {
        return Excel::download(new ProdukExport, 'daftar_produk.xlsx');
    }

    public function importProduk(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new ProdukImport, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Data produk berhasil diimpor.'
            ], 200);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();

            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Baris: " . $failure->row() . ", Kolom: " . $failure->attribute() . ", Error: " . implode(", ", $failure->errors());
            }

            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. ' . implode(". ", $errorMessages),
            ], 422); // Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengimpor data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkKode(Request $request)
    {
        $kode = $request->get('kode_produk');
        $produk = Produk::where('kode_produk', $kode)->first();

        if ($produk) {
            return response()->json([
                'exists' => true,
                'nama_produk' => $produk->nama_produk
            ]);
        }

        return response()->json(['exists' => false]);
    }

    public function checkNama(Request $request)
    {
        $nama = $request->get('nama_produk');
        $produk = Produk::where('nama_produk', $nama)->first();

        if ($produk) {
            return response()->json([
                'exists' => true,
                'kode_produk' => $produk->kode_produk
            ]);
        }

        return response()->json(['exists' => false]);
    }
}
