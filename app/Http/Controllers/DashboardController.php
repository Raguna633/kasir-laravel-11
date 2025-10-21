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
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    
    public function index(Request $request): \Illuminate\View\View
    {
        $kategori = Kategori::count();
        $produk = Produk::count();
        $supplier = Supplier::count();
        $member = Member::count();

        $bulan = $request->get('bulan', date('n'));
        $tahun = $request->get('tahun', date('Y'));

        $tanggal_awal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $tanggal_akhir = Carbon::create($tahun, $bulan, 1)->endOfMonth();

        // Optimize queries to avoid N+1 problems
        $penjualanData = Penjualan::whereBetween('created_at', [$tanggal_awal, $tanggal_akhir])
            ->selectRaw('DATE(created_at) as date, SUM(bayar) as total_bayar')
            ->groupBy('date')
            ->pluck('total_bayar', 'date');

        $pembelianData = Pembelian::whereBetween('created_at', [$tanggal_awal, $tanggal_akhir])
            ->selectRaw('DATE(created_at) as date, SUM(bayar) as total_bayar')
            ->groupBy('date')
            ->pluck('total_bayar', 'date');

        $pengeluaranData = Pengeluaran::whereBetween('created_at', [$tanggal_awal, $tanggal_akhir])
            ->selectRaw('DATE(created_at) as date, SUM(nominal) as total_nominal')
            ->groupBy('date')
            ->pluck('total_nominal', 'date');

        $data_tanggal = [];
        $data_pendapatan = [];

        $currentDate = $tanggal_awal->copy();
        while ($currentDate->lte($tanggal_akhir)) {
            $dateString = $currentDate->format('Y-m-d');
            $hari = (int) $currentDate->format('d');
            $data_tanggal[] = $hari;

            $total_penjualan = $penjualanData->get($dateString, 0);
            $total_pembelian = $pembelianData->get($dateString, 0);
            $total_pengeluaran = $pengeluaranData->get($dateString, 0);

            $pendapatan = $total_penjualan - $total_pembelian - $total_pengeluaran;
            $data_pendapatan[] = $pendapatan;

            $currentDate->addDay();
        }

        switch (Auth::user()->level) {
            case 1:
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
            default:
                return view('kasir.dashboard');
        }
    }
}
