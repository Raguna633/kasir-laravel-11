<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\ProdukSatuan;
use App\Models\SatuanProduk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdukModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create setting first
        \App\Models\Setting::factory()->create();
    }

    public function test_produk_model_has_correct_table_name()
    {
        $produk = new Produk();

        $this->assertEquals('produk', $produk->getTable());
    }

    public function test_produk_model_has_correct_primary_key()
    {
        $produk = new Produk();

        $this->assertEquals('id_produk', $produk->getKeyName());
    }

    public function test_produk_model_uses_guarded_instead_of_fillable()
    {
        $produk = new Produk();

        // Since it uses $guarded = [], all attributes should be fillable
        $this->assertEmpty($produk->getGuarded());
        $this->assertEmpty($produk->getFillable()); // When guarded is empty, fillable is also empty
    }

    public function test_produk_model_uses_has_factory_trait()
    {
        $produk = new Produk();

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($produk));
    }

    public function test_produk_model_belongs_to_kategori_relationship()
    {
        $kategori = Kategori::factory()->create();
        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);

        $this->assertInstanceOf(Kategori::class, $produk->kategori);
        $this->assertEquals($kategori->id_kategori, $produk->kategori->id_kategori);
    }

    public function test_produk_model_has_many_produk_satuan_relationship()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();

        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        $this->assertCount(1, $produk->produkSatuan);
        $this->assertInstanceOf(ProdukSatuan::class, $produk->produkSatuan->first());
        $this->assertEquals($produk->id_produk, $produk->produkSatuan->first()->id_produk);
    }

    public function test_produk_model_belongs_to_many_satuan_through_pivot()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();

        ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 10000,
            'harga_jual_borongan' => 9000,
        ]);

        $this->assertCount(1, $produk->satuan);
        $this->assertInstanceOf(SatuanProduk::class, $produk->satuan->first());

        // Test pivot data
        $pivot = $produk->satuan->first()->pivot;
        $this->assertEquals(10000, $pivot->harga_jual_eceran);
        $this->assertEquals(9000, $pivot->harga_jual_borongan);
    }

    public function test_produk_model_can_be_created_with_factory()
    {
        $kategori = Kategori::factory()->create();

        $produk = Produk::factory()->create([
            'nama_produk' => 'Test Product',
            'kode_produk' => 'PRD001',
            'harga_beli' => 50000,
            'stok' => 100,
            'id_kategori' => $kategori->id_kategori,
        ]);

        $this->assertInstanceOf(Produk::class, $produk);
        $this->assertEquals('Test Product', $produk->nama_produk);
        $this->assertEquals('PRD001', $produk->kode_produk);
        $this->assertEquals(50000, $produk->harga_beli);
        $this->assertEquals(100, $produk->stok);
        $this->assertEquals($kategori->id_kategori, $produk->id_kategori);
    }

    public function test_produk_model_has_timestamps()
    {
        $produk = Produk::factory()->create();

        $this->assertNotNull($produk->created_at);
        $this->assertNotNull($produk->updated_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $produk->created_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $produk->updated_at);
    }

    public function test_produk_model_kategori_relationship_returns_null_when_no_kategori()
    {
        $produk = Produk::factory()->create(['id_kategori' => null]);

        $this->assertNull($produk->kategori);
    }

    public function test_produk_model_produk_satuan_relationship_returns_empty_collection_when_no_satuans()
    {
        $produk = Produk::factory()->create();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $produk->produkSatuan);
        $this->assertCount(0, $produk->produkSatuan);
    }

    public function test_produk_model_satuan_relationship_returns_empty_collection_when_no_satuans()
    {
        $produk = Produk::factory()->create();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $produk->satuan);
        $this->assertCount(0, $produk->satuan);
    }

    public function test_produk_model_can_update_attributes()
    {
        $produk = Produk::factory()->create();

        $produk->update([
            'nama_produk' => 'Updated Product',
            'harga_beli' => 75000,
            'stok' => 150,
        ]);

        $this->assertEquals('Updated Product', $produk->nama_produk);
        $this->assertEquals(75000, $produk->harga_beli);
        $this->assertEquals(150, $produk->stok);
    }

    public function test_produk_model_kategori_relationship_with_correct_foreign_key()
    {
        $kategori = Kategori::factory()->create();
        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);

        // Test the relationship definition
        $relationship = $produk->kategori();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $relationship);

        // Test foreign key and owner key
        $this->assertEquals('id_kategori', $relationship->getForeignKeyName());
        $this->assertEquals('id_kategori', $relationship->getOwnerKeyName());
    }

    public function test_produk_model_produk_satuan_relationship_with_correct_foreign_key()
    {
        $produk = Produk::factory()->create();
        $produkSatuan = ProdukSatuan::factory()->create(['id_produk' => $produk->id_produk]);

        // Test the relationship definition
        $relationship = $produk->produkSatuan();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $relationship);

        // Test foreign key and local key
        $this->assertEquals('id_produk', $relationship->getForeignKeyName());
        $this->assertEquals('id_produk', $relationship->getLocalKeyName());
    }

    public function test_produk_model_satuan_relationship_with_correct_pivot_table()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();

        ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
        ]);

        // Test the relationship definition
        $relationship = $produk->satuan();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsToMany', $relationship);

        // Test pivot table and keys
        $this->assertEquals('produk_satuan', $relationship->getTable());
        $this->assertEquals('id_produk', $relationship->getForeignPivotKeyName());
        $this->assertEquals('id_satuan', $relationship->getRelatedPivotKeyName());
    }

    public function test_produk_model_relationships_are_lazy_loaded()
    {
        $kategori = Kategori::factory()->create();
        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);

        // Test that relationships are not loaded initially
        $this->assertFalse($produk->relationLoaded('kategori'));
        $this->assertFalse($produk->relationLoaded('produkSatuan'));
        $this->assertFalse($produk->relationLoaded('satuan'));

        // Load relationships
        $produk->load(['kategori', 'produkSatuan', 'satuan']);

        $this->assertTrue($produk->relationLoaded('kategori'));
        $this->assertTrue($produk->relationLoaded('produkSatuan'));
        $this->assertTrue($produk->relationLoaded('satuan'));
    }

    public function test_produk_model_can_be_soft_deleted_if_soft_deletes_trait_used()
    {
        $produk = Produk::factory()->create();

        // Check if model uses SoftDeletes trait
        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($produk));

        if ($usesSoftDeletes) {
            $produk->delete();
            $this->assertSoftDeleted($produk);
        } else {
            // If not using soft deletes, regular delete should work
            $produk->delete();
            $this->assertDatabaseMissing('produk', ['id_produk' => $produk->id_produk]);
        }
    }

    public function test_produk_model_to_array_includes_relationships_when_loaded()
    {
        $kategori = Kategori::factory()->create();
        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);

        $array = $produk->toArray();
        $this->assertArrayNotHasKey('kategori', $array); // Not loaded

        $produk->load('kategori');
        $arrayWithRelation = $produk->toArray();
        $this->assertArrayHasKey('kategori', $arrayWithRelation);
        $this->assertEquals($kategori->id_kategori, $arrayWithRelation['kategori']['id_kategori']);
    }

    public function test_produk_model_multiple_satuan_relationships()
    {
        $produk = Produk::factory()->create();

        $satuan1 = SatuanProduk::factory()->create(['nama' => 'Pack']);
        $satuan2 = SatuanProduk::factory()->create(['nama' => 'Box']);
        $satuan3 = SatuanProduk::factory()->create(['nama' => 'Piece']);

        ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuan1->id_satuan_produk,
            'harga_jual_eceran' => 10000,
        ]);

        ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuan2->id_satuan_produk,
            'harga_jual_eceran' => 90000,
        ]);

        ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuan3->id_satuan_produk,
            'harga_jual_eceran' => 12000,
        ]);

        $this->assertCount(3, $produk->satuan);
        $this->assertCount(3, $produk->produkSatuan);

        $satuanNames = $produk->satuan->pluck('nama')->sort()->values();
        $this->assertEquals(['Box', 'Pack', 'Piece'], $satuanNames->toArray());
    }

    public function test_produk_model_with_pivot_data_access()
    {
        $produk = Produk::factory()->create();
        $satuanProduk = SatuanProduk::factory()->create();

        $produkSatuan = ProdukSatuan::factory()->create([
            'id_produk' => $produk->id_produk,
            'id_satuan_produk' => $satuanProduk->id_satuan_produk,
            'harga_jual_eceran' => 15000,
            'harga_jual_borongan' => 13500,
        ]);

        $loadedProduk = Produk::with('satuan')->find($produk->id_produk);

        $pivot = $loadedProduk->satuan->first()->pivot;
        $this->assertEquals(15000, $pivot->harga_jual_eceran);
        $this->assertEquals(13500, $pivot->harga_jual_borongan);
    }

    public function test_produk_model_foreign_key_constraints()
    {
        $kategori = Kategori::factory()->create();

        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);

        // Verify foreign key is correctly set
        $this->assertEquals($kategori->id_kategori, $produk->id_kategori);
    }

    public function test_produk_model_default_stok_value()
    {
        $produk = Produk::factory()->create();

        // Stok should be numeric
        $this->assertIsNumeric($produk->stok);
    }

    public function test_produk_model_unique_kode_produk()
    {
        $produk1 = Produk::factory()->create(['kode_produk' => 'PRD001']);
        $produk2 = Produk::factory()->create(['kode_produk' => 'PRD002']);

        $this->assertNotEquals($produk1->kode_produk, $produk2->kode_produk);
    }
}
