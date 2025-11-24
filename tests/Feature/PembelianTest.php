<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembelianTest extends TestCase
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

    public function test_admin_can_view_pembelian_index()
    {
        Supplier::factory()->count(3)->create();

        $response = $this->get(route('pembelian.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pembelian.index');
        $response->assertViewHas('supplier');
    }

    public function test_admin_can_get_pembelian_data()
    {
        $supplier = Supplier::factory()->create();
        Pembelian::factory()->count(3)->create(['id_supplier' => $supplier->id_supplier]);

        $response = $this->get(route('pembelian.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'DT_RowIndex',
                    'total_item',
                    'total_harga',
                    'bayar',
                    'tanggal',
                    'supplier',
                    'diskon',
                    'aksi'
                ]
            ]
        ]);
    }

    public function test_admin_can_create_new_pembelian()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->get(route('pembelian.create', $supplier->id_supplier));

        $response->assertRedirect(route('pembelian_detail.index'));

        // Check if pembelian was created with correct initial values
        $this->assertDatabaseHas('pembelian', [
            'id_supplier' => $supplier->id_supplier,
            'total_item' => 0,
            'total_harga' => 0,
            'diskon' => 0,
            'bayar' => 0,
        ]);
    }

    public function test_admin_can_store_pembelian_with_details()
    {
        $supplier = Supplier::factory()->create();
        $produk = Produk::factory()->create(['harga_beli' => 10000, 'stok' => 10]);

        // Create pembelian
        $pembelian = Pembelian::factory()->create([
            'id_supplier' => $supplier->id_supplier,
            'total_item' => 0,
            'total_harga' => 0,
            'diskon' => 0,
            'bayar' => 0,
        ]);

        // Create pembelian detail
        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
            'harga_beli' => 10000,
            'jumlah' => 5,
            'subtotal' => 50000,
        ]);

        $updateData = [
            'id_pembelian' => $pembelian->id_pembelian,
            'total_item' => 5,
            'total' => 50000,
            'diskon' => 10,
            'bayar' => 45000,
        ];

        $response = $this->post(route('pembelian.store'), $updateData);

        $response->assertRedirect(route('pembelian.index'));

        // Check pembelian was updated
        $this->assertDatabaseHas('pembelian', [
            'id_pembelian' => $pembelian->id_pembelian,
            'total_item' => 5,
            'total_harga' => 50000,
            'diskon' => 10,
            'bayar' => 45000,
        ]);

        // Check product stock was increased
        $updatedProduk = Produk::find($produk->id_produk);
        $this->assertEquals(15, $updatedProduk->stok); // 10 + 5
    }

    public function test_admin_can_view_pembelian_detail()
    {
        $supplier = Supplier::factory()->create();
        $produk = Produk::factory()->create(['harga_beli' => 15000]);

        $pembelian = Pembelian::factory()->create(['id_supplier' => $supplier->id_supplier]);

        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
            'harga_beli' => 15000,
            'jumlah' => 3,
            'subtotal' => 45000,
        ]);

        $response = $this->get(route('pembelian.show', $pembelian->id_pembelian));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'DT_RowIndex',
                    'kode_produk',
                    'nama_produk',
                    'harga_beli',
                    'jumlah',
                    'subtotal'
                ]
            ]
        ]);
    }

    public function test_admin_can_delete_pembelian_and_restore_stock()
    {
        $supplier = Supplier::factory()->create();
        $produk = Produk::factory()->create(['harga_beli' => 20000, 'stok' => 20]);

        $pembelian = Pembelian::factory()->create(['id_supplier' => $supplier->id_supplier]);

        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
            'harga_beli' => 20000,
            'jumlah' => 8,
            'subtotal' => 160000,
        ]);

        // First add stock through store method
        $this->post(route('pembelian.store'), [
            'id_pembelian' => $pembelian->id_pembelian,
            'total_item' => 8,
            'total' => 160000,
            'diskon' => 0,
            'bayar' => 160000,
        ]);

        // Verify stock was increased
        $produkAfterStore = Produk::find($produk->id_produk);
        $this->assertEquals(28, $produkAfterStore->stok); // 20 + 8

        // Now delete pembelian
        $response = $this->delete(route('pembelian.destroy', $pembelian->id_pembelian));

        $response->assertStatus(204);

        // Check pembelian was deleted
        $this->assertDatabaseMissing('pembelian', ['id_pembelian' => $pembelian->id_pembelian]);

        // Check pembelian details were deleted
        $this->assertDatabaseMissing('pembelian_detail', ['id_pembelian' => $pembelian->id_pembelian]);

        // Check product stock was restored
        $produkAfterDelete = Produk::find($produk->id_produk);
        $this->assertEquals(20, $produkAfterDelete->stok); // Back to original 20
    }

    public function test_pembelian_with_discount_calculation()
    {
        $supplier = Supplier::factory()->create();
        $produk = Produk::factory()->create(['harga_beli' => 25000, 'stok' => 5]);

        $pembelian = Pembelian::factory()->create(['id_supplier' => $supplier->id_supplier]);

        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id_produk,
            'harga_beli' => 25000,
            'jumlah' => 4,
            'subtotal' => 100000,
        ]);

        $updateData = [
            'id_pembelian' => $pembelian->id_pembelian,
            'total_item' => 4,
            'total' => 100000,
            'diskon' => 15, // 15% discount
            'bayar' => 85000, // 100000 - (15% of 100000) = 85000
        ];

        $this->post(route('pembelian.store'), $updateData);

        $this->assertDatabaseHas('pembelian', [
            'id_pembelian' => $pembelian->id_pembelian,
            'total_item' => 4,
            'total_harga' => 100000,
            'diskon' => 15,
            'bayar' => 85000,
        ]);

        // Check stock was updated
        $updatedProduk = Produk::find($produk->id_produk);
        $this->assertEquals(9, $updatedProduk->stok); // 5 + 4
    }

    public function test_pembelian_with_multiple_products()
    {
        $supplier = Supplier::factory()->create();
        $produk1 = Produk::factory()->create(['harga_beli' => 10000, 'stok' => 10]);
        $produk2 = Produk::factory()->create(['harga_beli' => 20000, 'stok' => 5]);

        $pembelian = Pembelian::factory()->create(['id_supplier' => $supplier->id_supplier]);

        // Add first product
        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk1->id_produk,
            'harga_beli' => 10000,
            'jumlah' => 3,
            'subtotal' => 30000,
        ]);

        // Add second product
        PembelianDetail::factory()->create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk2->id_produk,
            'harga_beli' => 20000,
            'jumlah' => 2,
            'subtotal' => 40000,
        ]);

        $updateData = [
            'id_pembelian' => $pembelian->id_pembelian,
            'total_item' => 5, // 3 + 2
            'total' => 70000, // 30000 + 40000
            'diskon' => 5,
            'bayar' => 66500, // 70000 - (5% of 70000) = 66500
        ];

        $this->post(route('pembelian.store'), $updateData);

        $this->assertDatabaseHas('pembelian', [
            'id_pembelian' => $pembelian->id_pembelian,
            'total_item' => 5,
            'total_harga' => 70000,
            'diskon' => 5,
            'bayar' => 66500,
        ]);

        // Check both products' stock were updated
        $updatedProduk1 = Produk::find($produk1->id_produk);
        $this->assertEquals(13, $updatedProduk1->stok); // 10 + 3

        $updatedProduk2 = Produk::find($produk2->id_produk);
        $this->assertEquals(7, $updatedProduk2->stok); // 5 + 2
    }

    public function test_pembelian_data_shows_correct_supplier_name()
    {
        $supplier1 = Supplier::factory()->create(['nama' => 'Supplier A']);
        $supplier2 = Supplier::factory()->create(['nama' => 'Supplier B']);

        Pembelian::factory()->create(['id_supplier' => $supplier1->id_supplier]);
        Pembelian::factory()->create(['id_supplier' => $supplier2->id_supplier]);

        $response = $this->get(route('pembelian.data'));

        $response->assertStatus(200);

        $data = $response->json('data');
        $supplierNames = array_column($data, 'supplier');

        $this->assertContains('Supplier A', $supplierNames);
        $this->assertContains('Supplier B', $supplierNames);
    }

    public function test_pembelian_data_formats_currency_correctly()
    {
        $supplier = Supplier::factory()->create();

        Pembelian::factory()->create([
            'id_supplier' => $supplier->id_supplier,
            'total_item' => 1000,
            'total_harga' => 5000000,
            'bayar' => 4500000,
            'diskon' => 10,
        ]);

        $response = $this->get(route('pembelian.data'));

        $response->assertStatus(200);

        $data = $response->json('data');
        $firstRecord = $data[0];

        // Check currency formatting
        $this->assertStringContains('Rp. ', $firstRecord['total_harga']);
        $this->assertStringContains('Rp. ', $firstRecord['bayar']);
        $this->assertStringContains('%', $firstRecord['diskon']);
    }
}
