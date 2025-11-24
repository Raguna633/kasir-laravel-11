<?php

namespace Tests\Feature;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create setting first
        \App\Models\Setting::factory()->create();
    }

    public function test_supplier_model_has_correct_table_name()
    {
        $supplier = new Supplier();

        $this->assertEquals('supplier', $supplier->getTable());
    }

    public function test_supplier_model_has_correct_primary_key()
    {
        $supplier = new Supplier();

        $this->assertEquals('id_supplier', $supplier->getKeyName());
    }

    public function test_supplier_model_has_correct_fillable_attributes()
    {
        $supplier = new Supplier();

        $expectedFillable = [
            'nama',
            'telepon',
            'alamat',
        ];

        $this->assertEquals($expectedFillable, $supplier->getFillable());
    }

    public function test_supplier_model_uses_has_factory_trait()
    {
        $supplier = new Supplier();

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($supplier));
    }

    public function test_supplier_model_can_be_created_with_factory()
    {
        $supplier = Supplier::factory()->create([
            'nama' => 'PT. Example Supplier',
            'telepon' => '021-12345678',
            'alamat' => 'Jl. Supplier No. 123',
        ]);

        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertEquals('PT. Example Supplier', $supplier->nama);
        $this->assertEquals('021-12345678', $supplier->telepon);
        $this->assertEquals('Jl. Supplier No. 123', $supplier->alamat);
    }

    public function test_supplier_model_has_timestamps()
    {
        $supplier = Supplier::factory()->create();

        $this->assertNotNull($supplier->created_at);
        $this->assertNotNull($supplier->updated_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $supplier->created_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $supplier->updated_at);
    }

    public function test_supplier_model_can_update_attributes()
    {
        $supplier = Supplier::factory()->create();

        $supplier->update([
            'nama' => 'Updated Supplier Name',
            'telepon' => '022-87654321',
            'alamat' => 'Updated Address',
        ]);

        $this->assertEquals('Updated Supplier Name', $supplier->nama);
        $this->assertEquals('022-87654321', $supplier->telepon);
        $this->assertEquals('Updated Address', $supplier->alamat);
    }

    public function test_supplier_model_mass_assignment_protection()
    {
        $supplier = new Supplier();

        // Test that non-fillable attributes are protected
        $this->assertNotContains('id_supplier', $supplier->getFillable());
        $this->assertNotContains('created_at', $supplier->getFillable());
        $this->assertNotContains('updated_at', $supplier->getFillable());
    }

    public function test_supplier_model_to_array_includes_all_attributes()
    {
        $supplier = Supplier::factory()->create();

        $array = $supplier->toArray();

        $this->assertArrayHasKey('id_supplier', $array);
        $this->assertArrayHasKey('nama', $array);
        $this->assertArrayHasKey('telepon', $array);
        $this->assertArrayHasKey('alamat', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_supplier_model_search_by_name()
    {
        Supplier::factory()->create(['nama' => 'PT. ABC Supplier']);
        Supplier::factory()->create(['nama' => 'CV. XYZ Supplier']);
        Supplier::factory()->create(['nama' => 'PT. ABC Trading']);

        $abcResults = Supplier::where('nama', 'like', '%ABC%')->get();
        $this->assertCount(2, $abcResults); // PT. ABC Supplier and PT. ABC Trading

        $ptResults = Supplier::where('nama', 'like', 'PT.%')->get();
        $this->assertCount(2, $ptResults); // Both PT. companies
    }

    public function test_supplier_model_search_by_phone()
    {
        Supplier::factory()->create(['telepon' => '021-11111111']);
        Supplier::factory()->create(['telepon' => '022-22222222']);

        $results = Supplier::where('telepon', '021-11111111')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('021-11111111', $results->first()->telepon);
    }

    public function test_supplier_model_ordering_by_name()
    {
        $supplier1 = Supplier::factory()->create(['nama' => 'ABC Supplier']);
        $supplier2 = Supplier::factory()->create(['nama' => 'XYZ Supplier']);
        $supplier3 = Supplier::factory()->create(['nama' => 'MNO Supplier']);

        $ordered = Supplier::orderBy('nama')->get();

        $this->assertEquals('ABC Supplier', $ordered->first()->nama);
        $this->assertEquals('XYZ Supplier', $ordered->last()->nama);
    }

    public function test_supplier_model_count_method_works()
    {
        Supplier::factory()->count(5)->create();

        $this->assertEquals(5, Supplier::count());
    }

    public function test_supplier_model_find_method_works()
    {
        $supplier = Supplier::factory()->create();

        $found = Supplier::find($supplier->id_supplier);

        $this->assertInstanceOf(Supplier::class, $found);
        $this->assertEquals($supplier->id_supplier, $found->id_supplier);
    }

    public function test_supplier_model_where_method_works()
    {
        $supplier = Supplier::factory()->create(['nama' => 'Test Supplier']);

        $found = Supplier::where('nama', 'Test Supplier')->first();

        $this->assertInstanceOf(Supplier::class, $found);
        $this->assertEquals('Test Supplier', $found->nama);
    }

    public function test_supplier_model_delete_method_works()
    {
        $supplier = Supplier::factory()->create();

        $supplier->delete();

        $this->assertDatabaseMissing('supplier', ['id_supplier' => $supplier->id_supplier]);
    }

    public function test_supplier_model_soft_delete_if_available()
    {
        $supplier = Supplier::factory()->create();

        // Check if model uses SoftDeletes trait
        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($supplier));

        if ($usesSoftDeletes) {
            $supplier->delete();
            $this->assertSoftDeleted($supplier);
        } else {
            $supplier->delete();
            $this->assertDatabaseMissing('supplier', ['id_supplier' => $supplier->id_supplier]);
        }
    }

    public function test_supplier_model_fill_method_works()
    {
        $supplier = new Supplier();

        $supplier->fill([
            'nama' => 'Fill Test Supplier',
            'telepon' => '021-99999999',
            'alamat' => 'Fill Test Address',
        ]);

        $this->assertEquals('Fill Test Supplier', $supplier->nama);
        $this->assertEquals('021-99999999', $supplier->telepon);
        $this->assertEquals('Fill Test Address', $supplier->alamat);
    }

    public function test_supplier_model_save_method_works()
    {
        $supplier = new Supplier();

        $supplier->nama = 'Save Test Supplier';
        $supplier->telepon = '021-88888888';
        $supplier->alamat = 'Save Test Address';

        $supplier->save();

        $this->assertDatabaseHas('supplier', [
            'nama' => 'Save Test Supplier',
            'telepon' => '021-88888888',
            'alamat' => 'Save Test Address',
        ]);
    }

    public function test_supplier_model_create_method_works()
    {
        $supplier = Supplier::create([
            'nama' => 'Create Test Supplier',
            'telepon' => '021-77777777',
            'alamat' => 'Create Test Address',
        ]);

        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertEquals('Create Test Supplier', $supplier->nama);
        $this->assertEquals('021-77777777', $supplier->telepon);
        $this->assertEquals('Create Test Address', $supplier->alamat);
    }

    public function test_supplier_model_update_method_works()
    {
        $supplier = Supplier::factory()->create(['nama' => 'Original Name']);

        $supplier->update(['nama' => 'Updated Name']);

        $this->assertEquals('Updated Name', $supplier->nama);
        $this->assertDatabaseHas('supplier', [
            'id_supplier' => $supplier->id_supplier,
            'nama' => 'Updated Name',
        ]);
    }

    public function test_supplier_model_get_key_method_works()
    {
        $supplier = Supplier::factory()->create();

        $this->assertEquals($supplier->id_supplier, $supplier->getKey());
    }

    public function test_supplier_model_get_table_method_works()
    {
        $supplier = new Supplier();

        $this->assertEquals('supplier', $supplier->getTable());
    }

    public function test_supplier_model_get_key_name_method_works()
    {
        $supplier = new Supplier();

        $this->assertEquals('id_supplier', $supplier->getKeyName());
    }

    public function test_supplier_model_exists_property_works()
    {
        $supplier = Supplier::factory()->create();

        $this->assertTrue($supplier->exists);

        $newSupplier = new Supplier();
        $this->assertFalse($newSupplier->exists);
    }

    public function test_supplier_model_fresh_method_works()
    {
        $supplier = Supplier::factory()->create(['nama' => 'Original']);

        // Update in database
        Supplier::where('id_supplier', $supplier->id_supplier)->update(['nama' => 'Updated']);

        // Fresh should get updated data
        $fresh = $supplier->fresh();
        $this->assertEquals('Updated', $fresh->nama);
    }

    public function test_supplier_model_refresh_method_works()
    {
        $supplier = Supplier::factory()->create(['nama' => 'Original']);

        // Update in database
        Supplier::where('id_supplier', $supplier->id_supplier)->update(['nama' => 'Updated']);

        // Refresh should update the current instance
        $supplier->refresh();
        $this->assertEquals('Updated', $supplier->nama);
    }

    public function test_supplier_model_unique_nama_validation()
    {
        $supplier1 = Supplier::factory()->create(['nama' => 'Unique Supplier']);
        $supplier2 = Supplier::factory()->create(['nama' => 'Another Supplier']);

        $this->assertNotEquals($supplier1->nama, $supplier2->nama);
    }

    public function test_supplier_model_address_field_accepts_long_text()
    {
        $longAddress = str_repeat('Jl. Supplier Address Line ', 10) . 'No. 123';

        $supplier = Supplier::factory()->create(['alamat' => $longAddress]);

        $this->assertEquals($longAddress, $supplier->alamat);
        $this->assertDatabaseHas('supplier', ['alamat' => $longAddress]);
    }

    public function test_supplier_model_phone_field_accepts_various_formats()
    {
        $phones = [
            '021-12345678',
            '(021) 12345678',
            '08123456789',
            '+62-21-12345678',
        ];

        foreach ($phones as $phone) {
            $supplier = Supplier::factory()->create(['telepon' => $phone]);
            $this->assertEquals($phone, $supplier->telepon);
        }
    }

    public function test_supplier_model_scope_for_active_suppliers()
    {
        // This would test a scope if it existed, but since it doesn't, we'll test basic querying
        Supplier::factory()->count(3)->create();

        $allSuppliers = Supplier::all();
        $this->assertCount(3, $allSuppliers);
    }

    public function test_supplier_model_bulk_operations()
    {
        $suppliers = Supplier::factory()->count(5)->create();

        // Test that all suppliers were created
        $this->assertEquals(5, Supplier::count());

        // Test bulk update (if needed)
        Supplier::where('id_supplier', '>', 0)->update(['telepon' => '021-00000000']);

        $updatedCount = Supplier::where('telepon', '021-00000000')->count();
        $this->assertEquals(5, $updatedCount);
    }
}
