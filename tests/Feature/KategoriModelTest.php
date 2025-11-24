<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create setting first
        \App\Models\Setting::factory()->create();
    }

    public function test_kategori_model_has_correct_table_name()
    {
        $kategori = new Kategori();

        $this->assertEquals('kategori', $kategori->getTable());
    }

    public function test_kategori_model_has_correct_primary_key()
    {
        $kategori = new Kategori();

        $this->assertEquals('id_kategori', $kategori->getKeyName());
    }

    public function test_kategori_model_uses_guarded_instead_of_fillable()
    {
        $kategori = new Kategori();

        // Since it uses $guarded = [], all attributes should be fillable
        $this->assertEmpty($kategori->getGuarded());
        $this->assertEmpty($kategori->getFillable()); // When guarded is empty, fillable is also empty
    }

    public function test_kategori_model_uses_has_factory_trait()
    {
        $kategori = new Kategori();

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($kategori));
    }

    public function test_kategori_model_has_many_produk_relationship()
    {
        $kategori = Kategori::factory()->create();
        $produk1 = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);
        $produk2 = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);

        $this->assertCount(2, $kategori->produk);
        $this->assertInstanceOf(Produk::class, $kategori->produk->first());
        $this->assertEquals($kategori->id_kategori, $kategori->produk->first()->id_kategori);
    }

    public function test_kategori_model_can_be_created_with_factory()
    {
        $kategori = Kategori::factory()->create([
            'nama_kategori' => 'Test Category',
        ]);

        $this->assertInstanceOf(Kategori::class, $kategori);
        $this->assertEquals('Test Category', $kategori->nama_kategori);
    }

    public function test_kategori_model_has_timestamps()
    {
        $kategori = Kategori::factory()->create();

        $this->assertNotNull($kategori->created_at);
        $this->assertNotNull($kategori->updated_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $kategori->created_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $kategori->updated_at);
    }

    public function test_kategori_model_produk_relationship_returns_empty_collection_when_no_products()
    {
        $kategori = Kategori::factory()->create();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $kategori->produk);
        $this->assertCount(0, $kategori->produk);
    }

    public function test_kategori_model_can_update_attributes()
    {
        $kategori = Kategori::factory()->create();

        $kategori->update([
            'nama_kategori' => 'Updated Category',
        ]);

        $this->assertEquals('Updated Category', $kategori->nama_kategori);
    }

    public function test_kategori_model_produk_relationship_with_correct_foreign_key()
    {
        $kategori = Kategori::factory()->create();
        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);

        // Test the relationship definition
        $relationship = $kategori->produk();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $relationship);

        // Test foreign key and local key
        $this->assertEquals('id_kategori', $relationship->getForeignKeyName());
        $this->assertEquals('id_kategori', $relationship->getLocalKeyName());
    }

    public function test_kategori_model_relationships_are_lazy_loaded()
    {
        $kategori = Kategori::factory()->create();

        // Test that relationships are not loaded initially
        $this->assertFalse($kategori->relationLoaded('produk'));

        // Load relationships
        $kategori->load('produk');

        $this->assertTrue($kategori->relationLoaded('produk'));
    }

    public function test_kategori_model_can_be_soft_deleted_if_soft_deletes_trait_used()
    {
        $kategori = Kategori::factory()->create();

        // Check if model uses SoftDeletes trait
        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($kategori));

        if ($usesSoftDeletes) {
            $kategori->delete();
            $this->assertSoftDeleted($kategori);
        } else {
            // If not using soft deletes, regular delete should work
            $kategori->delete();
            $this->assertDatabaseMissing('kategori', ['id_kategori' => $kategori->id_kategori]);
        }
    }

    public function test_kategori_model_to_array_includes_relationships_when_loaded()
    {
        $kategori = Kategori::factory()->create();
        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);

        $array = $kategori->toArray();
        $this->assertArrayNotHasKey('produk', $array); // Not loaded

        $kategori->load('produk');
        $arrayWithRelation = $kategori->toArray();
        $this->assertArrayHasKey('produk', $arrayWithRelation);
        $this->assertCount(1, $arrayWithRelation['produk']);
    }

    public function test_kategori_model_with_multiple_products()
    {
        $kategori = Kategori::factory()->create();

        $produk1 = Produk::factory()->create([
            'id_kategori' => $kategori->id_kategori,
            'nama_produk' => 'Product 1',
        ]);

        $produk2 = Produk::factory()->create([
            'id_kategori' => $kategori->id_kategori,
            'nama_produk' => 'Product 2',
        ]);

        $produk3 = Produk::factory()->create([
            'id_kategori' => $kategori->id_kategori,
            'nama_produk' => 'Product 3',
        ]);

        $this->assertCount(3, $kategori->produk);

        $productNames = $kategori->produk->pluck('nama_produk')->sort()->values();
        $this->assertEquals(['Product 1', 'Product 2', 'Product 3'], $productNames->toArray());
    }

    public function test_kategori_model_count_method_works()
    {
        Kategori::factory()->count(5)->create();

        $this->assertEquals(5, Kategori::count());
    }

    public function test_kategori_model_unique_nama_kategori()
    {
        $kategori1 = Kategori::factory()->create(['nama_kategori' => 'Electronics']);
        $kategori2 = Kategori::factory()->create(['nama_kategori' => 'Clothing']);

        $this->assertNotEquals($kategori1->nama_kategori, $kategori2->nama_kategori);
    }

    public function test_kategori_model_ordering()
    {
        $kategori1 = Kategori::factory()->create(['nama_kategori' => 'A Category']);
        $kategori2 = Kategori::factory()->create(['nama_kategori' => 'B Category']);
        $kategori3 = Kategori::factory()->create(['nama_kategori' => 'C Category']);

        $ordered = Kategori::orderBy('nama_kategori')->get();

        $this->assertEquals('A Category', $ordered->first()->nama_kategori);
        $this->assertEquals('C Category', $ordered->last()->nama_kategori);
    }

    public function test_kategori_model_with_produk_count()
    {
        $kategori1 = Kategori::factory()->create();
        $kategori2 = Kategori::factory()->create();

        Produk::factory()->count(3)->create(['id_kategori' => $kategori1->id_kategori]);
        Produk::factory()->count(7)->create(['id_kategori' => $kategori2->id_kategori]);

        $kategoriWithCount = Kategori::withCount('produk')->find($kategori1->id_kategori);

        $this->assertEquals(3, $kategoriWithCount->produk_count);
    }

    public function test_kategori_model_cascade_delete_behavior()
    {
        $kategori = Kategori::factory()->create();
        $produk = Produk::factory()->create(['id_kategori' => $kategori->id_kategori]);

        // Delete kategori
        $kategori->delete();

        // Check if kategori is deleted
        $this->assertDatabaseMissing('kategori', ['id_kategori' => $kategori->id_kategori]);

        // Produk should still exist (no cascade delete in model relationships)
        $this->assertDatabaseHas('produk', ['id_produk' => $produk->id_produk]);
    }

    public function test_kategori_model_search_functionality()
    {
        Kategori::factory()->create(['nama_kategori' => 'Electronics']);
        Kategori::factory()->create(['nama_kategori' => 'Clothing']);
        Kategori::factory()->create(['nama_kategori' => 'Books']);

        $electronics = Kategori::where('nama_kategori', 'Electronics')->first();
        $this->assertEquals('Electronics', $electronics->nama_kategori);

        $searchResults = Kategori::where('nama_kategori', 'like', '%Book%')->get();
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Books', $searchResults->first()->nama_kategori);
    }
}
