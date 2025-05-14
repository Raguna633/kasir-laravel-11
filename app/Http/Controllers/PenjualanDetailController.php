<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Produk;
use App\Models\Setting;
use App\Models\Penjualan;
use App\Models\ProdukSatuan;
use Illuminate\Http\Request;
use App\Models\PenjualanDetail;
use App\Models\Kategori;

class PenjualanDetailController extends Controller
{
    public function index()
    {
        $produk = Produk::with('produkSatuan', 'kategori')
            ->orderBy('nama_produk', 'asc')
            ->get();
        $member = Member::orderBy('nama')->get();
        $diskon = Setting::first()->diskon ?? 0;
        $drafts = Penjualan::where('status', 0)->get();

        // $kategori = Produk::with('kategori')->get();
        // dd($produk);

        if ($id_penjualan = session('id_penjualan')) {
            $penjualan = Penjualan::find($id_penjualan);
            $memberSelected = $penjualan->member ?? new Member();

            return view('penjualan_detail.index', compact('produk', 'member', 'diskon', 'id_penjualan', 'penjualan', 'memberSelected', 'drafts'));
        } else {
            return redirect()->route('transaksi.baru');
        }
    }

    public function data($id)
    {
        $penjualan = Penjualan::findOrFail($id);
        $detail = PenjualanDetail::with(['produk', 'produkSatuan.masterSatuan', 'produk.kategori'])
            ->where('id_penjualan', $id)
            ->get();

        $data = [];
        $total = 0;
        $total_item = 0;

        foreach ($detail as $d) {
            // bangun dropdown pilih satuan
            $opts = '<select class="form-control produk-satuan" data-id="' . $d->id_penjualan_detail . '">';
            foreach ($d->produk->produkSatuan as $ps) {
                $sel = $d->id_produk_satuan == $ps->id ? 'selected' : '';
                $unitName  = optional($ps->masterSatuan)->nama;
                $harga = $penjualan->tipe_pembeli == 'eceran'
                    ? $ps->harga_jual_eceran
                    : $ps->harga_jual_borongan;
                $opts .= "<option value=\"{$ps->id}\" {$sel}>"
                    . "{$unitName} – Rp." . format_uang($harga)
                    . "</option>";
            }
            $opts .= '</select>';

            $harga_jual = optional($d->produkSatuan)->{$penjualan->tipe_pembeli == 'eceran'
                ? 'harga_jual_eceran'
                : 'harga_jual_borongan'} ?? 0;

            // input jumlah dengan presisi desimal
            $jumlahInput = '<input type="number" step="0.1" min="0.1" '
                . 'class="form-control  input-sm quantity" '
                . 'data-id="' . $d->id_penjualan_detail . '" '
                . 'value="' . $d->jumlah . '">';

            // hitung subtotal
            $subtotal = ($harga_jual * $d->jumlah) * (1 - $d->diskon / 100);

            $data[] = [
                'kode_produk'     => '<span class="label label-success">' . $d->produk->kode_produk . '</span>',
                'nama_produk'     => $d->produk->nama_produk,
                'produk_satuan'   => $opts,
                'jumlah'          => $jumlahInput,
                'max'             => $d->produk->stok, // + $d->jumlah
                'diskon'          => $d->diskon . '%',
                'subtotal'        => 'Rp. ' . format_uang($subtotal),
                'aksi'            => '<div class="btn-group">'
                    . '<button onclick="deleteData(`' . route('transaksi.destroy', $d->id_penjualan_detail) . '`)" '
                    . 'class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>'
                    . '</div>',
            ];

            $total += $subtotal;
            $total_item += $d->jumlah;
        }

        // tambahkan total & total_item tersembunyi
        $data[] = [
            'kode_produk'   => '<div class="total hide">' . $total . '</div>'
                . '<div class="total_item hide">' . $total_item . '</div>',
            'nama_produk'   => '',
            'produk_satuan' => '',
            'jumlah'        => '',
            'max'           => '',
            'diskon'        => '',
            'subtotal'      => '',
            'aksi'          => '',
        ];

        return datatables()
            ->of($data)
            ->addIndexColumn()
            ->rawColumns(['aksi', 'kode_produk', 'produk_satuan', 'jumlah'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_penjualan'       => 'required|exists:penjualan,id_penjualan',
            'id_produk'          => 'required|exists:produk,id_produk',
            'id_produk_satuan'   => 'nullable|exists:produk_satuan,id',
        ]);

        $penjualan = Penjualan::findOrFail($request->id_penjualan);
        if ($penjualan->status !== 0) {
            return response()->json(['message' => 'Transaksi telah selesai dan tidak dapat diubah.'], 400);
        }

        $produk = Produk::findOrFail($request->id_produk);

        $satuan = $request->id_produk_satuan
            ? ProdukSatuan::find($request->id_produk_satuan)
            : $produk->produkSatuan()->first();

        if (!$satuan) {
            return response()->json(['message' => 'Satuan produk tidak ditemukan'], 400);
        }

        $detail = PenjualanDetail::firstOrNew([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk'    => $produk->id_produk,
        ]);

        // jika baru, set quantity = 1, else tambahkan 1
        $jumlahBaru = $detail->exists ? ($detail->jumlah + 1) : 1;
        if ($jumlahBaru > $produk->stok) {
            return response()->json(['message' => 'Jumlah melebihi stok yang tersedia'], 400);
        }

        $detail->id_produk_satuan    = $satuan->id;
        $detail->harga_jual_eceran   = $satuan->harga_jual_eceran;
        $detail->jumlah              = $jumlahBaru;
        $detail->diskon              = 0;
        $detail->subtotal            = $satuan->harga_jual_eceran * $jumlahBaru;
        $detail->save();

        // update stok produk
        $produk->stok -= 1;
        $produk->save();

        return response()->json(['message' => 'Data berhasil disimpan', 'detail' => $detail], 200);
    }

