<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Member;
use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    
    public function index(Request $request)
    {
        // dd(auth()->check(), auth()->user());

        $kategori = Kategori::count();
        $produk = Produk::count();
        $supplier = Supplier::count();
        $member = Member::count();

        $bulan = $request->get('bulan', date('n'));
        $tahun = $request->get('tahun', date('Y'));

        $tanggal_awal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $tanggal_akhir = Carbon::create($tahun, $bulan, 1)->endOfMonth();

        // Ambil semua transaksi dalam range tanggal
        $penjualan = Penjualan::whereBetween('created_at', [$tanggal_awal, $tanggal_akhir])->get();
        $pembelian = Pembelian::whereBetween('created_at', [$tanggal_awal, $tanggal_akhir])->get();
        $pengeluaran = Pengeluaran::whereBetween('created_at', [$tanggal_awal, $tanggal_akhir])->get();

        $data_tanggal = [];
        $data_pendapatan = [];

        while ($tanggal_awal->lte($tanggal_akhir)) {
            $tanggal = $tanggal_awal->copy()->format('Y-m-d');
            $hari = (int) $tanggal_awal->format('d');
            $data_tanggal[] = $hari;

            // Filter data berdasarkan tanggal
            $total_penjualan = $penjualan->where('created_at', '>=', $tanggal . ' 00:00:00')
                ->where('created_at', '<=', $tanggal . ' 23:59:59')
                ->sum('bayar');

            $total_pembelian = $pembelian->where('created_at', '>=', $tanggal . ' 00:00:00')
                ->where('created_at', '<=', $tanggal . ' 23:59:59')
                ->sum('bayar');

            $total_pengeluaran = $pengeluaran->where('created_at', '>=', $tanggal . ' 00:00:00')
                ->where('created_at', '<=', $tanggal . ' 23:59:59')
                ->sum('nominal');

            $pendapatan = $total_penjualan - $total_pembelian - $total_pengeluaran;
            $data_pendapatan[] = $pendapatan;

            $tanggal_awal->addDay();
        }

        if (auth()->user()->level == 1) {
            return view('admin.dashboard', compact(
                'kategori',
                'produk',
                'supplier',
                'member',
                'data_tanggal',
                'data_pendapatan',
                'tanggal_awal',
                'tanggal_akhir',
            ));
        } else {
            return view('kasir.dashboard');
        }
    }
}
