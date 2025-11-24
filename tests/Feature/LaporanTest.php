<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create setting first
        \App\Models\Setting::factory()->create();

        // Create admin user for authentication
        $user = User::factory()->create();
        $user->level = 1; // Admin level
        $user->save();

        $this->actingAs($user);
    }

    public function test_admin_can_view_laporan_index()
    {
        $response = $this->get(route('laporan.index'));

        $response->assertStatus(200);
        $response->assertViewIs('laporan.index');
        $response->assertViewHas(['tanggalAwal', 'tanggalAkhir']);
    }

    public function test_laporan_index_sets_default_date_range()
    {
        $response = $this->get(route('laporan.index'));

        $response->assertStatus(200);

        $viewData = $response->getData();

        // Check that default dates are set (first day of current month to today)
        $expectedStart = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
        $expectedEnd = date('Y-m-d');

        $this->assertEquals($expectedStart, $viewData['tanggalAwal']);
        $this->assertEquals($expectedEnd, $viewData['tanggalAkhir']);
    }

    public function test_laporan_index_accepts_custom_date_range()
    {
        $customStart = '2024-01-01';
        $customEnd = '2024-01-31';

        $response = $this->get(route('laporan.index', [
            'tanggal_awal' => $customStart,
            'tanggal_akhir' => $customEnd
        ]));

        $response->assertStatus(200);

        $viewData = $response->getData();

        $this->assertEquals($customStart, $viewData['tanggalAwal']);
        $this->assertEquals($customEnd, $viewData['tanggalAkhir']);
    }

    public function test_admin_can_get_laporan_data()
    {
        // Create test data for a specific date
        $testDate = Carbon::now()->format('Y-m-d');

        Penjualan::factory()->create([
            'bayar' => 100000,
            'created_at' => $testDate . ' 10:00:00',
        ]);

        Pembelian::factory()->create([
            'bayar' => 50000,
            'created_at' => $testDate . ' 11:00:00',
        ]);

        Pengeluaran::factory()->create([
            'nominal' => 20000,
            'created_at' => $testDate . ' 12:00:00',
        ]);

        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');

        $response = $this->get(route('laporan.data', [$startDate, $endDate]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'DT_RowIndex',
                    'tanggal',
                    'penjualan',
                    'pembelian',
                    'pengeluaran',
                    'pendapatan'
                ]
            ]
        ]);
    }

    public function test_laporan_calculates_daily_profit_correctly()
    {
        $testDate = Carbon::now()->format('Y-m-d');

        // Create transactions for the test date
        Penjualan::factory()->create([
            'bayar' => 200000,
            'created_at' => $testDate . ' 10:00:00',
        ]);

        Pembelian::factory()->create([
            'bayar' => 80000,
            'created_at' => $testDate . ' 11:00:00',
        ]);

        Pengeluaran::factory()->create([
            'nominal' => 30000,
            'created_at' => $testDate . ' 12:00:00',
        ]);

        $startDate = $testDate;
        $endDate = $testDate;

        $response = $this->get(route('laporan.data', [$startDate, $endDate]));

        $response->assertStatus(200);

        $data = $response->json('data');

        // Should have one data row plus one total row
        $this->assertCount(2, $data);

        $dailyData = $data[0];

        // Check calculations: 200000 - 80000 - 30000 = 90000
        $this->assertEquals('Rp. 200.000', $dailyData['penjualan']);
        $this->assertEquals('Rp. 80.000', $dailyData['pembelian']);
        $this->assertEquals('Rp. 30.000', $dailyData['pengeluaran']);
        $this->assertEquals('Rp. 90.000', $dailyData['pendapatan']);
    }

    public function test_laporan_calculates_total_profit_correctly()
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');

        // Create multiple days of transactions
        $day1 = Carbon::now()->startOfMonth()->format('Y-m-d');
        $day2 = Carbon::now()->startOfMonth()->addDay()->format('Y-m-d');

        // Day 1: 100000 - 40000 - 10000 = 50000
        Penjualan::factory()->create(['bayar' => 100000, 'created_at' => $day1 . ' 10:00:00']);
        Pembelian::factory()->create(['bayar' => 40000, 'created_at' => $day1 . ' 11:00:00']);
        Pengeluaran::factory()->create(['nominal' => 10000, 'created_at' => $day1 . ' 12:00:00']);

        // Day 2: 150000 - 60000 - 20000 = 70000
        Penjualan::factory()->create(['bayar' => 150000, 'created_at' => $day2 . ' 10:00:00']);
        Pembelian::factory()->create(['bayar' => 60000, 'created_at' => $day2 . ' 11:00:00']);
        Pengeluaran::factory()->create(['nominal' => 20000, 'created_at' => $day2 . ' 12:00:00']);

        $response = $this->get(route('laporan.data', [$startDate, $endDate]));

        $response->assertStatus(200);

        $data = $response->json('data');

        // Find the total row (last row)
        $totalRow = end($data);

        // Total profit: 50000 + 70000 = 120000
        $this->assertEquals('Total Pendapatan', $totalRow['pengeluaran']);
        $this->assertEquals('Rp. 120.000', $totalRow['pendapatan']);
    }

    public function test_laporan_handles_date_range_with_no_transactions()
    {
        // Use a date range in the past with no transactions
        $startDate = '2020-01-01';
        $endDate = '2020-01-05';

        $response = $this->get(route('laporan.data', [$startDate, $endDate]));

        $response->assertStatus(200);

        $data = $response->json('data');

        // Should have 5 days of data (plus 1 total row = 6)
        $this->assertCount(6, $data);

        // Check that all daily profits are 0
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals('Rp. 0', $data[$i]['penjualan']);
            $this->assertEquals('Rp. 0', $data[$i]['pembelian']);
            $this->assertEquals('Rp. 0', $data[$i]['pengeluaran']);
            $this->assertEquals('Rp. 0', $data[$i]['pendapatan']);
        }

        // Total should also be 0
        $totalRow = end($data);
        $this->assertEquals('Rp. 0', $totalRow['pendapatan']);
    }

    public function test_laporan_handles_single_day_range()
    {
        $testDate = Carbon::now()->format('Y-m-d');

        Penjualan::factory()->create([
            'bayar' => 50000,
            'created_at' => $testDate . ' 10:00:00',
        ]);

        $response = $this->get(route('laporan.data', [$testDate, $testDate]));

        $response->assertStatus(200);

        $data = $response->json('data');

        // Should have 1 data row + 1 total row = 2 rows
        $this->assertCount(2, $data);

        $dailyData = $data[0];
        $this->assertEquals('Rp. 50.000', $dailyData['penjualan']);
        $this->assertEquals('Rp. 0', $dailyData['pembelian']);
        $this->assertEquals('Rp. 0', $dailyData['pengeluaran']);
        $this->assertEquals('Rp. 50.000', $dailyData['pendapatan']);
    }

    public function test_laporan_formats_dates_correctly()
    {
        $testDate = '2024-03-15'; // March 15, 2024

        Penjualan::factory()->create([
            'bayar' => 75000,
            'created_at' => $testDate . ' 10:00:00',
        ]);

        $response = $this->get(route('laporan.data', [$testDate, $testDate]));

        $response->assertStatus(200);

        $data = $response->json('data');
        $dailyData = $data[0];

        // Should format date as Indonesian format
        $this->assertStringContains('15', $dailyData['tanggal']); // Day should be present
        $this->assertStringContains('2024', $dailyData['tanggal']); // Year should be present
    }

    public function test_laporan_handles_negative_daily_profit()
    {
        $testDate = Carbon::now()->format('Y-m-d');

        // Create scenario with losses
        Penjualan::factory()->create([
            'bayar' => 30000,
            'created_at' => $testDate . ' 10:00:00',
        ]);

        Pembelian::factory()->create([
            'bayar' => 50000,
            'created_at' => $testDate . ' 11:00:00',
        ]);

        Pengeluaran::factory()->create([
            'nominal' => 20000,
            'created_at' => $testDate . ' 12:00:00',
        ]);

        $response = $this->get(route('laporan.data', [$testDate, $testDate]));

        $response->assertStatus(200);

        $data = $response->json('data');
        $dailyData = $data[0];

        // Loss calculation: 30000 - 50000 - 20000 = -40000
        $this->assertEquals('Rp. 30.000', $dailyData['penjualan']);
        $this->assertEquals('Rp. 50.000', $dailyData['pembelian']);
        $this->assertEquals('Rp. 20.000', $dailyData['pengeluaran']);
        $this->assertEquals('Rp. -40.000', $dailyData['pendapatan']);
    }

    public function test_laporan_export_pdf_returns_pdf()
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');

        // Create some test data
        Penjualan::factory()->create([
            'bayar' => 100000,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->get(route('laporan.exportPDF', [$startDate, $endDate]));

        $response->assertStatus(200);

        // Check if response is PDF
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContains('application/pdf', $contentType);

        // Check if filename contains expected pattern
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContains('Laporan-pendapatan-', $contentDisposition);
        $this->assertStringContains('.pdf', $contentDisposition);
    }

    public function test_laporan_data_includes_sequential_row_numbers()
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->endOfMonth()->addDays(2)->format('Y-m-d'); // 3 days

        $response = $this->get(route('laporan.data', [$startDate, $endDate]));

        $response->assertStatus(200);

        $data = $response->json('data');

        // Should have 3 data rows + 1 total row = 4 rows
        $this->assertCount(4, $data);

        // Check sequential numbering (excluding total row)
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($i + 1, $data[$i]['DT_RowIndex']);
        }

        // Total row should have empty DT_RowIndex
        $this->assertEquals('', end($data)['DT_RowIndex']);
    }

    public function test_laporan_handles_month_boundary_dates()
    {
        // Test with dates that span month boundaries
        $startDate = '2024-01-30';
        $endDate = '2024-02-02';

        // Create transaction at month boundary
        Penjualan::factory()->create([
            'bayar' => 25000,
            'created_at' => '2024-01-31 10:00:00',
        ]);

        Penjualan::factory()->create([
            'bayar' => 35000,
            'created_at' => '2024-02-01 10:00:00',
        ]);

        $response = $this->get(route('laporan.data', [$startDate, $endDate]));

        $response->assertStatus(200);

        $data = $response->json('data');

        // Should have 4 days of data (30, 31 Jan, 1, 2 Feb) + 1 total = 5 rows
        $this->assertCount(5, $data);

        // Check that data spans the month boundary correctly
        $this->assertStringContains('30', $data[0]['tanggal']); // Jan 30
        $this->assertStringContains('31', $data[1]['tanggal']); // Jan 31
        $this->assertStringContains('01', $data[2]['tanggal']); // Feb 1
        $this->assertStringContains('02', $data[3]['tanggal']); // Feb 2
    }

    public function test_laporan_aggregates_multiple_transactions_same_day()
    {
        $testDate = Carbon::now()->format('Y-m-d');

        // Create multiple transactions for the same day
        Penjualan::factory()->create(['bayar' => 50000, 'created_at' => $testDate . ' 09:00:00']);
        Penjualan::factory()->create(['bayar' => 30000, 'created_at' => $testDate . ' 10:00:00']);

        Pembelian::factory()->create(['bayar' => 20000, 'created_at' => $testDate . ' 11:00:00']);

        Pengeluaran::factory()->create(['nominal' => 10000, 'created_at' => $testDate . ' 12:00:00']);
        Pengeluaran::factory()->create(['nominal' => 5000, 'created_at' => $testDate . ' 13:00:00']);

        $response = $this->get(route('laporan.data', [$testDate, $testDate]));

        $response->assertStatus(200);

        $data = $response->json('data');
        $dailyData = $data[0];

        // Check aggregated values
        $this->assertEquals('Rp. 80.000', $dailyData['penjualan']); // 50000 + 30000
        $this->assertEquals('Rp. 20.000', $dailyData['pembelian']); // 20000
        $this->assertEquals('Rp. 15.000', $dailyData['pengeluaran']); // 10000 + 5000
        $this->assertEquals('Rp. 45.000', $dailyData['pendapatan']); // 80000 - 20000 - 15000
    }
}
