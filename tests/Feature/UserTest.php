<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create setting first
        \App\Models\Setting::factory()->create();

        // Create admin user for authentication
        $adminUser = User::factory()->create();
        $adminUser->level = 1; // Admin level
        $adminUser->save();

        $this->actingAs($adminUser);
    }

    public function test_admin_can_view_user_index()
    {
        $response = $this->get(route('user.index'));

        $response->assertStatus(200);
        $response->assertViewIs('user.index');
    }

    public function test_admin_can_get_user_data()
    {
        User::factory()->count(3)->create(['level' => 2]); // Create non-admin users

        $response = $this->get(route('user.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'level',
                    'foto',
                    'aksi'
                ]
            ]
        ]);
    }

    public function test_admin_can_create_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $response = $this->post(route('user.store'), $userData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'level' => 2, // Should be set to 2 (non-admin)
        ]);

        // Verify password is hashed
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_admin_can_view_user_detail()
    {
        $user = User::factory()->create(['level' => 2]);

        $response = $this->get(route('user.show', $user->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'email',
            'level',
            'foto'
        ]);
    }

    public function test_admin_can_update_user()
    {
        $user = User::factory()->create(['level' => 2]);

        $updateData = [
            'name' => 'Jane Doe Updated',
            'email' => 'jane@example.com',
            'password' => 'newpassword123'
        ];

        $response = $this->put(route('user.update', $user->id), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe Updated',
            'email' => 'jane@example.com',
        ]);

        // Verify password is updated and hashed
        $updatedUser = User::find($user->id);
        $this->assertTrue(Hash::check('newpassword123', $updatedUser->password));
    }

    public function test_admin_can_update_user_without_password()
    {
        $user = User::factory()->create(['level' => 2]);

        $updateData = [
            'name' => 'Jane Doe Updated',
            'email' => 'jane@example.com'
            // No password provided
        ];

        $response = $this->put(route('user.update', $user->id), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe Updated',
            'email' => 'jane@example.com',
        ]);

        // Verify password is not changed
        $updatedUser = User::find($user->id);
        $this->assertTrue(Hash::check('password', $updatedUser->password)); // Original password
    }

    public function test_admin_can_delete_user()
    {
        $user = User::factory()->create(['level' => 2]);

        $response = $this->delete(route('user.destroy', $user->id));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_can_view_own_profile()
    {
        $user = User::factory()->create(['level' => 2]);
        $this->actingAs($user);

        $response = $this->get(route('user.profil'));

        $response->assertStatus(200);
        $response->assertViewIs('user.profil');
        $response->assertViewHas('profil');
    }

    public function test_user_can_update_own_profile()
    {
        $user = User::factory()->create(['level' => 2, 'password' => Hash::make('oldpassword')]);
        $this->actingAs($user);

        $updateData = [
            'name' => 'Updated Name',
            'old_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ];

        $response = $this->post(route('user.update_profil'), $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        // Verify password is updated
        $updatedUser = User::find($user->id);
        $this->assertTrue(Hash::check('newpassword123', $updatedUser->password));
    }

    public function test_user_cannot_update_profile_with_wrong_old_password()
    {
        $user = User::factory()->create(['level' => 2, 'password' => Hash::make('oldpassword')]);
        $this->actingAs($user);

        $updateData = [
            'name' => 'Updated Name',
            'old_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ];

        $response = $this->post(route('user.update_profil'), $updateData);

        $response->assertStatus(422);
        $response->assertSee('Password lama tidak sesuai');
    }

    public function test_user_cannot_update_profile_with_mismatched_password_confirmation()
    {
        $user = User::factory()->create(['level' => 2, 'password' => Hash::make('oldpassword')]);
        $this->actingAs($user);

        $updateData = [
            'name' => 'Updated Name',
            'old_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword'
        ];

        $response = $this->post(route('user.update_profil'), $updateData);

        $response->assertStatus(422);
        $response->assertSee('Konfirmasi password tidak sesuai');
    }

    public function test_user_can_update_profile_photo()
    {
        $user = User::factory()->create(['level' => 2]);
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('profile.jpg');

        $updateData = [
            'name' => $user->name,
            'foto' => $file
        ];

        $response = $this->post(route('user.update_profil'), $updateData);

        $response->assertStatus(200);

        $updatedUser = User::find($user->id);
        $this->assertStringContains('/img/', $updatedUser->foto);
        $this->assertStringContains('logo-', $updatedUser->foto); // File is renamed with timestamp
    }

    public function test_non_admin_user_cannot_access_user_management_routes()
    {
        $user = User::factory()->create();
        $user->level = 2; // Non-admin level
        $user->save();

        $this->actingAs($user);

        $response = $this->get(route('user.index'));

        $response->assertStatus(403); // Forbidden
    }

    public function test_admin_user_is_not_shown_in_user_data()
    {
        User::factory()->create(['level' => 1]); // Admin user
        User::factory()->create(['level' => 2]); // Non-admin user

        $response = $this->get(route('user.data'));

        $response->assertStatus(200);

        $data = $response->json('data');
        // Should only show non-admin users
        foreach ($data as $user) {
            $this->assertEquals(2, $user['level']);
        }
    }
}
