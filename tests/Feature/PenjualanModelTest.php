<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Pelayan;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use App\Models\SatuanProduk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenjualanModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create setting first
        \App\Models\Setting::factory()->create();
    }

    public function test_penjualan_model_has_correct_table_name()
    {
        $penjualan = new Penjualan();

        $this->assertEquals('penjualan', $penjualan->getTable());
    }

    public function test_penjualan_model_has_correct_primary_key()
    {
        $penjualan = new Penjualan();

        $this->assertEquals('id_penjualan', $penjualan->getKeyName());
    }

    public function test_penjualan_model_has_correct_fillable_attributes()
    {
        $penjualan = new Penjualan();

        $expectedFillable = [
            'id_member',
            'id_pelayan',
            'total_item',
            'total_harga',
            'diskon',
            'bayar',
            'diterima',
            'hutang',
            'tipe_pembeli',
            'nama_pembeli',
            'status',
            'ishutang',
            'id_user',
        ];

        $this->assertEquals($expectedFillable, $penjualan->getFillable());
    }

    public function test_penjualan_model_uses_has_factory_trait()
    {
        $penjualan = new Penjualan();

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($penjualan));
    }

    public function test_penjualan_model_has_status_constants()
    {
        $this->assertEquals(0, Penjualan::STATUS_DRAFT);
        $this->assertEquals(1, Penjualan::STATUS_FINAL);
    }

    public function test_penjualan_model_belongs_to_member_relationship()
    {
        $member = Member::factory()->create();
        $penjualan = Penjualan::factory()->create(['id_member' => $member->id_member]);

        $this->assertInstanceOf(Member::class, $penjualan->member);
        $this->assertEquals($member->id_member, $penjualan->member->id_member);
    }

    public function test_penjualan_model_belongs_to_user_relationship()
    {
        $user = User::factory()->create();
        $penjualan = Penjualan::factory()->create(['id_user' => $user->id]);

        $this->assertInstanceOf(User::class, $penjualan->user);
        $this->assertEquals($user->id, $penjualan->user->id);
    }

    public function test_penjualan_model_has_many_details_relationship()
    {
        $penjualan = Penjualan::factory()->create();
        $detail1 = PenjualanDetail::factory()->create(['id_penjualan' => $penjualan->id_penjualan]);
        $detail2 = PenjualanDetail::factory()->create(['id_penjualan' => $penjualan->id_penjualan]);

        $this->assertCount(2, $penjualan->details);
        $this->assertInstanceOf(PenjualanDetail::class, $penjualan->details->first());
        $this->assertEquals($penjualan->id_penjualan, $penjualan->details->first()->id_penjualan);
    }

    public function test_penjualan_model_belongs_to_pelayan_relationship()
    {
        $pelayan = Pelayan::factory()->create();
        $penjualan = Penjualan::factory()->create(['id_pelayan' => $pelayan->id]);

        $this->assertInstanceOf(Pelayan::class, $penjualan->pelayan);
        $this->assertEquals($pelayan->id, $penjualan->pelayan->id);
    }

    public function test_penjualan_model_belongs_to_produk_satuan_relationship()
    {
        $produkSatuan = ProdukSatuan::factory()->create();
        $penjualan = Penjualan::factory()->create(['id_produk_satuan' => $produkSatuan->id]);

        $this->assertInstanceOf(ProdukSatuan::class, $penjualan->produkSatuan);
        $this->assertEquals($produkSatuan->id, $penjualan->produkSatuan->id);
    }

    public function test_penjualan_model_can_be_created_with_factory()
    {
        $penjualan = Penjualan::factory()->create([
            'total_item' => 5,
            'total_harga' => 100000,
            'diskon' => 10,
            'bayar' => 90000,
            'tipe_pembeli' => 'eceran',
            'nama_pembeli' => 'Test Customer',
            'status' => Penjualan::STATUS_DRAFT,
        ]);

        $this->assertInstanceOf(Penjualan::class, $penjualan);
        $this->assertEquals(5, $penjualan->total_item);
        $this->assertEquals(100000, $penjualan->total_harga);
        $this->assertEquals(10, $penjualan->diskon);
        $this->assertEquals(90000, $penjualan->bayar);
        $this->assertEquals('eceran', $penjualan->tipe_pembeli);
        $this->assertEquals('Test Customer', $penjualan->nama_pembeli);
        $this->assertEquals(Penjualan::STATUS_DRAFT, $penjualan->status);
    }

    public function test_penjualan_model_has_timestamps()
    {
        $penjualan = Penjualan::factory()->create();

        $this->assertNotNull($penjualan->created_at);
        $this->assertNotNull($penjualan->updated_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $penjualan->created_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $penjualan->updated_at);
    }

    public function test_penjualan_model_member_relationship_returns_null_when_no_member()
    {
        $penjualan = Penjualan::factory()->create(['id_member' => null]);

        $this->assertNull($penjualan->member);
    }

    public function test_penjualan_model_pelayan_relationship_returns_null_when_no_pelayan()
    {
        $penjualan = Penjualan::factory()->create(['id_pelayan' => null]);

        $this->assertNull($penjualan->pelayan);
    }

    public function test_penjualan_model_details_relationship_returns_empty_collection_when_no_details()
    {
        $penjualan = Penjualan::factory()->create();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $penjualan->details);
        $this->assertCount(0, $penjualan->details);
    }

    public function test_penjualan_model_can_update_fillable_attributes()
    {
        $penjualan = Penjualan::factory()->create();

        $penjualan->update([
            'total_item' => 10,
            'total_harga' => 200000,
            'diskon' => 15,
            'bayar' => 170000,
            'tipe_pembeli' => 'borongan',
            'nama_pembeli' => 'Updated Customer',
            'status' => Penjualan::STATUS_FINAL,
        ]);

        $this->assertEquals(10, $penjualan->total_item);
        $this->assertEquals(200000, $penjualan->total_harga);
        $this->assertEquals(15, $penjualan->diskon);
        $this->assertEquals(170000, $penjualan->bayar);
        $this->assertEquals('borongan', $penjualan->tipe_pembeli);
        $this->assertEquals('Updated Customer', $penjualan->nama_pembeli);
        $this->assertEquals(Penjualan::STATUS_FINAL, $penjualan->status);
    }

    public function test_penjualan_model_mass_assignment_protection()
    {
        $penjualan = new Penjualan();

        // Test that non-fillable attributes are protected
        $this->assertNotContains('id_penjualan', $penjualan->getFillable());
        $this->assertNotContains('created_at', $penjualan->getFillable());
        $this->assertNotContains('updated_at', $penjualan->getFillable());
    }

    public function test_penjualan_model_member_relationship_with_correct_foreign_key()
    {
        $member = Member::factory()->create();
        $penjualan = Penjualan::factory()->create(['id_member' => $member->id_member]);

        // Test the relationship definition
        $relationship = $penjualan->member();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasOne', $relationship);

        // Test foreign key and local key
        $this->assertEquals('id_member', $relationship->getForeignKeyName());
        $this->assertEquals('id_member', $relationship->getLocalKeyName());
    }

    public function test_penjualan_model_user_relationship_with_correct_foreign_key()
    {
        $user = User::factory()->create();
        $penjualan = Penjualan::factory()->create(['id_user' => $user->id]);

        // Test the relationship definition
        $relationship = $penjualan->user();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $relationship);

        // Test foreign key and owner key
        $this->assertEquals('id_user', $relationship->getForeignKeyName());
        $this->assertEquals('id', $relationship->getOwnerKeyName());
    }

    public function test_penjualan_model_details_relationship_with_correct_foreign_key()
    {
        $penjualan = Penjualan::factory()->create();
        $detail = PenjualanDetail::factory()->create(['id_penjualan' => $penjualan->id_penjualan]);

        // Test the relationship definition
        $relationship = $penjualan->details();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $relationship);

        // Test foreign key and local key
        $this->assertEquals('id_penjualan', $relationship->getForeignKeyName());
        $this->assertEquals('id_penjualan', $relationship->getLocalKeyName());
    }

    public function test_penjualan_model_pelayan_relationship_with_correct_foreign_key()
    {
        $pelayan = Pelayan::factory()->create();
        $penjualan = Penjualan::factory()->create(['id_pelayan' => $pelayan->id]);

        // Test the relationship definition
        $relationship = $penjualan->pelayan();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $relationship);

        // Test foreign key and owner key
        $this->assertEquals('id_pelayan', $relationship->getForeignKeyName());
        $this->assertEquals('id', $relationship->getOwnerKeyName());
    }

    public function test_penjualan_model_produk_satuan_relationship_with_correct_foreign_key()
    {
        $produkSatuan = ProdukSatuan::factory()->create();
        $penjualan = Penjualan::factory()->create(['id_produk_satuan' => $produkSatuan->id]);

        // Test the relationship definition
        $relationship = $penjualan->produkSatuan();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $relationship);

        // Test foreign key and owner key
        $this->assertEquals('id_produk_satuan', $relationship->getForeignKeyName());
        $this->assertEquals('id', $relationship->getOwnerKeyName());
    }

    public function test_penjualan_model_default_values()
    {
        $penjualan = Penjualan::factory()->create();

        // Test that certain fields have default values
        $this->assertIsNumeric($penjualan->total_item);
        $this->assertIsNumeric($penjualan->total_harga);
        $this->assertIsNumeric($penjualan->diskon);
        $this->assertIsNumeric($penjualan->bayar);
        $this->assertIsNumeric($penjualan->diterima);
        $this->assertIsString($penjualan->tipe_pembeli);
        $this->assertIsNumeric($penjualan->status);
    }

    public function test_penjualan_model_status_values_are_valid()
    {
        $draftPenjualan = Penjualan::factory()->create(['status' => Penjualan::STATUS_DRAFT]);
        $finalPenjualan = Penjualan::factory()->create(['status' => Penjualan::STATUS_FINAL]);

        $this->assertEquals(Penjualan::STATUS_DRAFT, $draftPenjualan->status);
        $this->assertEquals(Penjualan::STATUS_FINAL, $finalPenjualan->status);

        // Ensure status values are different
        $this->assertNotEquals(Penjualan::STATUS_DRAFT, Penjualan::STATUS_FINAL);
    }

    public function test_penjualan_model_relationships_are_lazy_loaded()
    {
        $member = Member::factory()->create();
        $user = User::factory()->create();
        $penjualan = Penjualan::factory()->create([
            'id_member' => $member->id_member,
            'id_user' => $user->id,
        ]);

        // Test that relationships are not loaded initially
        $this->assertFalse($penjualan->relationLoaded('member'));
        $this->assertFalse($penjualan->relationLoaded('user'));
        $this->assertFalse($penjualan->relationLoaded('details'));

        // Load relationships
        $penjualan->load(['member', 'user', 'details']);

        $this->assertTrue($penjualan->relationLoaded('member'));
        $this->assertTrue($penjualan->relationLoaded('user'));
        $this->assertTrue($penjualan->relationLoaded('details'));
    }

    public function test_penjualan_model_can_be_soft_deleted_if_soft_deletes_trait_used()
    {
        $penjualan = Penjualan::factory()->create();

        // Check if model uses SoftDeletes trait
        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($penjualan));

        if ($usesSoftDeletes) {
            $penjualan->delete();
            $this->assertSoftDeleted($penjualan);
        } else {
            // If not using soft deletes, regular delete should work
            $penjualan->delete();
            $this->assertDatabaseMissing('penjualan', ['id_penjualan' => $penjualan->id_penjualan]);
        }
    }

    public function test_penjualan_model_to_array_includes_relationships_when_loaded()
    {
        $member = Member::factory()->create();
        $penjualan = Penjualan::factory()->create(['id_member' => $member->id_member]);

        $array = $penjualan->toArray();
        $this->assertArrayNotHasKey('member', $array); // Not loaded

        $penjualan->load('member');
        $arrayWithRelation = $penjualan->toArray();
        $this->assertArrayHasKey('member', $arrayWithRelation);
        $this->assertEquals($member->id_member, $arrayWithRelation['member']['id_member']);
    }

    public function test_penjualan_model_scope_queries_work()
    {
        Penjualan::factory()->count(3)->create(['status' => Penjualan::STATUS_DRAFT]);
        Penjualan::factory()->count(2)->create(['status' => Penjualan::STATUS_FINAL]);

        $draftSales = Penjualan::where('status', Penjualan::STATUS_DRAFT)->get();
        $finalSales = Penjualan::where('status', Penjualan::STATUS_FINAL)->get();

        $this->assertCount(3, $draftSales);
        $this->assertCount(2, $finalSales);
    }

    public function test_penjualan_model_foreign_key_constraints()
    {
        $member = Member::factory()->create();
        $user = User::factory()->create();
        $pelayan = Pelayan::factory()->create();

        $penjualan = Penjualan::factory()->create([
            'id_member' => $member->id_member,
            'id_user' => $user->id,
            'id_pelayan' => $pelayan->id,
        ]);

        // Verify foreign keys are correctly set
        $this->assertEquals($member->id_member, $penjualan->id_member);
        $this->assertEquals($user->id, $penjualan->id_user);
        $this->assertEquals($pelayan->id, $penjualan->id_pelayan);
    }
}
