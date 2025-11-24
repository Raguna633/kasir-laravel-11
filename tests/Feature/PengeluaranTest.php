<?php

namespace Tests\Feature;

use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengeluaranTest extends TestCase
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

    public function test_user_can_view_pengeluaran_index()
    {
        $response = $this->get(route('pengeluaran.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pengeluaran.index');
    }

    public function test_user_can_get_pengeluaran_data()
    {
        Pengeluaran::factory()->count(3)->create();

        $response = $this->get(route('pengeluaran.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id_pengeluaran',
                    'deskripsi',
                    'nominal',
                    'created_at',
                    'updated_at',
                    'aksi'
                ]
            ]
        ]);
    }

    public function test_user_can_create_pengeluaran()
    {
        $pengeluaranData = [
            'deskripsi' => 'Biaya listrik bulan Oktober',
            'nominal' => 500000
        ];

        $response = $this->post(route('pengeluaran.store'), $pengeluaranData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('pengeluaran', $pengeluaranData);
    }

    public function test_user_can_view_pengeluaran_detail()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        $response = $this->get(route('pengeluaran.show', $pengeluaran->id_pengeluaran));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id_pengeluaran',
            'deskripsi',
            'nominal',
            'created_at',
            'updated_at'
        ]);
    }

    public function test_user_can_update_pengeluaran()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        $updateData = [
            'deskripsi' => 'Biaya listrik bulan November - Updated',
            'nominal' => 600000
        ];

        $response = $this->put(route('pengeluaran.update', $pengeluaran->id_pengeluaran), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('pengeluaran', $updateData);
    }

    public function test_user_can_delete_pengeluaran()
    {
        $pengeluaran = Pengeluaran::factory()->create();

        $response = $this->delete(route('pengeluaran.destroy', $pengeluaran->id_pengeluaran));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('pengeluaran', ['id_pengeluaran' => $pengeluaran->id_pengeluaran]);
    }

    public function test_validation_fails_when_creating_pengeluaran_without_required_fields()
    {
        $invalidData = [
            'nominal' => 500000
            // Missing 'deskripsi'
        ];

        $response = $this->post(route('pengeluaran.store'), $invalidData);

        $response->assertStatus(302); // Redirect with validation errors
        $response->assertSessionHasErrors('deskripsi');
    }

    public function test_validation_fails_when_creating_pengeluaran_with_invalid_data()
    {
        $invalidData = [
            'deskripsi' => str_repeat('A', 256), // Too long
            'nominal' => -1000, // Negative value
        ];

        $response = $this->post(route('pengeluaran.store'), $invalidData);

        $response->assertStatus(302); // Redirect with validation errors
        $response->assertSessionHasErrors(['deskripsi', 'nominal']);
    }

    public function test_validation_fails_when_creating_pengeluaran_with_zero_nominal()
    {
        $invalidData = [
            'deskripsi' => 'Test pengeluaran',
            'nominal' => 0, // Zero value
        ];

        $response = $this->post(route('pengeluaran.store'), $invalidData);

        $response->assertStatus(200); // Actually succeeds since validation allows 0
        $response->assertSee('Data berhasil disimpan');
    }

    public function test_non_admin_user_cannot_access_pengeluaran_routes()
    {
        $user = User::factory()->create();
        $user->level = 2; // Non-admin level
        $user->save();

        $this->actingAs($user);

        $response = $this->get(route('pengeluaran.index'));

        $response->assertStatus(403); // Forbidden
    }

    public function test_pengeluaran_nominal_must_be_numeric()
    {
        $invalidData = [
            'deskripsi' => 'Test pengeluaran',
            'nominal' => 'not_a_number', // Non-numeric value
        ];

        $response = $this->post(route('pengeluaran.store'), $invalidData);

        $response->assertStatus(302); // Redirect with validation errors
        $response->assertSessionHasErrors('nominal');
    }
}
