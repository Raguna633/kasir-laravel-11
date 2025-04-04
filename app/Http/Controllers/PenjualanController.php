<?php

namespace App\Http\Controllers;

// use PDF;
use App\Models\Member;
use App\Models\Produk;
use App\Models\Setting;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\PenjualanDetail;
use Barryvdh\DomPDF\Facade\Pdf;

class PenjualanController extends Controller
{
    public function index()
    {
        return view('penjualan.index');
    }

    public function data()
    {
        $penjualan = Penjualan::with(['member', 'details.produkSatuan'])
            ->orderBy('id_penjualan', 'desc')
            ->get();

        return datatables()
            ->of($penjualan)
            ->addIndexColumn()
            ->addColumn('nama_pembeli', function ($penjualan) {
                return $penjualan->nama_pembeli;
            })
            ->addColumn('total_item', function ($penjualan) {
                return format_uang($penjualan->total_item);
            })
            ->addColumn('satuan', function ($penjualan) {
                return $penjualan->details->map(function ($detail) {
                    return $detail->produkSatuan->satuan ?? 'Tidak ada satuan';
                })->join(', ');
            })
            ->addColumn('total_harga', function ($penjualan) {
                return 'Rp. ' . format_uang($penjualan->total_harga);
            })
            ->addColumn('tipe', function ($penjualan) {
                return $penjualan->tipe_pembeli;
            })
            ->addColumn('bayar', function ($penjualan) {
                return 'Rp. ' . format_uang($penjualan->bayar);
            })
            ->addColumn('tanggal', function ($penjualan) {
                return tanggal_indonesia($penjualan->created_at, false);
            })
            ->addColumn('kode_member', function ($penjualan) {
                $member = $penjualan->member->kode_member ?? '';
                return '<span class="label label-success">' . $member . '</span>';
            })
            ->editColumn('diskon', function ($penjualan) {
                return $penjualan->diskon . '%';
            })
            ->editColumn('kasir', function ($penjualan) {
                return $penjualan->user->name ?? '';
            })
            ->addColumn('aksi', function ($penjualan) {
                if (auth()->user()->level == 1) {
                    return '
                <div class="btn-group">
                    <button onclick="showDetail(`' . route('penjualan.show', $penjualan->id_penjualan) . '`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-eye"></i></button>
                    <button onclick="deleteData(`' . route('penjualan.destroy', $penjualan->id_penjualan) . '`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
                } else {
                    return '
                <div class="btn-group">
                    <button onclick="showDetail(`' . route('penjualan.show', $penjualan->id_penjualan) . '`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-eye"></i></button>
                </div>
                ';
                }
            })
            ->rawColumns(['aksi', 'kode_member'])
            ->make(true);
    }

    public function create()
    {
        $penjualan = new Penjualan();
        $penjualan->id_member = null;
        $penjualan->total_item = 0;
        $penjualan->total_harga = 0;
        $penjualan->diskon = 0;
        $penjualan->bayar = 0;
        $penjualan->diterima = 0;
        $penjualan->tipe_pembeli = 'eceran';
        $penjualan->status = 0; // Set status sebagai draft
        $penjualan->id_user = auth()->id();
        $penjualan->save();

        session(['id_penjualan' => $penjualan->id_penjualan]);
        return redirect()->route('transaksi.index');
    }

    public function updateTipePembeli(Request $request)
    {
        $penjualan = Penjualan::findOrFail($request->id_penjualan);
        if ($penjualan) {
            $penjualan->tipe_pembeli = $request->tipe_pembeli;
            $penjualan->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'total_item' => 'required|numeric|min:1',
            'total' => 'required|numeric|min:1',
            'nama_pembeli' => 'required|string|max:12',
            'diskon' => 'nullable|numeric|min:0',
            'diterima' => 'required|numeric|min:0',
        ]);

        $penjualan = Penjualan::findOrFail($request->id_penjualan);

        // Periksa apakah transaksi disimpan sebagai hutang
        if ($request->has('simpan_sebagai_hutang')) {
            $penjualan->id_member = $request->id_member;
            $penjualan->total_item = $request->total_item;
            $penjualan->total_harga = $request->total;
            $penjualan->diskon = $request->diskon;
            $penjualan->bayar = $request->bayar;
            $penjualan->diterima = $request->diterima;
            $penjualan->hutang = $penjualan->total_harga - ($penjualan->diskon / 100 * $penjualan->total_harga) - $request->diterima;
            $penjualan->tipe_pembeli = $request->tipe_pembeli;
            $penjualan->nama_pembeli = $request->nama_pembeli;
            $penjualan->status = 0; // Tetap draft
            $penjualan->ishutang = 1;

            // Perbarui stok produk
            $detail = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
            foreach ($detail as $item) {
                $produk = Produk::find($item->id_produk);
                $produk->stok -= $item->jumlah; // Kurangi stok
                $produk->update();
            }

            $penjualan->update();

            return response()->json(['success' => true, 'message' => 'Transaksi disimpan sebagai hutang.']);
        }

