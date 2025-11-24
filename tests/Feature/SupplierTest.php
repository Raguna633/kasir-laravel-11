<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
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

    public function test_user_can_view_supplier_index()
    {
        $response = $this->get(route('supplier.index'));

        $response->assertStatus(200);
        $response->assertViewIs('supplier.index');
    }

    public function test_user_can_get_supplier_data()
    {
        Supplier::factory()->count(3)->create();

        $response = $this->get(route('supplier.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id_supplier',
                    'nama',
                    'alamat',
                    'telepon',
                    'created_at',
                    'updated_at',
                    'aksi'
                ]
            ]
        ]);
    }

    public function test_user_can_create_supplier()
    {
        $supplierData = [
            'nama' => 'PT. Supplier Indonesia',
            'telepon' => '021-12345678',
            'alamat' => 'Jl. Supplier No. 123, Jakarta'
        ];

        $response = $this->post(route('supplier.store'), $supplierData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('supplier', $supplierData);
    }

    public function test_user_can_view_supplier_detail()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->get(route('supplier.show', $supplier->id_supplier));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id_supplier',
            'nama',
            'alamat',
            'telepon'
        ]);
    }

    public function test_user_can_update_supplier()
    {
        $supplier = Supplier::factory()->create();

        $updateData = [
            'nama' => 'PT. Updated Supplier',
            'telepon' => '021-87654321',
            'alamat' => 'Jl. Updated No. 456, Jakarta'
        ];

        $response = $this->put(route('supplier.update', $supplier->id_supplier), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('supplier', $updateData);
    }

    public function test_user_can_delete_supplier()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->delete(route('supplier.destroy', $supplier->id_supplier));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('supplier', ['id_supplier' => $supplier->id_supplier]);
    }

    public function test_validation_fails_when_creating_supplier_without_required_fields()
    {
        $invalidData = [
            'telepon' => '021-12345678',
            'alamat' => 'Jl. Supplier No. 123'
            // Missing 'nama'
        ];

        $response = $this->post(route('supplier.store'), $invalidData);

        $response->assertStatus(302); // Redirect with validation errors
        $response->assertSessionHasErrors('nama');
    }

    public function test_validation_fails_when_creating_supplier_with_invalid_data()
    {
        $invalidData = [
            'nama' => str_repeat('A', 256), // Too long
            'telepon' => str_repeat('1', 21), // Too long
            'alamat' => str_repeat('A', 501), // Too long
        ];

        $response = $this->post(route('supplier.store'), $invalidData);

        $response->assertStatus(302); // Redirect with validation errors
        $response->assertSessionHasErrors(['nama', 'telepon', 'alamat']);
    }

    public function test_non_admin_user_cannot_access_supplier_routes()
    {
        $user = User::factory()->create();
        $user->level = 2; // Non-admin level
        $user->save();

        $this->actingAs($user);

        $response = $this->get(route('supplier.index'));

        $response->assertStatus(403); // Forbidden
    }
}
