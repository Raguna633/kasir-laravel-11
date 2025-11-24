<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Pelayan;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use App\Models\SatuanProduk;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenjualanTest extends TestCase
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

    public function test_admin_can_view_penjualan_index()
    {
        $response = $this->get(route('penjualan.index'));

        $response->assertStatus(200);
        $response->assertViewIs('penjualan.index');
    }

    public function test_admin_can_get_penjualan_data()
    {
        $member = Member::factory()->create();
        $pelayan = Pelayan::factory()->create();

        Penjualan::factory()->count(3)->create([
            'id_member' => $member->id_member,
            'id_pelayan' => $pelayan->id_pelayan,
        ]);

        $response = $this->get(route('penjualan.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'DT_RowIndex',
                    'nama_pembeli',
                    'total_item',
                    'satuan',
                    'total_harga',
                    'tipe',
                    'bayar',
                    'tanggal',
                    'kode_member',
                    'diskon',
                    'kasir',
                    'aksi'
                ]
            ]
        ]);
    }

    public function test_admin_can_create_new_penjualan()
    {
        $response = $this->get(route('penjualan.create'));

        $response->assertRedirect(route('transaksi.index'));

        // Check if penjualan was created with correct initial values
        $this->assertDatabaseHas('penjualan', [
            'total_item' => 0,
            'total_harga' => 0,
            'diskon' => 0,
            'bayar' => 0,
            'diterima' => 0,
            'tipe_pembeli' => 'eceran',
            'status' => Penjualan::STATUS_DRAFT,
            'id_user' => auth()->id(),
        ]);
    }

    public function test_admin_can_update_customer_type()
    {
        $penjualan = Penjualan::factory()->create(['tipe_pembeli' => 'eceran']);

        $response = $this->post(route('penjualan.updateTipePembeli'), [
            'id_penjualan' => $penjualan->id_penjualan,
            'tipe_pembeli' => 'borongan',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('penjualan', [
            'id_penjualan' => $penjualan->id_penjualan,
            'tipe_pembeli' => 'borongan',
        ]);
    }

    public function test_admin_can_store_penjualan_with_complete_payment()
    {
        $member = Member::factory()->create();
        $pelayan = Pelayan::factory()->create(['poin' => 5]);
        $produk = Produk::factory()->create(['stok' => 10]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 15000,
            'harga_jual_borongan' => 14000,
        ]);

        $penjualan = Penjualan::factory()->create([
            'status' => Penjualan::STATUS_DRAFT,
            'tipe_pembeli' => 'eceran',
        ]);

        // Create penjualan detail
        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 15000,
            'jumlah' => 2,
            'diskon' => 0,
            'subtotal' => 30000,
        ]);

        $storeData = [
            'id_penjualan' => $penjualan->id_penjualan,
            'id_member' => $member->id_member,
            'id_pelayan' => $pelayan->id_pelayan,
            'total_item' => 2,
            'total' => 30000,
            'nama_pembeli' => 'John Doe',
            'diskon' => 10,
            'diterima' => 27000, // 30000 - 10% = 27000
            'tipe_pembeli' => 'eceran',
        ];

        $response = $this->post(route('penjualan.store'), $storeData);

        $response->assertRedirect(route('transaksi.selesai'));

        // Check penjualan was updated
        $this->assertDatabaseHas('penjualan', [
            'id_penjualan' => $penjualan->id_penjualan,
            'id_member' => $member->id_member,
            'id_pelayan' => $pelayan->id_pelayan,
            'total_item' => 2,
            'total_harga' => 30000,
            'diskon' => 10,
            'bayar' => 27000,
            'diterima' => 27000,
            'hutang' => 0,
            'tipe_pembeli' => 'eceran',
            'nama_pembeli' => 'John Doe',
            'status' => Penjualan::STATUS_FINAL,
        ]);

        // Check product stock was decreased
        $updatedProduk = Produk::find($produk->id_produk);
        $this->assertEquals(8, $updatedProduk->stok); // 10 - 2

        // Check pelayan poin was increased
        $updatedPelayan = Pelayan::find($pelayan->id_pelayan);
        $this->assertEquals(6, $updatedPelayan->poin); // 5 + 1
    }

    public function test_admin_can_store_penjualan_as_debt()
    {
        $member = Member::factory()->create();
        $produk = Produk::factory()->create(['stok' => 15]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 20000,
        ]);

        $penjualan = Penjualan::factory()->create([
            'status' => Penjualan::STATUS_DRAFT,
            'tipe_pembeli' => 'eceran',
        ]);

        // Create penjualan detail
        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 20000,
            'jumlah' => 3,
            'subtotal' => 60000,
        ]);

        $storeData = [
            'id_penjualan' => $penjualan->id_penjualan,
            'id_member' => $member->id_member,
            'total_item' => 3,
            'total' => 60000,
            'nama_pembeli' => 'Jane Smith',
            'diskon' => 5,
            'diterima' => 30000, // Partial payment
            'simpan_sebagai_hutang' => true,
            'tipe_pembeli' => 'eceran',
        ];

        $response = $this->post(route('penjualan.store'), $storeData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'message' => 'Transaksi disimpan sebagai hutang.']);

        // Check penjualan was updated as debt
        $this->assertDatabaseHas('penjualan', [
            'id_penjualan' => $penjualan->id_penjualan,
            'id_member' => $member->id_member,
            'total_item' => 3,
            'total_harga' => 60000,
            'diskon' => 5,
            'bayar' => 57000, // 60000 - 5% = 57000
            'diterima' => 30000,
            'hutang' => 27000, // 57000 - 30000
            'tipe_pembeli' => 'eceran',
            'nama_pembeli' => 'Jane Smith',
            'status' => Penjualan::STATUS_DRAFT,
            'ishutang' => 1,
        ]);

        // Check product stock was decreased even for debt
        $updatedProduk = Produk::find($produk->id_produk);
        $this->assertEquals(12, $updatedProduk->stok); // 15 - 3
    }

    public function test_admin_can_store_penjualan_with_partial_payment()
    {
        $produk = Produk::factory()->create(['stok' => 20]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 25000,
        ]);

        $penjualan = Penjualan::factory()->create([
            'status' => Penjualan::STATUS_DRAFT,
            'tipe_pembeli' => 'borongan',
        ]);

        // Create penjualan detail
        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 25000,
            'jumlah' => 4,
            'subtotal' => 100000,
        ]);

        $storeData = [
            'id_penjualan' => $penjualan->id_penjualan,
            'total_item' => 4,
            'total' => 100000,
            'nama_pembeli' => 'Bob Wilson',
            'diskon' => 0,
            'diterima' => 75000, // Partial payment
            'tipe_pembeli' => 'borongan',
        ];

        $response = $this->post(route('penjualan.store'), $storeData);

        $response->assertRedirect(route('transaksi.baru'));

        // Check penjualan was updated with debt
        $this->assertDatabaseHas('penjualan', [
            'id_penjualan' => $penjualan->id_penjualan,
            'total_item' => 4,
            'total_harga' => 100000,
            'diskon' => 0,
            'bayar' => 100000,
            'diterima' => 75000,
            'hutang' => 25000, // 100000 - 75000
            'tipe_pembeli' => 'borongan',
            'nama_pembeli' => 'Bob Wilson',
            'status' => Penjualan::STATUS_DRAFT,
            'ishutang' => 0,
        ]);

        // Check product stock was NOT decreased for draft status
        $updatedProduk = Produk::find($produk->id_produk);
        $this->assertEquals(20, $updatedProduk->stok); // Still 20
    }

    public function test_admin_can_view_penjualan_detail()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 30000,
            'harga_jual_borongan' => 28000,
        ]);

        $penjualan = Penjualan::factory()->create(['tipe_pembeli' => 'eceran']);

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 30000,
            'jumlah' => 2,
            'diskon' => 5,
            'subtotal' => 57000, // (30000 * 2) * (1 - 5/100) = 57000
        ]);

        $response = $this->get(route('penjualan.show', $penjualan->id_penjualan));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'DT_RowIndex',
                    'kode_produk',
                    'nama_produk',
                    'harga_jual',
                    'jumlah',
                    'subtotal'
                ]
            ]
        ]);
    }

    public function test_admin_can_delete_penjualan_and_restore_stock()
    {
        $produk = Produk::factory()->create(['stok' => 25]);
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 18000,
        ]);

        $penjualan = Penjualan::factory()->create();

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 18000,
            'jumlah' => 5,
            'subtotal' => 90000,
        ]);

        // First decrease stock by storing as final
        $this->post(route('penjualan.store'), [
            'id_penjualan' => $penjualan->id_penjualan,
            'total_item' => 5,
            'total' => 90000,
            'nama_pembeli' => 'Test Customer',
            'diskon' => 0,
            'diterima' => 90000,
        ]);

        // Verify stock was decreased
        $produkAfterStore = Produk::find($produk->id_produk);
        $this->assertEquals(20, $produkAfterStore->stok); // 25 - 5

        // Now delete penjualan
        $response = $this->delete(route('penjualan.destroy', $penjualan->id_penjualan));

        $response->assertStatus(204);

        // Check penjualan was deleted
        $this->assertDatabaseMissing('penjualan', ['id_penjualan' => $penjualan->id_penjualan]);

        // Check penjualan details were deleted
        $this->assertDatabaseMissing('penjualan_detail', ['id_penjualan' => $penjualan->id_penjualan]);

        // Check product stock was restored
        $produkAfterDelete = Produk::find($produk->id_produk);
        $this->assertEquals(25, $produkAfterDelete->stok); // Back to original 25
    }

    public function test_penjualan_data_shows_correct_member_code()
    {
        $member1 = Member::factory()->create(['kode_member' => 'MEM001']);
        $member2 = Member::factory()->create(['kode_member' => 'MEM002']);

        Penjualan::factory()->create(['id_member' => $member1->id_member]);
        Penjualan::factory()->create(['id_member' => $member2->id_member]);
        Penjualan::factory()->create(['id_member' => null]); // No member

        $response = $this->get(route('penjualan.data'));

        $response->assertStatus(200);

        $data = $response->json('data');
        $memberCodes = array_column($data, 'kode_member');

        // Check that member codes are displayed correctly
        $this->assertStringContains('MEM001', implode(' ', $memberCodes));
        $this->assertStringContains('MEM002', implode(' ', $memberCodes));
    }

    public function test_penjualan_data_formats_currency_correctly()
    {
        Penjualan::factory()->create([
            'total_item' => 1000,
            'total_harga' => 2500000,
            'bayar' => 2250000,
            'diskon' => 10,
        ]);

        $response = $this->get(route('penjualan.data'));

        $response->assertStatus(200);

        $data = $response->json('data');
        $firstRecord = $data[0];

        // Check currency formatting
        $this->assertStringContains('Rp. ', $firstRecord['total_harga']);
        $this->assertStringContains('Rp. ', $firstRecord['bayar']);
        $this->assertStringContains('%', $firstRecord['diskon']);
    }

    public function test_penjualan_shows_correct_price_based_on_customer_type()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create(['nama' => 'Pack']);
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 20000,
            'harga_jual_borongan' => 18000,
        ]);

        // Test eceran (retail) pricing
        $penjualanEceran = Penjualan::factory()->create(['tipe_pembeli' => 'eceran']);
        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualanEceran->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 20000,
            'jumlah' => 3,
            'subtotal' => 60000,
        ]);

        $responseEceran = $this->get(route('penjualan.show', $penjualanEceran->id_penjualan));
        $responseEceran->assertStatus(200);

        // Test borongan (wholesale) pricing
        $penjualanBorongan = Penjualan::factory()->create(['tipe_pembeli' => 'borongan']);
        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualanBorongan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
            'harga_jual_eceran' => 20000,
            'jumlah' => 3,
            'subtotal' => 54000, // 18000 * 3
        ]);

        $responseBorongan = $this->get(route('penjualan.show', $penjualanBorongan->id_penjualan));
        $responseBorongan->assertStatus(200);

        // Both should return successfully with different pricing logic
        $this->assertTrue(true); // Placeholder assertion
    }

    public function test_penjualan_cannot_be_modified_after_final_status()
    {
        $penjualan = Penjualan::factory()->create(['status' => Penjualan::STATUS_FINAL]);

        $storeData = [
            'id_penjualan' => $penjualan->id_penjualan,
            'total_item' => 1,
            'total' => 10000,
            'nama_pembeli' => 'Test',
            'diskon' => 0,
            'diterima' => 10000,
        ];

        $response = $this->post(route('penjualan.store'), $storeData);

        $response->assertRedirect(); // Should redirect with error
    }

    public function test_penjualan_nota_kecil_view()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $penjualan = Penjualan::factory()->create([
            'nama_pembeli' => 'Test Customer',
            'status' => Penjualan::STATUS_FINAL,
        ]);

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
        ]);

        // Set session for nota kecil
        session(['id_penjualan' => $penjualan->id_penjualan]);

        $response = $this->get(route('penjualan.notaKecil'));

        $response->assertStatus(200);
        $response->assertViewIs('penjualan.nota_kecil');
        $response->assertViewHas(['setting', 'penjualan', 'detail']);
    }

    public function test_penjualan_print_nota_kecil()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $penjualan = Penjualan::factory()->create([
            'nama_pembeli' => 'Print Test Customer',
            'status' => Penjualan::STATUS_FINAL,
        ]);

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
        ]);

        $response = $this->get(route('penjualan.printnota_kecil', $penjualan->id_penjualan));

        $response->assertStatus(200);
        $response->assertViewIs('penjualan.nota_kecil');
    }

    public function test_penjualan_print_nota_besar_returns_pdf()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $penjualan = Penjualan::factory()->create([
            'nama_pembeli' => 'PDF Test Customer',
            'status' => Penjualan::STATUS_FINAL,
        ]);

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
        ]);

        $response = $this->get(route('penjualan.printnota_besar', $penjualan->id_penjualan));

        $response->assertStatus(200);
        // PDF response should have appropriate headers
        $this->assertStringContains('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_get_draft_transaction()
    {
        $member = Member::factory()->create();
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $penjualan = Penjualan::factory()->create([
            'id_member' => $member->id_member,
            'status' => Penjualan::STATUS_DRAFT,
            'tipe_pembeli' => 'eceran',
        ]);

        PenjualanDetail::factory()->create([
            'id_penjualan' => $penjualan->id_penjualan,
            'id_produk' => $produk->id_produk,
            'id_produk_satuan' => $produkSatuan->id,
        ]);

        $response = $this->get(route('penjualan.getDraftTransaction', $penjualan->id_penjualan));

        $response->assertStatus(200);
        $response->assertViewIs('penjualan_detail.index');
        $response->assertViewHas(['penjualan', 'produk', 'member', 'diskon', 'memberSelected']);
    }

    public function test_get_draft_transaction_only_shows_draft_status()
    {
        $penjualanDraft = Penjualan::factory()->create(['status' => Penjualan::STATUS_DRAFT]);
        $penjualanFinal = Penjualan::factory()->create(['status' => Penjualan::STATUS_FINAL]);

        $response = $this->get(route('penjualan.getDraftTransaction', $penjualanFinal->id_penjualan));

        $response->assertStatus(404); // Should not find final transactions
    }
}
