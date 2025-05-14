<?php

namespace App\Http\Controllers;

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

        $setting = Setting::first();

        return datatables()
            ->of($penjualan)
            ->addIndexColumn()
            ->addColumn('nama_pembeli', fn($p) => $p->nama_pembeli)
            ->addColumn('total_item', fn($p) => format_uang($p->total_item))
            ->addColumn('satuan', function ($p) {
                return $p->details
                    ->pluck('produkSatuan.satuan')
                    ->filter()
                    ->unique()
                    ->join(', ');
            })
            ->addColumn('total_harga', fn($p) => 'Rp. ' . format_uang($p->total_harga))
            ->addColumn('tipe', fn($p) => $p->tipe_pembeli)
            ->addColumn('bayar', fn($p) => 'Rp. ' . format_uang($p->bayar))
            ->addColumn('tanggal', fn($p) => tanggal_indonesia($p->created_at, false))
            ->addColumn('kode_member', fn($p) =>
            '<span class="label label-success">' . ($p->member->kode_member ?? '') . '</span>')
            ->editColumn('diskon', fn($p) => $p->diskon . '%')
            ->editColumn('kasir', fn($p) => $p->user->name ?? '')
            ->addColumn('aksi', function ($p) use ($setting) {
                $show   = "<button onclick=\"showDetail('" . route('penjualan.show', $p->id_penjualan) . "')\" class=\"btn btn-xs btn-info\"><i class=\"fa fa-eye\"></i></button>";
                $del    = "<button onclick=\"deleteData('" . route('penjualan.destroy', $p->id_penjualan) . "')\" class=\"btn btn-xs btn-danger\"><i class=\"fa fa-trash\"></i></button>";
                $print  = $setting->tipe_nota == 1
                    ? "<button onclick=\"notaKecil('" . route('penjualan.printnota_kecil', $p->id_penjualan) . "')\" class=\"btn btn-xs btn-success\"><i class=\"fa fa-print\"></i></button>"
                    : "<button onclick=\"notaBesar('" . route('penjualan.printnota_besar', $p->id_penjualan) . "')\" class=\"btn btn-xs btn-success\"><i class=\"fa fa-print\"></i></button>";
                return "<div class=\"btn-group\">{$show}{$del}{$print}</div>";
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
        $penjualan = Penjualan::with(['details.produk', 'details.produkSatuan', 'produkSatuan.masterSatuan'])
            ->findOrFail($id);

        return datatables()
            ->of($penjualan->details)
            ->addIndexColumn()
            ->addColumn('kode_produk', fn($d) =>
            '<span class="label label-success">' . $d->produk->kode_produk . '</span>')
            ->addColumn('nama_produk', fn($d) => $d->produk->nama_produk)
            ->addColumn('harga_jual', function ($d) use ($penjualan) {
                $ps = $d->produkSatuan;
                $unitName  = optional($ps->masterSatuan)->nama;
                $harga = $penjualan->tipe_pembeli == 'eceran'
                    ? $ps->harga_jual_eceran
                    : $ps->harga_jual_borongan;
                return 'Rp. ' . format_uang($harga) . ' - ' . $unitName;
            })
            ->addColumn('jumlah', fn($d) =>
            format_uang($d->jumlah) . ' ' . $d->produkSatuan->satuan)
            ->addColumn('subtotal', function ($d) use ($penjualan) {
                $ps = $d->produkSatuan;
                $harga = $penjualan->tipe_pembeli == 'eceran'
                    ? $ps->harga_jual_eceran
                    : $ps->harga_jual_borongan;
                $sub   = ($harga * $d->jumlah) * (1 - $d->diskon / 100);
                return 'Rp. ' . format_uang($sub);
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
        $pdf->setPaper([0, 0, 609, 440], 'potrait');
        return $pdf->stream('Transaksi-' . date('Y-m-d-his') . '.pdf');
    }

    public function printnotaKecil($id)
    {
        $setting = Setting::first();
        $penjualan = Penjualan::with(['details.produk', 'details.produkSatuan'])
            ->find($id);

        if (!$penjualan) {
            abort(404);
        }

        $detail = PenjualanDetail::with(['produk', 'produkSatuan'])
            ->where('id_penjualan', $id)
            ->get();

        return view('penjualan.nota_kecil', compact('setting', 'penjualan', 'detail'));
    }


    public function printnotaBesar($id)
    {
        $setting = Setting::first();
        $penjualan = Penjualan::with(['details.produk', 'details.produkSatuan'])
            ->find($id);

        if (!$penjualan) {
            abort(404);
        }

        $detail = PenjualanDetail::with(['produk', 'produkSatuan'])
            ->where('id_penjualan', $id)
            ->get();

        $pdf = PDF::loadView('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
        $pdf->setPaper([0, 0, 609, 440], 'potrait');
        return $pdf->stream('Transaksi-' . date('Y-m-d-his') . '.pdf');
    }
}
