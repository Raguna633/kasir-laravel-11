<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Produk;
use App\Models\Setting;
use App\Models\Penjualan;
use App\Models\ProdukSatuan;
use Illuminate\Http\Request;
use App\Models\PenjualanDetail;

class PenjualanDetailController extends Controller
{
    public function index()
    {
        $produk = Produk::with('produkSatuan')
            ->orderBy('nama_produk', 'asc')
            ->get();
        $member = Member::orderBy('nama')->get();
        $diskon = Setting::first()->diskon ?? 0;
        $drafts = Penjualan::where('status', 0)->get();

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
        $penjualan = Penjualan::findOrFail($id); // Menggunakan ID langsung, bukan session
        $detail = PenjualanDetail::with(['produk', 'produk.produkSatuan'])
            ->where('id_penjualan', $id)
            ->get();

        $data = [];
        $total = 0;
        $total_item = 0;

        foreach ($detail as $item) {
            $row = [];
            $row['kode_produk'] = '<span class="label label-success">' . $item->produk->kode_produk . '</span>';
            $row['nama_produk'] = $item->produk->nama_produk;

            // **Dropdown Pilihan Satuan**
            $row['produk_satuan'] = '<select class="form-control input-sm produk-satuan" data-id="' . $item->id_penjualan_detail . '">';
            foreach ($item->produk->produkSatuan as $satuan) {
                $selected = $item->id_produk_satuan == $satuan->id ? 'selected' : '';
                $harga_satuan = $penjualan->tipe_pembeli == 'eceran' ? $satuan->harga_jual_eceran : $satuan->harga_jual_borongan;
                $row['produk_satuan'] .= '<option value="' . $satuan->id . '" ' . $selected . '>' . $satuan->satuan . ' - Rp. ' . format_uang($harga_satuan) . '</option>';
            }
            $row['produk_satuan'] .= '</select>';

            // **Ambil Harga Sesuai Satuan yang Dipilih**
            $harga_jual = 0;
            foreach ($item->produk->produkSatuan as $satuan) {
                if ($item->id_produk_satuan == $satuan->id) {
                    $harga_jual = $penjualan->tipe_pembeli == 'eceran' ? $satuan->harga_jual_eceran : $satuan->harga_jual_borongan;
                }
            }

            // **Field Jumlah**
            $row['jumlah'] = '<input type="number" class="form-control input-sm quantity" data-id="' . $item->id_penjualan_detail . '" value="' . $item->jumlah . '">';
            $row['max'] = $item->produk->stok; // Stok utama produk

            // **Hitung Subtotal (Harga * Jumlah - Diskon)**
            $subtotal = ($harga_jual * $item->jumlah) * (1 - $item->diskon / 100);

            $row['diskon'] = $item->diskon . '%';
            $row['subtotal'] = 'Rp. ' . format_uang($subtotal);
            $row['aksi'] = '<div class="btn-group">
                            <button onclick="deleteData(`' . route('transaksi.destroy', $item->id_penjualan_detail) . '`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                        </div>';

            $data[] = $row;

            $total += $subtotal;
            $total_item += $item->jumlah;
        }

        // Tambahkan total transaksi ke dalam array data
        $data[] = [
            'kode_produk' => '<div class="total hide">' . $total . '</div><div class="total_item hide">' . $total_item . '</div>',
            'nama_produk' => '',
            'produk_satuan' => '',
            'harga_jual_eceran' => '',
            'jumlah' => '',
            'max' => '',
            'diskon' => '',
            'subtotal' => '',
            'aksi' => '',
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
            'id_penjualan' => 'required|exists:penjualan,id_penjualan',
            'id_produk' => 'required|exists:produk,id_produk',
            'id_produk_satuan' => 'nullable|exists:produk_satuan,id',
        ]);

        $penjualan = Penjualan::findOrFail($request->id_penjualan);
        if ($penjualan->status !== 0) {
            return response()->json(['message' => 'Transaksi telah selesai dan tidak dapat diubah.'], 400);
        }

        $produk = Produk::findOrFail($request->id_produk);

        $satuan = $request->id
            ? ProdukSatuan::find($request->id)
            : $produk->produkSatuan()->where('satuan', 'pcs')->first();

        if (!$satuan) {
            return response()->json(['message' => 'Satuan produk tidak ditemukan'], 400);
        }

        $detail = PenjualanDetail::where('id_penjualan', $request->id_penjualan)
            ->where('id_produk', $request->id_produk)
            ->first();

        if ($detail) {
            $newQuantity = $detail->jumlah + 1;
            if ($newQuantity > $produk->stok) {
                return response()->json(['message' => 'Jumlah melebihi stok yang tersedia'], 400);
            }

            $detail->jumlah = $newQuantity;
            $detail->subtotal = $satuan->harga_jual_eceran * $newQuantity;
            $detail->id_produk_satuan = $satuan->id;
            $detail->harga_jual_eceran = $satuan->harga_jual_eceran;
            $detail->update();
        } else {
            if (1 > $produk->stok) {
                return response()->json(['message' => 'Stok produk tidak mencukupi'], 400);
            }

            $detail = new PenjualanDetail();
            $detail->id_penjualan = $request->id_penjualan;
            $detail->id_produk = $produk->id_produk;
            $detail->id_produk_satuan = $satuan->id;
            $detail->harga_jual_eceran = $satuan->harga_jual_eceran;
            $detail->jumlah = 1;
            $detail->diskon = 0;
            $detail->subtotal = $satuan->harga_jual_eceran;
            $detail->save();
        }

        $produk->stok -= $detail->jumlah;
        $produk->save();

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'detail' => $detail,
        ], 200);
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
        $detail = PenjualanDetail::find($id);
        $produk = Produk::find($detail->id_produk);

        if ($request->jumlah > $produk->stok) {
            return response()->json(['message' => 'Jumlah melebihi stok yang tersedia'], 400);
        }

        $detail->jumlah = $request->jumlah;
        $detail->subtotal = $detail->harga_jual_eceran * $request->jumlah;
        $detail->save();

        return response()->json('Data berhasil diperbarui', 200);
    }


    public function destroy($id)
    {
        $detail = PenjualanDetail::find($id);
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
