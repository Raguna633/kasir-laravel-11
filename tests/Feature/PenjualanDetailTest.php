<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use App\Models\SatuanProduk;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenjualanDetailTest extends TestCase
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

    public function test_admin_can_view_penjualan_detail_index()
    {
        $produk = Produk::factory()->create();
        $member = Member::factory()->create();

        // Create penjualan and set session
        $penjualan = Penjualan::factory()->create([
            'status' => Penjualan::STATUS_DRAFT,
            'tipe_pembeli' => 'eceran',
        ]);

        // Set session data
        session(['id_penjualan' => $penjualan->id_penjualan]);

        $response = $this->get(route('transaksi.index'));

        $response->assertStatus(200);
        $response->assertViewIs('penjualan_detail.index');
        $response->assertViewHas(['produk', 'member', 'diskon', 'id_penjualan', 'penjualan', 'memberSelected', 'drafts']);
    }

    public function test_penjualan_detail_index_redirects_without_session()
    {
        $response = $this->get(route('transaksi.index'));

        $response->assertRedirect(route('transaksi.baru'));
    }

    public function test_admin_can_get_penjualan_detail_data()
    {
        $produk = Produk::factory()->create(['stok' => 10]);
        $satuanProduk = SatuanProduk::factory()->create(['nama' => 'Pack']);
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 25000,
            'harga_jual_borongan' => 23000,
        ]);

        $penjualan = Penjualan::factory()->create(['tipe_pembeli' => 'eceran']);

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 25000,
            'jumlah' => 2,
            'diskon' => 5,
            'subtotal' => 47500, // (25000 * 2) * (1 - 5/100) = 47500
        ]);

        $response = $this->get(route('transaksi.data', $penjualan->id_penjualan));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'DT_RowIndex',
                    'kode_produk',
                    'nama_produk',
                    'produk_satuan',
                    'jumlah',
                    'max',
                    'diskon',
                    'subtotal',
                    'aksi'
                ]
            ]
        ]);
    }

    public function test_admin_can_add_product_to_penjualan_detail()
    {
        $produk = Produk::factory()->create(['stok' => 15]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 30000,
        ]);

        $penjualan = Penjualan::factory()->create(['status' => Penjualan::STATUS_DRAFT]);

        $storeData = [
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
        ];

        $response = $this->post(route('transaksi.store'), $storeData);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Data berhasil disimpan']);

        // Check penjualan detail was created
        $this->assertDatabaseHas('penjualan_detail', [
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 30000,
            'jumlah' => 1,
            'diskon' => 0,
            'subtotal' => 30000,
        ]);

        // Check product stock was decreased
        $updatedProduk = Produk::find($produk->id_produk);
        $this->assertEquals(14, $updatedProduk->stok); // 15 - 1
    }

    public function test_cannot_add_product_exceeding_stock()
    {
        $produk = Produk::factory()->create(['stok' => 3]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 20000,
        ]);

        $penjualan = Penjualan::factory()->create(['status' => Penjualan::STATUS_DRAFT]);

        $storeData = [
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
        ];

        // Add first item
        $this->post(route('transaksi.store'), $storeData);
        // Try to add second item (should fail due to stock limit)
        $response = $this->post(route('transaksi.store'), $storeData);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Jumlah melebihi stok yang tersedia']);
    }

    public function test_cannot_add_product_to_finalized_transaction()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $penjualan = Penjualan::factory()->create(['status' => Penjualan::STATUS_FINAL]);

        $storeData = [
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
        ];

        $response = $this->post(route('transaksi.store'), $storeData);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Transaksi telah selesai dan tidak dapat diubah.']);
    }

    public function test_admin_can_update_penjualan_detail_quantity()
    {
        $produk = Produk::factory()->create(['stok' => 20]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 15000,
        ]);

        $penjualan = Penjualan::factory()->create(['tipe_pembeli' => 'eceran']);

        $detail = PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 15000,
            'jumlah' => 1,
            'diskon' => 0,
            'subtotal' => 15000,
        ]);

        $updateData = [
            'jumlah' => 3.5, // Decimal quantity
        ];

        $response = $this->put(route('transaksi.update', $detail->id_penjualan_detail), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil diperbarui');

        // Check quantity and subtotal were updated
        $this->assertDatabaseHas('penjualan_detail', [
            'id_penjualan_detail' => $detail->id_penjualan_detail,
            'jumlah' => 3.5,
            'subtotal' => 52500, // 15000 * 3.5
        ]);

        // Check stock was adjusted correctly
        $updatedProduk = Produk::find($produk->id_produk);
        $this->assertEquals(16.5, $updatedProduk->stok); // 20 - 3.5
    }

    public function test_cannot_update_quantity_to_zero_or_negative()
    {
        $produk = Produk::factory()->create(['stok' => 10]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $penjualan = Penjualan::factory()->create();

        $detail = PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
        ]);

        $updateData = [
            'jumlah' => 0,
        ];

        $response = $this->put(route('transaksi.update', $detail->id_penjualan_detail), $updateData);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Jumlah harus lebih besar dari 0']);
    }

    public function test_cannot_update_quantity_exceeding_available_stock()
    {
        $produk = Produk::factory()->create(['stok' => 5]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $penjualan = Penjualan::factory()->create();

        $detail = PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'jumlah' => 1,
        ]);

        $updateData = [
            'jumlah' => 10, // Exceeds available stock
        ];

        $response = $this->put(route('transaksi.update', $detail->id_penjualan_detail), $updateData);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Jumlah melebihi stok yang tersedia']);
    }

    public function test_admin_can_delete_penjualan_detail_and_restore_stock()
    {
        $produk = Produk::factory()->create(['stok' => 25]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $penjualan = Penjualan::factory()->create();

        $detail = PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'jumlah' => 4,
        ]);

        $response = $this->delete(route('transaksi.destroy', $detail->id_penjualan_detail));

        $response->assertStatus(204);

        // Check detail was deleted
        $this->assertDatabaseMissing('penjualan_detail', [
            'id_penjualan_detail' => $detail->id_penjualan_detail,
        ]);

        // Check stock was restored
        $updatedProduk = Produk::find($produk->id_produk);
        $this->assertEquals(29, $updatedProduk->stok); // 25 + 4
    }

    public function test_admin_can_update_product_unit()
    {
        $produk = Produk::factory()->create();
        $satuanProduk1 = SatuanProduk::factory()->create(['nama' => 'Pack']);
        $satuanProduk2 = SatuanProduk::factory()->create(['nama' => 'Box']);

        $produkSatuan1 = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk1->id_satuan_produk,
            'harga_jual_eceran' => 20000,
        ]);

        $produkSatuan2 = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk2->id_satuan_produk,
            'harga_jual_eceran' => 180000, // Different price for box
        ]);

        $penjualan = Penjualan::factory()->create();

        $detail = PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan1->id,
            'harga_jual_eceran' => 20000,
            'jumlah' => 2,
            'subtotal' => 40000,
        ]);

        $updateData = [
            'id_satuan' => $produkSatuan2->id,
        ];

        $response = $this->post(route('transaksi.updateSatuan', $detail->id_penjualan_detail), $updateData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check unit and price were updated
        $this->assertDatabaseHas('penjualan_detail', [
            'id_penjualan_detail' => $detail->id_penjualan_detail,
            'id_produk_satuan' => $produkSatuan2->id,
            'harga_jual_eceran' => 180000,
            'subtotal' => 360000, // 180000 * 2
        ]);
    }

    public function test_penjualan_detail_data_includes_total_calculation()
    {
        $produk1 = Produk::factory()->create();
        $produk2 = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();

        $produkSatuan1 = ProdukSatuan::factory()->create([
            'id_produk' => $produk1->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 10000,
        ]);

        $produkSatuan2 = ProdukSatuan::factory()->create([
            'id_produk' => $produk2->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 20000,
        ]);

        $penjualan = Penjualan::factory()->create(['tipe_pembeli' => 'eceran']);

        // Add first product
        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk1->id_produk,
            'id_produk_satuan' => $produkSatuan1->id,
            'harga_jual_eceran' => 10000,
            'jumlah' => 3,
            'subtotal' => 30000,
        ]);

        // Add second product
        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk2->id_produk,
            'id_produk_satuan' => $produkSatuan2->id,
            'harga_jual_eceran' => 20000,
            'jumlah' => 2,
            'subtotal' => 40000,
        ]);

        $response = $this->get(route('transaksi.data', $penjualan->id_penjualan));

        $response->assertStatus(200);

        $data = $response->json('data');

        // Check that last row contains total calculation
        $lastRow = end($data);
        $this->assertStringContains('70000', $lastRow['kode_produk']); // Total: 30000 + 40000 = 70000
        $this->assertStringContains('5', $lastRow['nama_produk']); // Total items: 3 + 2 = 5
    }

    public function test_penjualan_detail_data_formats_currency_correctly()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 25000,
        ]);

        $penjualan = Penjualan::factory()->create(['tipe_pembeli' => 'eceran']);

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 25000,
            'jumlah' => 2,
            'subtotal' => 50000,
        ]);

        $response = $this->get(route('transaksi.data', $penjualan->id_penjualan));

        $response->assertStatus(200);

        $data = $response->json('data');
        $firstRow = $data[0];

        // Check currency formatting
        $this->assertStringContains('Rp. ', $firstRow['subtotal']);
    }

    public function test_load_form_calculates_payment_correctly()
    {
        $response = $this->get(route('transaksi.loadForm', [
            'diskon' => 10,
            'total' => 100000,
            'diterima' => 95000
        ]));

        $response->assertStatus(200);

        $data = $response->json();

        // Expected calculations:
        // bayar = 100000 - (10% of 100000) = 90000
        // kembali = 95000 - 90000 = 5000
        $this->assertEquals(90000, $data['bayar']);
        $this->assertEquals('Rp. 100.000', $data['totalrp']);
        $this->assertEquals('Rp. 90.000', $data['bayarrp']);
        $this->assertEquals('Rp. 5.000', $data['kembalirp']);
        $this->assertStringContains('Rupiah', $data['kembali_terbilang']);
    }

    public function test_load_form_handles_underpayment()
    {
        $response = $this->get(route('transaksi.loadForm', [
            'diskon' => 5,
            'total' => 50000,
            'diterima' => 20000
        ]));

        $response->assertStatus(200);

        $data = $response->json();

        // Expected calculations:
        // bayar = 50000 - (5% of 50000) = 47500
        // kurang = 47500 - 20000 = 27500
        $this->assertEquals(47500, $data['bayar']);
        $this->assertEquals(27500, $data['kurang']);
        $this->assertEquals('Rp. 27.500', $data['kurangrp']);
        $this->assertStringContains('Kurang: Rp. 27.500', $data['bayar_text']);
    }

    public function test_load_form_handles_exact_payment()
    {
        $response = $this->get(route('transaksi.loadForm', [
            'diskon' => 0,
            'total' => 75000,
            'diterima' => 75000
        ]));

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertEquals(75000, $data['bayar']);
        $this->assertEquals(0, $data['kembalirp']);
        $this->assertStringContains('Bayar: Rp. 75.000', $data['bayar_text']);
    }

    public function test_penjualan_detail_data_shows_product_codes_and_names()
    {
        $produk = Produk::factory()->create([
            'kode_produk' => 'PRD001',
            'nama_produk' => 'Test Product',
        ]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $penjualan = Penjualan::factory()->create();

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
        ]);

        $response = $this->get(route('transaksi.data', $penjualan->id_penjualan));

        $response->assertStatus(200);

        $data = $response->json('data');
        $firstRow = $data[0];

        $this->assertStringContains('PRD001', $firstRow['kode_produk']);
        $this->assertEquals('Test Product', $firstRow['nama_produk']);
    }

    public function test_penjualan_detail_data_includes_unit_dropdown()
    {
        $produk = Produk::factory()->create();
        $satuanProduk1 = SatuanProduk::factory()->create(['nama' => 'Pack']);
        $satuanProduk2 = SatuanProduk::factory()->create(['nama' => 'Box']);

        ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk1->id_satuan_produk,
            'harga_jual_eceran' => 15000,
        ]);

        ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk2->id_satuan_produk,
            'harga_jual_eceran' => 130000,
        ]);

        $penjualan = Penjualan::factory()->create();

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => ProdukSatuan::where('id_produk', $produk->id_produk)->first()->id,
        ]);

        $response = $this->get(route('transaksi.data', $penjualan->id_penjualan));

        $response->assertStatus(200);

        $data = $response->json('data');
        $firstRow = $data[0];

        // Check that unit dropdown contains both options
        $this->assertStringContains('<select', $firstRow['produk_satuan']);
        $this->assertStringContains('Pack', $firstRow['produk_satuan']);
        $this->assertStringContains('Box', $firstRow['produk_satuan']);
        $this->assertStringContains('Rp.15.000', $firstRow['produk_satuan']);
        $this->assertStringContains('Rp.130.000', $firstRow['produk_satuan']);
    }

    public function test_penjualan_detail_quantity_input_has_correct_attributes()
    {
        $produk = Produk::factory()->create(['stok' => 10]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $penjualan = Penjualan::factory()->create();

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'jumlah' => 3.5,
        ]);

        $response = $this->get(route('transaksi.data', $penjualan->id_penjualan));

        $response->assertStatus(200);

        $data = $response->json('data');
        $firstRow = $data[0];

        // Check quantity input attributes
        $this->assertStringContains('type="number"', $firstRow['jumlah']);
        $this->assertStringContains('step="0.1"', $firstRow['jumlah']);
        $this->assertStringContains('min="0.1"', $firstRow['jumlah']);
        $this->assertStringContains('class="form-control  input-sm quantity"', $firstRow['jumlah']);
        $this->assertStringContains('value="3.5"', $firstRow['jumlah']);
    }
}
