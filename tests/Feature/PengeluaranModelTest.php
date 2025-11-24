<?php

namespace Tests\Feature;

use App\Models\Pengeluaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengeluaranModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create setting first
        \App\Models\Setting::factory()->create();
    }

    public function test_pengeluaran_model_has_correct_table_name()
    {
        $pengeluaran = new Pengeluaran();

        $this->assertEquals('pengeluaran', $pengeluaran->getTable());
    }

    public function test_pengeluaran_model_has_correct_primary_key()
    {
        $pengeluaran = new Pengeluaran();

        $this->assertEquals('id_pengeluaran', $pengeluaran->getKeyName());
    }

    public function test_pengeluaran_model_has_correct_fillable_attributes()
    {
        $pengeluaran = new Pengeluaran();

        $expectedFillable = [
            'deskripsi',
            'nominal',
        ];

        $this->assertEquals($expectedFillable, $pengeluaran->getFillable());
    }

    public function test_pengeluaran_model_uses_has_factory_trait()
    {
        $pengeluaran = new Pengeluaran();

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($pengeluaran));
    }

    public function test_pengeluaran_model_can_be_created_with_factory()
    {
        $pengeluaran = Pengeluaran::factory()->create([
            'deskripsi' => 'Office Supplies Purchase',
            'nominal' => 500000,
        ]);

        $this->assertInstanceOf(Pengeluaran::class, $pengeluaran);
        $this->assertEquals('Office Supplies Purchase', $pengeluaran->deskripsi);
        $this->assertEquals(500000, $pengeluaran->nominal);
    }

    public function test_pengeluaran_model_has_timestamps()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        $this->assertNotNull($pengeluaran->created_at);
        $this->assertNotNull($pengeluaran->updated_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $pengeluaran->created_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $pengeluaran->updated_at);
    }

    public function test_pengeluaran_model_can_update_attributes()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        $pengeluaran->update([
            'deskripsi' => 'Updated Expense Description',
            'nominal' => 750000,
        ]);

        $this->assertEquals('Updated Expense Description', $pengeluaran->deskripsi);
        $this->assertEquals(750000, $pengeluaran->nominal);
    }

    public function test_pengeluaran_model_mass_assignment_protection()
    {
        $pengeluaran = new Pengeluaran();

        // Test that non-fillable attributes are protected
        $this->assertNotContains('id_pengeluaran', $pengeluaran->getFillable());
        $this->assertNotContains('created_at', $pengeluaran->getFillable());
        $this->assertNotContains('updated_at', $pengeluaran->getFillable());
    }

    public function test_pengeluaran_model_to_array_includes_all_attributes()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        $array = $pengeluaran->toArray();

        $this->assertArrayHasKey('id_pengeluaran', $array);
        $this->assertArrayHasKey('deskripsi', $array);
        $this->assertArrayHasKey('nominal', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_pengeluaran_model_search_by_description()
    {
        Pengeluaran::factory()->create(['deskripsi' => 'Office Supplies']);
        Pengeluaran::factory()->create(['deskripsi' => 'Electricity Bill']);
        Pengeluaran::factory()->create(['deskripsi' => 'Office Maintenance']);

        $officeResults = Pengeluaran::where('deskripsi', 'like', '%Office%')->get();
        $this->assertCount(2, $officeResults); // Office Supplies and Office Maintenance

        $billResults = Pengeluaran::where('deskripsi', 'Electricity Bill')->get();
        $this->assertCount(1, $billResults);
        $this->assertEquals('Electricity Bill', $billResults->first()->deskripsi);
    }

    public function test_pengeluaran_model_filter_by_nominal_range()
    {
        Pengeluaran::factory()->create(['nominal' => 100000]);
        Pengeluaran::factory()->create(['nominal' => 500000]);
        Pengeluaran::factory()->create(['nominal' => 1000000]);

        $smallExpenses = Pengeluaran::where('nominal', '<', 300000)->get();
        $this->assertCount(1, $smallExpenses);
        $this->assertEquals(100000, $smallExpenses->first()->nominal);

        $largeExpenses = Pengeluaran::where('nominal', '>', 300000)->get();
        $this->assertCount(2, $largeExpenses);
    }

    public function test_pengeluaran_model_ordering_by_nominal()
    {
        $expense1 = Pengeluaran::factory()->create(['nominal' => 100000]);
        $expense2 = Pengeluaran::factory()->create(['nominal' => 500000]);
        $expense3 = Pengeluaran::factory()->create(['nominal' => 250000]);

        $ordered = Pengeluaran::orderBy('nominal')->get();

        $this->assertEquals(100000, $ordered->first()->nominal);
        $this->assertEquals(500000, $ordered->last()->nominal);
    }

    public function test_pengeluaran_model_count_method_works()
    {
        Pengeluaran::factory()->count(5)->create();

        $this->assertEquals(5, Pengeluaran::count());
    }

    public function test_pengeluaran_model_sum_method_works()
    {
        Pengeluaran::factory()->create(['nominal' => 100000]);
        Pengeluaran::factory()->create(['nominal' => 200000]);
        Pengeluaran::factory()->create(['nominal' => 300000]);

        $total = Pengeluaran::sum('nominal');

        $this->assertEquals(600000, $total);
    }

    public function test_pengeluaran_model_find_method_works()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        $found = Pengeluaran::find($pengeluaran->id_pengeluaran);

        $this->assertInstanceOf(Pengeluaran::class, $found);
        $this->assertEquals($pengeluaran->id_pengeluaran, $found->id_pengeluaran);
    }

    public function test_pengeluaran_model_where_method_works()
    {
        $pengeluaran = Pengeluaran::factory()->create(['deskripsi' => 'Test Expense']);

        $found = Pengeluaran::where('deskripsi', 'Test Expense')->first();

        $this->assertInstanceOf(Pengeluaran::class, $found);
        $this->assertEquals('Test Expense', $found->deskripsi);
    }

    public function test_pengeluaran_model_delete_method_works()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        $pengeluaran->delete();

        $this->assertDatabaseMissing('pengeluaran', ['id_pengeluaran' => $pengeluaran->id_pengeluaran]);
    }

    public function test_pengeluaran_model_soft_delete_if_available()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        // Check if model uses SoftDeletes trait
        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($pengeluaran));

        if ($usesSoftDeletes) {
            $pengeluaran->delete();
            $this->assertSoftDeleted($pengeluaran);
        } else {
            $pengeluaran->delete();
            $this->assertDatabaseMissing('pengeluaran', ['id_pengeluaran' => $pengeluaran->id_pengeluaran]);
        }
    }

    public function test_pengeluaran_model_fill_method_works()
    {
        $pengeluaran = new Pengeluaran();

        $pengeluaran->fill([
            'deskripsi' => 'Fill Test Expense',
            'nominal' => 999999,
        ]);

        $this->assertEquals('Fill Test Expense', $pengeluaran->deskripsi);
        $this->assertEquals(999999, $pengeluaran->nominal);
    }

    public function test_pengeluaran_model_save_method_works()
    {
        $pengeluaran = new Pengeluaran();

        $pengeluaran->deskripsi = 'Save Test Expense';
        $pengeluaran->nominal = 888888;

        $pengeluaran->save();

        $this->assertDatabaseHas('pengeluaran', [
            'deskripsi' => 'Save Test Expense',
            'nominal' => 888888,
        ]);
    }

    public function test_pengeluaran_model_create_method_works()
    {
        $pengeluaran = Pengeluaran::create([
            'deskripsi' => 'Create Test Expense',
            'nominal' => 777777,
        ]);

        $this->assertInstanceOf(Pengeluaran::class, $pengeluaran);
        $this->assertEquals('Create Test Expense', $pengeluaran->deskripsi);
        $this->assertEquals(777777, $pengeluaran->nominal);
    }

    public function test_pengeluaran_model_update_method_works()
    {
        $pengeluaran = Pengeluaran::factory()->create(['nominal' => 100000]);

        $pengeluaran->update(['nominal' => 200000]);

        $this->assertEquals(200000, $pengeluaran->nominal);
        $this->assertDatabaseHas('pengeluaran', [
            'id_pengeluaran' => $pengeluaran->id_pengeluaran,
            'nominal' => 200000,
        ]);
    }

    public function test_pengeluaran_model_get_key_method_works()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        $this->assertEquals($pengeluaran->id_pengeluaran, $pengeluaran->getKey());
    }

    public function test_pengeluaran_model_get_table_method_works()
    {
        $pengeluaran = new Pengeluaran();

        $this->assertEquals('pengeluaran', $pengeluaran->getTable());
    }

    public function test_pengeluaran_model_get_key_name_method_works()
    {
        $pengeluaran = new Pengeluaran();

        $this->assertEquals('id_pengeluaran', $pengeluaran->getKeyName());
    }

    public function test_pengeluaran_model_exists_property_works()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        $this->assertTrue($pengeluaran->exists);

        $newPengeluaran = new Pengeluaran();
        $this->assertFalse($newPengeluaran->exists);
    }

    public function test_pengeluaran_model_fresh_method_works()
    {
        $pengeluaran = Pengeluaran::factory()->create(['nominal' => 100000]);

        // Update in database
        Pengeluaran::where('id_pengeluaran', $pengeluaran->id_pengeluaran)->update(['nominal' => 200000]);

        // Fresh should get updated data
        $fresh = $pengeluaran->fresh();
        $this->assertEquals(200000, $fresh->nominal);
    }

    public function test_pengeluaran_model_refresh_method_works()
    {
        $pengeluaran = Pengeluaran::factory()->create(['nominal' => 100000]);

        // Update in database
        Pengeluaran::where('id_pengeluaran', $pengeluaran->id_pengeluaran)->update(['nominal' => 200000]);

        // Refresh should update the current instance
        $pengeluaran->refresh();
        $this->assertEquals(200000, $pengeluaran->nominal);
    }

    public function test_pengeluaran_model_nominal_field_accepts_various_values()
    {
        $nominals = [1000, 50000, 1000000, 999999999];

        foreach ($nominals as $nominal) {
            $pengeluaran = Pengeluaran::factory()->create(['nominal' => $nominal]);
            $this->assertEquals($nominal, $pengeluaran->nominal);
        }
    }

    public function test_pengeluaran_model_description_field_accepts_long_text()
    {
        $longDescription = str_repeat('Expense description line ', 5) . 'with additional details.';

        $pengeluaran = Pengeluaran::factory()->create(['deskripsi' => $longDescription]);

        $this->assertEquals($longDescription, $pengeluaran->deskripsi);
        $this->assertDatabaseHas('pengeluaran', ['deskripsi' => $longDescription]);
    }

    public function test_pengeluaran_model_average_calculation()
    {
        Pengeluaran::factory()->create(['nominal' => 100000]);
        Pengeluaran::factory()->create(['nominal' => 200000]);
        Pengeluaran::factory()->create(['nominal' => 300000]);

        $average = Pengeluaran::avg('nominal');

        $this->assertEquals(200000, $average);
    }

    public function test_pengeluaran_model_max_nominal()
    {
        Pengeluaran::factory()->create(['nominal' => 50000]);
        Pengeluaran::factory()->create(['nominal' => 150000]);
        Pengeluaran::factory()->create(['nominal' => 75000]);

        $max = Pengeluaran::max('nominal');

        $this->assertEquals(150000, $max);
    }

    public function test_pengeluaran_model_min_nominal()
    {
        Pengeluaran::factory()->create(['nominal' => 50000]);
        Pengeluaran::factory()->create(['nominal' => 150000]);
        Pengeluaran::factory()->create(['nominal' => 75000]);

        $min = Pengeluaran::min('nominal');

        $this->assertEquals(50000, $min);
    }

    public function test_pengeluaran_model_date_filtering()
    {
        $today = now();
        $yesterday = now()->subDay();

        Pengeluaran::factory()->create([
            'deskripsi' => 'Today Expense',
            'created_at' => $today,
        ]);

        Pengeluaran::factory()->create([
            'deskripsi' => 'Yesterday Expense',
            'created_at' => $yesterday,
        ]);

        $todayExpenses = Pengeluaran::whereDate('created_at', $today->toDateString())->get();
        $this->assertCount(1, $todayExpenses);
        $this->assertEquals('Today Expense', $todayExpenses->first()->deskripsi);
    }

    public function test_pengeluaran_model_bulk_operations()
    {
        $expenses = Pengeluaran::factory()->count(5)->create();

        // Test that all expenses were created
        $this->assertEquals(5, Pengeluaran::count());

        // Test bulk update
        Pengeluaran::where('id_pengeluaran', '>', 0)->update(['nominal' => 100000]);

        $updatedCount = Pengeluaran::where('nominal', 100000)->count();
        $this->assertEquals(5, $updatedCount);
    }

    public function test_pengeluaran_model_pagination()
    {
        Pengeluaran::factory()->count(10)->create();

        $paginated = Pengeluaran::paginate(5);

        $this->assertCount(5, $paginated->items());
        $this->assertEquals(10, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
    }
}
