<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembelianDetailTest extends TestCase
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

    public function test_admin_can_view_pembelian_detail_index()
    {
        $supplier = Supplier::factory()->create();
        $produk = Produk::factory()->create();

        // Create pembelian and set session
        $pembelian = Pembelian::factory()->create([
            'id_supplier' => $supplier->id_supplier,
            'total_item' => 0,
            'total_harga' => 0,
            'diskon' => 5,
            'bayar' => 0,
        ]);

        // Simulate session data
        session(['id_pembelian' => $pembelian->id_pembelian]);
        session(['id_supplier' => $supplier->id_supplier]);

        $response = $this->get(route('pembelian_detail.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pembelian_detail.index');
        $response->assertViewHas(['id_pembelian', 'produk', 'supplier', 'diskon']);
    }

    public function test_pembelian_detail_index_requires_valid_supplier_session()
    {
        // Set invalid supplier session
        session(['id_pembelian' => 1]);
        session(['id_supplier' => 999]); // Non-existent supplier

        $response = $this->get(route('pembelian_detail.index'));

        $response->assertStatus(404);
    }

    public function test_admin_can_get_pembelian_detail_data()
    {
        $supplier = Supplier::factory()->create();
        $produk = Produk::factory()->create(['harga_beli' => 25000]);

        $pembelian = Pembelian::factory()->create(['id_supplier' => $supplier->id_supplier]);

        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
            'harga_beli' => 25000,
            'jumlah' => 3,
            'subtotal' => 75000,
        ]);

        $response = $this->get(route('pembelian_detail.data', $pembelian->id_pembelian));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'DT_RowIndex',
                    'kode_produk',
                    'nama_produk',
                    'harga_beli',
                    'jumlah',
                    'subtotal',
                    'aksi'
                ]
            ]
        ]);
    }

    public function test_admin_can_add_product_to_pembelian_detail()
    {
        $produk = Produk::factory()->create(['harga_beli' => 30000]);
        $pembelian = Pembelian::factory()->create();

        $requestData = [
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
        ];

        $response = $this->post(route('pembelian_detail.store'), $requestData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        // Check pembelian detail was created
        $this->assertDatabaseHas('pembelian_detail', [
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
            'harga_beli' => 30000,
            'jumlah' => 1,
            'subtotal' => 30000,
        ]);
    }

    public function test_cannot_add_nonexistent_product_to_pembelian_detail()
    {
        $pembelian = Pembelian::factory()->create();

        $requestData = [
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => 999, // Non-existent product
        ];

        $response = $this->post(route('pembelian_detail.store'), $requestData);

        $response->assertStatus(400);
        $response->assertSee('Data gagal disimpan');
    }

    public function test_admin_can_update_pembelian_detail_quantity()
    {
        $produk = Produk::factory()->create(['harga_beli' => 20000]);
        $pembelian = Pembelian::factory()->create();

        $detail = PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
            'harga_beli' => 20000,
            'jumlah' => 1,
            'subtotal' => 20000,
        ]);

        $updateData = [
            'jumlah' => 5,
        ];

        $response = $this->put(route('pembelian_detail.update', $detail->id_pembelian_detail), $updateData);

        $response->assertStatus(200);

        // Check quantity and subtotal were updated
        $this->assertDatabaseHas('pembelian_detail', [
            'id_pembelian_detail' => $detail->id_pembelian_detail,
            'jumlah' => 5,
            'subtotal' => 100000, // 20000 * 5
        ]);
    }

    public function test_admin_can_delete_pembelian_detail()
    {
        $produk = Produk::factory()->create();
        $pembelian = Pembelian::factory()->create();

        $detail = PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
        ]);

        $response = $this->delete(route('pembelian_detail.destroy', $detail->id_pembelian_detail));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('pembelian_detail', [
            'id_pembelian_detail' => $detail->id_pembelian_detail,
        ]);
    }

    public function test_pembelian_detail_data_includes_total_calculation()
    {
        $supplier = Supplier::factory()->create();
        $produk1 = Produk::factory()->create(['harga_beli' => 15000]);
        $produk2 = Produk::factory()->create(['harga_beli' => 25000]);

        $pembelian = Pembelian::factory()->create(['id_supplier' => $supplier->id_supplier]);

        // Add first product
        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk1->id_produk,
            'harga_beli' => 15000,
            'jumlah' => 2,
            'subtotal' => 30000,
        ]);

        // Add second product
        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk2->id_produk,
            'harga_beli' => 25000,
            'jumlah' => 3,
            'subtotal' => 75000,
        ]);

        $response = $this->get(route('pembelian_detail.data', $pembelian->id_pembelian));

        $response->assertStatus(200);

        $data = $response->json('data');

        // Check that last row contains total calculation
        $lastRow = end($data);
        $this->assertStringContains('105000', $lastRow['kode_produk']); // Total: 30000 + 75000 = 105000
        $this->assertStringContains('5', $lastRow['nama_produk']); // Total items: 2 + 3 = 5
    }

    public function test_pembelian_detail_data_formats_currency_correctly()
    {
        $supplier = Supplier::factory()->create();
        $produk = Produk::factory()->create(['harga_beli' => 35000]);

        $pembelian = Pembelian::factory()->create(['id_supplier' => $supplier->id_supplier]);

        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
            'harga_beli' => 35000,
            'jumlah' => 4,
            'subtotal' => 140000,
        ]);

        $response = $this->get(route('pembelian_detail.data', $pembelian->id_pembelian));

        $response->assertStatus(200);

        $data = $response->json('data');
        $firstRow = $data[0];

        // Check currency formatting
        $this->assertStringContains('Rp. ', $firstRow['harga_beli']);
        $this->assertStringContains('Rp. ', $firstRow['subtotal']);
    }

    public function test_load_form_calculates_discount_correctly()
    {
        $response = $this->get(route('pembelian_detail.loadForm', ['diskon' => 10, 'total' => 100000]));

        $response->assertStatus(200);

        $data = $response->json();

        // Expected: 100000 - (10% of 100000) = 90000
        $this->assertEquals(90000, $data['bayar']);
        $this->assertEquals('Rp. 100.000', $data['totalrp']);
        $this->assertEquals('Rp. 90.000', $data['bayarrp']);
        $this->assertStringContains('Rupiah', $data['terbilang']);
    }

    public function test_load_form_with_zero_discount()
    {
        $response = $this->get(route('pembelian_detail.loadForm', ['diskon' => 0, 'total' => 50000]));

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertEquals(50000, $data['bayar']);
        $this->assertEquals('Rp. 50.000', $data['totalrp']);
        $this->assertEquals('Rp. 50.000', $data['bayarrp']);
    }

    public function test_load_form_with_high_discount()
    {
        $response = $this->get(route('pembelian_detail.loadForm', ['diskon' => 50, 'total' => 200000]));

        $response->assertStatus(200);

        $data = $response->json();

        // Expected: 200000 - (50% of 200000) = 100000
        $this->assertEquals(100000, $data['bayar']);
        $this->assertEquals('Rp. 200.000', $data['totalrp']);
        $this->assertEquals('Rp. 100.000', $data['bayarrp']);
    }

    public function test_pembelian_detail_data_includes_product_codes_and_names()
    {
        $supplier = Supplier::factory()->create();
        $produk = Produk::factory()->create([
            'kode_produk' => 'PRD001',
            'nama_produk' => 'Test Product',
            'harga_beli' => 18000
        ]);

        $pembelian = Pembelian::factory()->create(['id_supplier' => $supplier->id_supplier]);

        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
            'harga_beli' => 18000,
            'jumlah' => 2,
            'subtotal' => 36000,
        ]);

        $response = $this->get(route('pembelian_detail.data', $pembelian->id_pembelian));

        $response->assertStatus(200);

        $data = $response->json('data');
        $firstRow = $data[0];

        $this->assertStringContains('PRD001', $firstRow['kode_produk']);
        $this->assertEquals('Test Product', $firstRow['nama_produk']);
    }

    public function test_pembelian_detail_quantity_input_field()
    {
        $supplier = Supplier::factory()->create();
        $produk = Produk::factory()->create(['harga_beli' => 12000]);

        $pembelian = Pembelian::factory()->create(['id_supplier' => $supplier->id_supplier]);

        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
            'harga_beli' => 12000,
            'jumlah' => 7,
            'subtotal' => 84000,
        ]);

        $response = $this->get(route('pembelian_detail.data', $pembelian->id_pembelian));

        $response->assertStatus(200);

        $data = $response->json('data');
        $firstRow = $data[0];

        // Check that quantity field contains input with correct value and data-id
        $this->assertStringContains('type="number"', $firstRow['jumlah']);
        $this->assertStringContains('class="form-control input-sm quantity"', $firstRow['jumlah']);
        $this->assertStringContains('value="7"', $firstRow['jumlah']);
    }
}
