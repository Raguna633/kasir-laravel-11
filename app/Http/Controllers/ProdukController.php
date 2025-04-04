<?php

namespace App\Http\Controllers;

use PDF;
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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $kategori = Kategori::all()->pluck('nama_kategori', 'id_kategori');

        return view('produk.index', compact('kategori'));
    }

    public function data()
    {
        $produk = Produk::with('produkSatuan')
            ->leftJoin('kategori', 'kategori.id_kategori', 'produk.id_kategori')
            ->select('produk.*', 'nama_kategori')
            ->orderBy('kode_produk', 'asc')
            ->get();

        $produk = $produk->map(function ($item) {
            $item->produk_satuan = $item->produkSatuan->map(function ($satuan) {
                return [
                    'satuan' => $satuan->satuan,
                    'harga_jual_eceran' => $satuan->harga_jual_eceran,
                    'harga_jual_borongan' => $satuan->harga_jual_borongan,
                ];
            })->toArray();

            return $item;
        });

        return datatables()
            ->of($produk)
            ->addIndexColumn()
            ->addColumn('select_all', function ($produk) {
                return '
                    <input type="checkbox" name="id_produk[]" value="' . $produk->id_produk . '">
                ';
            })
            ->addColumn('kode_produk', function ($produk) {
                return '<span class="label label-success">' . $produk->kode_produk . '</span>';
            })
            ->addColumn('harga_beli', function ($produk) {
                return format_uang($produk->harga_beli);
            })
            ->addColumn('produk_satuan_eceran', function ($produk) {
                return collect($produk->produk_satuan)->map(function ($satuan) {
                    return $satuan['satuan'] . ': ' . format_uang($satuan['harga_jual_eceran']);
                })->join(', ');
            })
            ->addColumn('produk_satuan_borongan', function ($produk) {
                return collect($produk->produk_satuan)->map(function ($satuan) {
                    return $satuan['satuan'] . ': ' . format_uang($satuan['harga_jual_borongan']);
                })->join(', ');
            })
            ->addColumn('stok', function ($produk) {
                return format_uang($produk->stok);
            })
            ->addColumn('aksi', function ($produk) {
                return '
                <div class="btn-group">
                    <button type="button" onclick="editForm(`' . route('produk.update', $produk->id_produk) . '`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-pencil"></i></button>
                    <button type="button" onclick="deleteData(`' . route('produk.destroy', $produk->id_produk) . '`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi', 'kode_produk', 'select_all'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $produk = Produk::latest()->first() ?? new Produk();
        if ($request['kode_produk'] == null) {
            $request['kode_produk'] = tambah_nol_didepan((int)$produk->id_produk + 1, 6);
        };

        $request->validate([
            'kode_produk' => 'required|unique:produk,kode_produk',
            'nama_produk' => 'required|string|max:255',
            'harga_beli' => 'required|numeric|min:0',
            'produk_satuan' => 'required|array',
            'produk_satuan.*.satuan' => 'required|string|max:50',
            'produk_satuan.*.harga_jual_eceran' => 'required|numeric|min:0',
            'produk_satuan.*.harga_jual_borongan' => 'nullable|numeric|min:0',
        ]);

        $data = $request->all();

        if (isset($data['produk_satuan'])) {
            foreach ($data['produk_satuan'] as $satuan) {
                if (!SatuanProduk::where('nama', $satuan['satuan'])->exists()) {
                    SatuanProduk::create(['nama' => $satuan['satuan']]);
                }
            }
        }

        // Simpan data produk
        $produk = Produk::create([
            'kode_produk' => $request->kode_produk,
            'nama_produk' => $request->nama_produk,
            'id_kategori' => $request->id_kategori,
            'merk' => $request->merk,
            'harga_beli' => $request->harga_beli,
            'diskon' => $request->diskon,
            'stok' => $request->stok,
        ]);

        // Simpan data produk_satuan
        foreach ($request->produk_satuan as $satuan) {
            ProdukSatuan::create([
                'id_produk' => $produk->id_produk,
                'satuan' => $satuan['satuan'],
                'harga_jual_eceran' => $satuan['harga_jual_eceran'],
                'harga_jual_borongan' => $satuan['harga_jual_borongan'],
            ]);
        }

        return response()->json('Data berhasil disimpan', 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $produk = Produk::with('produkSatuan')->find($id);

        if (!$produk) {
            return response()->json(['error' => 'Produk tidak ditemukan'], 404);
        }

        // Kembalikan data dengan relasi dalam format JSON
        return response()->json($produk);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'kode_produk' => "required|unique:produk,kode_produk,{$id},id_produk",
            'nama_produk' => 'required|string|max:255',
            'harga_beli' => 'required|numeric|min:0',
            'produk_satuan' => 'required|array',
            'produk_satuan.*.satuan' => 'required|string|max:50',
            'produk_satuan.*.harga_jual_eceran' => 'required|numeric|min:0',
            'produk_satuan.*.harga_jual_borongan' => 'required|numeric|min:0',
        ]);
        $produk = Produk::with('produkSatuan')->find($id);

        $produk->update($request->except('produk_satuan'));

        // Update data produk_satuan
        $dataBaru = $request->produk_satuan;

        // Ambil data lama
        $dataLama = $produk->produkSatuan->keyBy('satuan')->toArray();

        $data = $request->all();

        if (isset($data['produk_satuan'])) {
            foreach ($data['produk_satuan'] as $satuan) {
                if (!SatuanProduk::where('nama', $satuan['satuan'])->exists()) {
                    SatuanProduk::create(['nama' => $satuan['satuan']]);
                }
            }
        }

        // Loop data baru untuk memperbarui atau menambah
        foreach ($dataBaru as $satuanBaru) {
            if (isset($dataLama[$satuanBaru['satuan']])) {
                // Jika sudah ada, update
                ProdukSatuan::where('id_produk', $produk->id_produk)
                    ->where('satuan', $satuanBaru['satuan'])
                    ->update(['harga_jual_eceran' => $satuanBaru['harga_jual_eceran'], 'harga_jual_borongan' => $satuanBaru['harga_jual_borongan']]);

                // Hapus dari data lama (sudah diupdate)
                unset($dataLama[$satuanBaru['satuan']]);
            } else {
                // Jika belum ada, tambahkan
                ProdukSatuan::create([
                    'id_produk' => $produk->id_produk,
                    'satuan' => $satuanBaru['satuan'],
                    'harga_jual_eceran' => $satuanBaru['harga_jual_eceran'],
                    'harga_jual_borongan' => $satuanBaru['harga_jual_borongan'],
                ]);
            }
        }

        // Hapus data lama yang tidak ada di data baru
        if (count($dataLama) > 0) {
            ProdukSatuan::where('id_produk', $produk->id_produk)
                ->whereIn('satuan', array_keys($dataLama))
                ->delete();
        }

        return response()->json('Data berhasil diperbarui', 200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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
        $pdf = PDF::loadView('produk.barcode', compact('dataproduk', 'no'));
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