        // Logika default untuk menyimpan transaksi
        if ($penjualan->status === 0) { // Jika masih draft
            $penjualan->id_member = $request->id_member;
            $penjualan->total_item = $request->total_item;
            $penjualan->total_harga = $request->total;
            $penjualan->diskon = $request->diskon;
            $penjualan->bayar = $request->bayar;
            $penjualan->diterima = $request->diterima;
            $penjualan->tipe_pembeli = $request->tipe_pembeli;
            $penjualan->nama_pembeli = $request->nama_pembeli;

            if ($request->diterima < $penjualan->total_harga) {
                $penjualan->hutang = $penjualan->total_harga - ($penjualan->diskon / 100 * $penjualan->total_harga) - $request->diterima;
                $penjualan->status = 0; // Tetap draft
            } else {
                $penjualan->hutang = 0;
                $penjualan->status = 1; // Final
            }

            $penjualan->update();


            // Perbarui stok hanya jika status final
            if ($penjualan->status === 1 && $penjualan->ishutang === 0) {
                $detail = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
                foreach ($detail as $item) {
                    $produk = Produk::find($item->id_produk);
                    $produk->stok -= $item->jumlah;
                    $produk->update();
                }
            }
        } else {
            return redirect()->back()->withErrors(['error' => 'Transaksi telah selesai dan tidak dapat diperbarui.']);
        }

        if ($penjualan->diterima > 0) {
            return redirect()->route('transaksi.selesai');
        } else {
            return redirect()->route('transaksi.baru');
        };
    }

    public function getDraftTransaction($id_penjualan)
    {
        // Ambil data transaksi beserta detail produk dan satuan terkait
        $penjualan = Penjualan::with(['details.produk', 'details.produkSatuan', 'member'])
            ->where('id_penjualan', $id_penjualan)
            ->where('status', 0)
            ->firstOrFail();

        $produk = Produk::orderBy('nama_produk')->get();
        $member = Member::orderBy('nama')->get();
        $memberSelected = $penjualan->member ?? new Member();
        $diskon = Setting::first()->diskon ?? 0;
        $drafts = Penjualan::where('status', 0)->get();

        session(['id_penjualan' => $penjualan->id_penjualan]);
        return view('penjualan_detail.index', compact('penjualan', 'id_penjualan', 'produk', 'drafts', 'member', 'diskon', 'memberSelected'));
    }



    public function show($id)
    {
        $penjualan = Penjualan::findOrFail($id); // Ambil transaksi berdasarkan ID
        $detail = PenjualanDetail::with(['produk', 'produk.produkSatuan'])
            ->where('id_penjualan', $id)
            ->get();

        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', function ($detail) {
                return '<span class="label label-success">' . $detail->produk->kode_produk . '</span>';
            })
            ->addColumn('nama_produk', function ($detail) {
                return $detail->produk->nama_produk;
            })
            ->addColumn('harga_jual', function ($detail) use ($penjualan) {
                // Ambil harga jual berdasarkan tipe pembeli
                $harga_jual = 0;
                foreach ($detail->produk->produkSatuan as $satuan) {
                    if ($detail->id_produk_satuan == $satuan->id) {
                        $harga_jual = $penjualan->tipe_pembeli == 'eceran' ? $satuan->harga_jual_eceran : $satuan->harga_jual_borongan;
                    }
                }
                return 'Rp. ' . format_uang($harga_jual);
            })
            ->addColumn('jumlah', function ($detail) {
                return format_uang($detail->jumlah) . ' ' . ($detail->produkSatuan->satuan ?? '');
            })
            ->addColumn('subtotal', function ($detail) use ($penjualan) {
                // Ambil harga jual berdasarkan tipe pembeli
                $harga_jual = 0;
                foreach ($detail->produk->produkSatuan as $satuan) {
                    if ($detail->id_produk_satuan == $satuan->id) {
                        $harga_jual = $penjualan->tipe_pembeli == 'eceran' ? $satuan->harga_jual_eceran : $satuan->harga_jual_borongan;
                    }
                }

                // Hitung subtotal setelah diskon
                $subtotal = ($harga_jual * $detail->jumlah) * (1 - $detail->diskon / 100);
                return 'Rp. ' . format_uang($subtotal);
            })
            ->rawColumns(['kode_produk'])
            ->make(true);
    }


    public function destroy($id)
    {
        $penjualan = Penjualan::find($id);
        $detail    = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            if ($produk) {
                $produk->stok += $item->jumlah;
                $produk->update();
            }

            $item->delete();
        }

        $penjualan->delete();

        return response(null, 204);
    }

    public function selesai()
    {
        $setting = Setting::first();

        return view('penjualan.selesai', compact('setting'));
    }

    public function notaKecil()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::with(['details.produk', 'details.produkSatuan'])
            ->find(session('id_penjualan'));

        if (!$penjualan) {
            abort(404);
        }

        $detail = PenjualanDetail::with(['produk', 'produkSatuan'])
            ->where('id_penjualan', session('id_penjualan'))
            ->get();

        return view('penjualan.nota_kecil', compact('setting', 'penjualan', 'detail'));
    }


    public function notaBesar()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::with(['details.produk', 'details.produkSatuan'])
            ->find(session('id_penjualan'));

        if (!$penjualan) {
            abort(404);
        }

        $detail = PenjualanDetail::with(['produk', 'produkSatuan'])
            ->where('id_penjualan', session('id_penjualan'))
            ->get();

        $pdf = PDF::loadView('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
        $pdf->setPaper(0, 0, 609, 440, 'potrait');
        return $pdf->stream('Transaksi-' . date('Y-m-d-his') . '.pdf');
    }
}
