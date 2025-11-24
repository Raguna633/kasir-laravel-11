<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTest extends TestCase
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

    public function test_user_can_view_member_index()
    {
        $response = $this->get(route('member.index'));

        $response->assertStatus(200);
        $response->assertViewIs('member.index');
    }

    public function test_user_can_get_member_data()
    {
        Member::factory()->count(3)->create();

        $response = $this->get(route('member.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id_member',
                    'kode_member',
                    'nama',
                    'alamat',
                    'telepon',
                    'created_at',
                    'updated_at',
                    'select_all',
                    'aksi'
                ]
            ]
        ]);
    }

    public function test_user_can_create_member()
    {
        $memberData = [
            'nama' => 'John Doe',
            'telepon' => '081234567890',
            'alamat' => 'Jl. Test No. 123'
        ];

        $response = $this->post(route('member.store'), $memberData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('member', [
            'nama' => 'John Doe',
            'telepon' => '081234567890',
            'alamat' => 'Jl. Test No. 123'
        ]);
    }

    public function test_user_can_view_member_detail()
    {
        $member = Member::factory()->create();

        $response = $this->get(route('member.show', $member->id_member));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id_member',
            'kode_member',
            'nama',
            'alamat',
            'telepon'
        ]);
    }

    public function test_user_can_update_member()
    {
        $member = Member::factory()->create();

        $updateData = [
            'nama' => 'Jane Doe Updated',
            'telepon' => '081987654321',
            'alamat' => 'Jl. Updated No. 456'
        ];

        $response = $this->put(route('member.update', $member->id_member), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('member', $updateData);
    }

    public function test_user_can_delete_member()
    {
        $member = Member::factory()->create();

        $response = $this->delete(route('member.destroy', $member->id_member));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('member', ['id_member' => $member->id_member]);
    }

    public function test_validation_fails_when_creating_member_without_required_fields()
    {
        $invalidData = [
            'telepon' => '081234567890',
            'alamat' => 'Jl. Test No. 123'
            // Missing 'nama'
        ];

        $response = $this->post(route('member.store'), $invalidData);

        $response->assertStatus(302); // Redirect with validation errors
        $response->assertSessionHasErrors('nama');
    }

    public function test_validation_fails_when_creating_member_with_invalid_data()
    {
        $invalidData = [
            'nama' => str_repeat('A', 256), // Too long
            'telepon' => str_repeat('1', 21), // Too long
            'alamat' => str_repeat('A', 501), // Too long
        ];

        $response = $this->post(route('member.store'), $invalidData);

        $response->assertStatus(302); // Redirect with validation errors
        $response->assertSessionHasErrors(['nama', 'telepon', 'alamat']);
    }

    public function test_non_admin_user_cannot_access_member_routes()
    {
        $user = User::factory()->create();
        $user->level = 2; // Non-admin level
        $user->save();

        $this->actingAs($user);

        $response = $this->get(route('member.index'));

        $response->assertStatus(403); // Forbidden
    }

    public function test_member_kode_member_is_auto_generated()
    {
        $memberData = [
            'nama' => 'Test Member',
            'telepon' => '081234567890',
            'alamat' => 'Jl. Test No. 123'
        ];

        $this->post(route('member.store'), $memberData);

        $member = Member::where('nama', 'Test Member')->first();

        $this->assertNotNull($member->kode_member);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $member->kode_member);
    }
}
