<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Member;
use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create setting first
        \App\Models\Setting::factory()->create();

        // Create admin user for authentication
        $adminUser = User::factory()->create();
        $adminUser->level = 1; // Admin level
        $adminUser->save();

        $this->actingAs($adminUser);
    }

    public function test_admin_can_view_dashboard_with_correct_data()
    {
        // Create test data
        Kategori::factory()->count(5)->create();
        Produk::factory()->count(10)->create();
        Supplier::factory()->count(3)->create();
        Member::factory()->count(8)->create();

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas([
            'kategori',
            'produk',
            'supplier',
            'member',
            'data_tanggal',
            'data_pendapatan',
            'tanggal_awal',
            'tanggal_akhir'
        ]);

        // Check counts are correct
        $viewData = $response->viewData();
        $this->assertEquals(5, $viewData['kategori']);
        $this->assertEquals(10, $viewData['produk']);
        $this->assertEquals(3, $viewData['supplier']);
        $this->assertEquals(8, $viewData['member']);
    }

    public function test_dashboard_shows_correct_financial_data_for_current_month()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Create transactions for current month
        $penjualan = Penjualan::factory()->create([
            'bayar' => 100000,
            'created_at' => Carbon::now(),
        ]);

        $pembelian = Pembelian::factory()->create([
            'bayar' => 50000,
            'created_at' => Carbon::now(),
        ]);

        $pengeluaran = Pengeluaran::factory()->create([
            'nominal' => 20000,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);

        $viewData = $response->viewData();

        // Check that financial data arrays are populated
        $this->assertIsArray($viewData['data_tanggal']);
        $this->assertIsArray($viewData['data_pendapatan']);

        // Check date range
        $this->assertInstanceOf(Carbon::class, $viewData['tanggal_awal']);
        $this->assertInstanceOf(Carbon::class, $viewData['tanggal_akhir']);

        // Check that current month data is included
        $currentDay = (int) Carbon::now()->format('d');
        $this->assertContains($currentDay, $viewData['data_tanggal']);
    }

    public function test_dashboard_calculates_profit_correctly()
    {
        // Create transactions for a specific date
        $testDate = Carbon::now()->startOfMonth()->addDays(5);

        Penjualan::factory()->create([
            'bayar' => 200000,
            'created_at' => $testDate,
        ]);

        Pembelian::factory()->create([
            'bayar' => 80000,
            'created_at' => $testDate,
        ]);

        Pengeluaran::factory()->create([
            'nominal' => 30000,
            'created_at' => $testDate,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);

        $viewData = $response->viewData();

        // Profit calculation: 200000 - 80000 - 30000 = 90000
        $expectedProfit = 200000 - 80000 - 30000;

        // Find the profit for the test date
        $testDay = (int) $testDate->format('d');
        $dayIndex = array_search($testDay, $viewData['data_tanggal']);

        if ($dayIndex !== false) {
            $this->assertEquals($expectedProfit, $viewData['data_pendapatan'][$dayIndex]);
        }
    }

    public function test_dashboard_filters_data_by_selected_month_and_year()
    {
        $selectedMonth = 6; // June
        $selectedYear = 2023;

        // Create data for selected month
        Penjualan::factory()->create([
            'bayar' => 150000,
            'created_at' => Carbon::create($selectedYear, $selectedMonth, 15),
        ]);

        // Create data for different month (should not appear)
        Penjualan::factory()->create([
            'bayar' => 50000,
            'created_at' => Carbon::create($selectedYear, 7, 15), // July
        ]);

        $response = $this->get(route('dashboard', [
            'bulan' => $selectedMonth,
            'tahun' => $selectedYear
        ]));

        $response->assertStatus(200);

        $viewData = $response->viewData();

        // Check date range is for selected month
        $this->assertEquals($selectedMonth, $viewData['tanggal_awal']->month);
        $this->assertEquals($selectedYear, $viewData['tanggal_awal']->year);
        $this->assertEquals($selectedMonth, $viewData['tanggal_akhir']->month);
        $this->assertEquals($selectedYear, $viewData['tanggal_akhir']->year);
    }

    public function test_dashboard_handles_empty_data_gracefully()
    {
        // No transactions created

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);

        $viewData = $response->viewData();

        // Should still have arrays, even if empty
        $this->assertIsArray($viewData['data_tanggal']);
        $this->assertIsArray($viewData['data_pendapatan']);

        // All profit values should be 0
        foreach ($viewData['data_pendapatan'] as $profit) {
            $this->assertEquals(0, $profit);
        }
    }

    public function test_dashboard_shows_data_for_complete_month()
    {
        $testMonth = 3; // March
        $testYear = 2024;

        // March 2024 has 31 days
        $response = $this->get(route('dashboard', [
            'bulan' => $testMonth,
            'tahun' => $testYear
        ]));

        $response->assertStatus(200);

        $viewData = $response->viewData();

        // Should have 31 days of data
        $this->assertCount(31, $viewData['data_tanggal']);
        $this->assertCount(31, $viewData['data_pendapatan']);

        // Check date range
        $this->assertEquals(1, $viewData['tanggal_awal']->day);
        $this->assertEquals(31, $viewData['tanggal_akhir']->day);
        $this->assertEquals($testMonth, $viewData['tanggal_awal']->month);
        $this->assertEquals($testYear, $viewData['tanggal_awal']->year);
    }

    public function test_dashboard_handles_february_leap_year()
    {
        $testMonth = 2; // February
        $testYear = 2024; // Leap year

        // February 2024 has 29 days (leap year)
        $response = $this->get(route('dashboard', [
            'bulan' => $testMonth,
            'tahun' => $testYear
        ]));

        $response->assertStatus(200);

        $viewData = $response->viewData();

        // Should have 29 days of data for leap year February
        $this->assertCount(29, $viewData['data_tanggal']);
        $this->assertCount(29, $viewData['data_pendapatan']);
    }

    public function test_dashboard_handles_february_non_leap_year()
    {
        $testMonth = 2; // February
        $testYear = 2023; // Non-leap year

        // February 2023 has 28 days
        $response = $this->get(route('dashboard', [
            'bulan' => $testMonth,
            'tahun' => $testYear
        ]));

        $response->assertStatus(200);

        $viewData = $response->viewData();

        // Should have 28 days of data for non-leap year February
        $this->assertCount(28, $viewData['data_tanggal']);
        $this->assertCount(28, $viewData['data_pendapatan']);
    }

    public function test_dashboard_calculates_negative_profit_correctly()
    {
        // Create scenario with losses
        $testDate = Carbon::now();

        Penjualan::factory()->create([
            'bayar' => 50000,
            'created_at' => $testDate,
        ]);

        Pembelian::factory()->create([
            'bayar' => 80000,
            'created_at' => $testDate,
        ]);

        Pengeluaran::factory()->create([
            'nominal' => 20000,
            'created_at' => $testDate,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);

        $viewData = $response->viewData();

        // Loss calculation: 50000 - 80000 - 20000 = -50000
        $expectedLoss = 50000 - 80000 - 20000;

        // Find the loss for the test date
        $testDay = (int) $testDate->format('d');
        $dayIndex = array_search($testDay, $viewData['data_tanggal']);

        if ($dayIndex !== false) {
            $this->assertEquals($expectedLoss, $viewData['data_pendapatan'][$dayIndex]);
        }
    }

    public function test_kasir_user_sees_different_dashboard()
    {
        // Create kasir user (level 2)
        $kasirUser = User::factory()->create();
        $kasirUser->level = 2; // Kasir level
        $kasirUser->save();

        $this->actingAs($kasirUser);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('kasir.dashboard');
    }

    public function test_dashboard_aggregates_multiple_transactions_per_day()
    {
        $testDate = Carbon::now();

        // Create multiple transactions for the same day
        Penjualan::factory()->create(['bayar' => 100000, 'created_at' => $testDate]);
        Penjualan::factory()->create(['bayar' => 50000, 'created_at' => $testDate]);

        Pembelian::factory()->create(['bayar' => 30000, 'created_at' => $testDate]);
        Pengeluaran::factory()->create(['nominal' => 10000, 'created_at' => $testDate]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);

        $viewData = $response->viewData();

        // Total profit: (100000 + 50000) - 30000 - 10000 = 120000
        $expectedTotalProfit = (100000 + 50000) - 30000 - 10000;

        $testDay = (int) $testDate->format('d');
        $dayIndex = array_search($testDay, $viewData['data_tanggal']);

        if ($dayIndex !== false) {
            $this->assertEquals($expectedTotalProfit, $viewData['data_pendapatan'][$dayIndex]);
        }
    }

    public function test_dashboard_date_range_includes_all_days_of_month()
    {
        $testMonth = 5; // May
        $testYear = 2024;

        $response = $this->get(route('dashboard', [
            'bulan' => $testMonth,
            'tahun' => $testYear
        ]));

        $response->assertStatus(200);

        $viewData = $response->viewData();

        // May has 31 days
        $this->assertCount(31, $viewData['data_tanggal']);

        // Check that dates are consecutive from 1 to 31
        for ($i = 1; $i <= 31; $i++) {
            $this->assertContains($i, $viewData['data_tanggal']);
        }
    }
}
