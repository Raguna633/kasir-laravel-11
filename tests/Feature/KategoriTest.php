<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriTest extends TestCase
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

    public function test_user_can_view_kategori_index()
    {
        $response = $this->get(route('kategori.index'));

        $response->assertStatus(200);
        $response->assertViewIs('kategori.index');
    }

    public function test_user_can_get_kategori_data()
    {
        Kategori::factory()->count(3)->create();

        $response = $this->get(route('kategori.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id_kategori',
                    'nama_kategori',
                    'created_at',
                    'updated_at',
                    'aksi'
                ]
            ]
        ]);
    }

    public function test_user_can_create_kategori()
    {
        $kategoriData = [
            'nama_kategori' => 'Kategori Test'
        ];

        $response = $this->post(route('kategori.store'), $kategoriData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('kategori', $kategoriData);
    }

    public function test_user_can_view_kategori_detail()
    {
        $kategori = Kategori::factory()->create();

        $response = $this->get(route('kategori.show', $kategori->id_kategori));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'nama_kategori',
            'produk'
        ]);
    }

    public function test_user_can_update_kategori()
    {
        $kategori = Kategori::factory()->create();

        $updateData = [
            'nama_kategori' => 'Kategori Updated'
        ];

        $response = $this->put(route('kategori.update', $kategori->id_kategori), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('kategori', $updateData);
    }

    public function test_user_can_delete_kategori()
    {
        $kategori = Kategori::factory()->create();

        $response = $this->delete(route('kategori.destroy', $kategori->id_kategori));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('kategori', ['id_kategori' => $kategori->id_kategori]);
    }

    public function test_validation_fails_when_creating_duplicate_kategori()
    {
        // Create first kategori
        Kategori::factory()->create(['nama_kategori' => 'Duplicate Kategori']);

        // Try to create duplicate - this should return a 500 error due to database constraint
        $duplicateData = [
            'nama_kategori' => 'Duplicate Kategori'
        ];

        $response = $this->post(route('kategori.store'), $duplicateData);

        $response->assertStatus(500); // Database constraint violation
    }

    public function test_non_admin_user_cannot_access_kategori_routes()
    {
        $user = User::factory()->create();
        $user->level = 2; // Non-admin level
        $user->save();

        $this->actingAs($user);

        $response = $this->get(route('kategori.index'));

        $response->assertStatus(403); // Forbidden
    }
}