    public function updateSatuan(Request $request, $id)
    {
        $penjualanDetail = PenjualanDetail::findOrFail($id);

        $penjualanDetail->id_produk_satuan = $request->id_satuan;

        // Cari harga jual baru berdasarkan satuan yang dipilih
        $produkSatuan = ProdukSatuan::findOrFail($request->id_satuan);
        $penjualanDetail->harga_jual_eceran = $produkSatuan->harga_jual_eceran;

        // Perbarui subtotal
        $penjualanDetail->subtotal = $penjualanDetail->harga_jual_eceran * $penjualanDetail->jumlah;

        $penjualanDetail->save();

        return response()->json(['success' => true, 'message' => 'Satuan dan harga berhasil diperbarui.']);
    }


    public function update(Request $request, $id)
    {
        $detail = PenjualanDetail::findOrFail($id);
        $produk = Produk::findOrFail($detail->id_produk);

        $jumlah = (float) $request->input('jumlah');
        if ($jumlah <= 0) {
            return response()->json(['message' => 'Jumlah harus lebih besar dari 0'], 422);
        }
        // restore stok lama sebelum validasi
        $available = $produk->stok + $detail->jumlah;
        if ($jumlah > $available) {
            return response()->json(['message' => 'Jumlah melebihi stok yang tersedia'], 400);
        }

        // perbarui stok
        $produk->stok = $available - $jumlah;
        $produk->save();

        $detail->jumlah   = $jumlah;
        $detail->subtotal = round($detail->harga_jual_eceran * $jumlah * (1 - $detail->diskon / 100), 2);
        $detail->save();

        return response()->json('Data berhasil diperbarui', 200);
    }


    public function destroy($id)
    {
        $detail = PenjualanDetail::findOrFail($id);
        // kembalikan stok
        $produk = Produk::find($detail->id_produk);
        if ($produk) {
            $produk->stok += $detail->jumlah;
            $produk->save();
        }
        $detail->delete();
        return response(null, 204);
    }

    public function loadForm($diskon = 0, $total = 0, $diterima = 0)
    {
        $bayar   = $total - ($diskon / 100 * $total);
        $kembali = $diterima - $bayar;

        $kurang = 0;
        $kurangrp = '';
        $kurang_terbilang = '';
        $bayar_text = 'Bayar: Rp. ' . format_uang($bayar); // Teks default

        if ($diterima > 0 && $kembali < 0) { // Hanya hitung jika diterima > 0 dan kurang bayar
            $kurang = abs($kembali);
            $kurangrp = format_uang($kurang);
            $kurang_terbilang = ucwords(terbilang($kurang) . ' Rupiah');
            $kembali = 0;
            $bayar_text = 'Kurang: Rp. ' . $kurangrp; // Ganti teks
        } else if ($diterima > 0) {
            $bayar_text = 'Kembali: Rp. ' . format_uang($kembali);
        }

        $data    = [
            'totalrp' => format_uang($total),
            'bayar' => $bayar,
            'bayarrp' => format_uang($bayar),
            'terbilang' => ucwords(terbilang($bayar) . ' Rupiah'),
            'kembalirp' => format_uang($kembali),
            'kembali_terbilang' => ucwords(terbilang($kembali) . ' Rupiah'),
            'kurangrp' => $kurangrp,
            'kurang_terbilang' => $kurang_terbilang,
            'bayar_text' => $bayar_text, // Kirim teks yang akan ditampilkan
        ];

        return response()->json($data);
    }
}
