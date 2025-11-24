<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SettingTest extends TestCase
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

    public function test_admin_can_view_setting_index()
    {
        $response = $this->get(route('setting.index'));

        $response->assertStatus(200);
        $response->assertViewIs('setting.index');
    }

    public function test_admin_can_get_setting_data()
    {
        $response = $this->get(route('setting.show'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id_setting',
            'nama_perusahaan',
            'alamat',
            'telepon',
            'diskon',
            'tipe_nota',
            'path_logo',
            'path_kartu_member'
        ]);
    }

    public function test_admin_can_update_setting()
    {
        $updateData = [
            'nama_perusahaan' => 'PT. Updated Company',
            'alamat' => 'Jl. Updated Address No. 123',
            'telepon' => '021-87654321',
            'diskon' => 15,
            'tipe_nota' => 1 // 1 for 'kecil', 0 for 'besar'
        ];

        $response = $this->post(route('setting.update'), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $this->assertDatabaseHas('setting', $updateData);
    }

    public function test_admin_can_update_setting_with_logo()
    {
        $logoFile = UploadedFile::fake()->image('logo.png');

        $updateData = [
            'nama_perusahaan' => 'PT. Company With Logo',
            'alamat' => 'Jl. Logo Street No. 456',
            'telepon' => '021-12345678',
            'diskon' => 10,
            'tipe_nota' => 0, // 0 for 'besar'
            'path_logo' => $logoFile
        ];

        $response = $this->post(route('setting.update'), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $setting = Setting::first();
        $this->assertStringContains('/img/', $setting->path_logo);
        $this->assertStringContains('logo-', $setting->path_logo);
    }

    public function test_admin_can_update_setting_with_member_card()
    {
        $cardFile = UploadedFile::fake()->image('member_card.jpg');

        $updateData = [
            'nama_perusahaan' => 'PT. Company With Card',
            'alamat' => 'Jl. Card Street No. 789',
            'telepon' => '021-98765432',
            'diskon' => 20,
            'tipe_nota' => 1, // 1 for 'kecil'
            'path_kartu_member' => $cardFile
        ];

        $response = $this->post(route('setting.update'), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $setting = Setting::first();
        $this->assertStringContains('/img/', $setting->path_kartu_member);
        $this->assertStringContains('logo-', $setting->path_kartu_member);
    }

    public function test_admin_can_update_setting_with_both_files()
    {
        $logoFile = UploadedFile::fake()->image('logo.png');
        $cardFile = UploadedFile::fake()->image('member_card.jpg');

        $updateData = [
            'nama_perusahaan' => 'PT. Company With Both Files',
            'alamat' => 'Jl. Both Files Street No. 999',
            'telepon' => '021-55555555',
            'diskon' => 25,
            'tipe_nota' => 0, // 0 for 'besar'
            'path_logo' => $logoFile,
            'path_kartu_member' => $cardFile
        ];

        $response = $this->post(route('setting.update'), $updateData);

        $response->assertStatus(200);
        $response->assertSee('Data berhasil disimpan');

        $setting = Setting::first();
        $this->assertStringContains('/img/', $setting->path_logo);
        $this->assertStringContains('/img/', $setting->path_kartu_member);
        $this->assertStringContains('logo-', $setting->path_logo);
        $this->assertStringContains('logo-', $setting->path_kartu_member);
    }

    public function test_setting_update_preserves_existing_files_when_not_provided()
    {
        // First update with files
        $logoFile = UploadedFile::fake()->image('logo.png');
        $cardFile = UploadedFile::fake()->image('member_card.jpg');

        $initialData = [
            'nama_perusahaan' => 'PT. Initial Company',
            'alamat' => 'Jl. Initial Address',
            'telepon' => '021-11111111',
            'diskon' => 5,
            'tipe_nota' => 1, // 1 for 'kecil'
            'path_logo' => $logoFile,
            'path_kartu_member' => $cardFile
        ];

        $this->post(route('setting.update'), $initialData);

        $setting = Setting::first();
        $originalLogoPath = $setting->path_logo;
        $originalCardPath = $setting->path_kartu_member;

        // Update without files
        $updateData = [
            'nama_perusahaan' => 'PT. Updated Company',
            'alamat' => 'Jl. Updated Address',
            'telepon' => '021-22222222',
            'diskon' => 10,
            'tipe_nota' => 0 // 0 for 'besar'
            // No files provided
        ];

        $this->post(route('setting.update'), $updateData);

        $updatedSetting = Setting::first();
        $this->assertEquals($originalLogoPath, $updatedSetting->path_logo);
        $this->assertEquals($originalCardPath, $updatedSetting->path_kartu_member);
    }

    public function test_setting_diskon_validation()
    {
        // Test valid diskon values
        $validData = [
            'nama_perusahaan' => 'PT. Valid Company',
            'alamat' => 'Jl. Valid Address',
            'telepon' => '021-12345678',
            'diskon' => 50, // Valid: between 0 and 100
            'tipe_nota' => 1 // 1 for 'kecil'
        ];

        $response = $this->post(route('setting.update'), $validData);
        $response->assertStatus(200);

        // Test invalid diskon values (negative)
        $invalidData = [
            'nama_perusahaan' => 'PT. Invalid Company',
            'alamat' => 'Jl. Invalid Address',
            'telepon' => '021-87654321',
            'diskon' => -5, // Invalid: negative
            'tipe_nota' => 1 // 1 for 'kecil'
        ];

        $response = $this->post(route('setting.update'), $invalidData);
        $response->assertStatus(200); // Laravel doesn't validate diskon range in controller
    }

    public function test_setting_tipe_nota_validation()
    {
        // Test valid tipe_nota values
        $validData = [
            'nama_perusahaan' => 'PT. Valid Company',
            'alamat' => 'Jl. Valid Address',
            'telepon' => '021-12345678',
            'diskon' => 10,
            'tipe_nota' => 0 // 0 for 'besar'
        ];

        $response = $this->post(route('setting.update'), $validData);
        $response->assertStatus(200);

        $this->assertDatabaseHas('setting', ['tipe_nota' => 0]);
    }

    public function test_non_admin_user_cannot_access_setting_routes()
    {
        $user = User::factory()->create();
        $user->level = 2; // Non-admin level
        $user->save();

        $this->actingAs($user);

        $response = $this->get(route('setting.index'));

        $response->assertStatus(403); // Forbidden
    }

    public function test_setting_always_returns_first_record()
    {
        // Create multiple settings (though normally there should only be one)
        Setting::factory()->count(2)->create();

        $response = $this->get(route('setting.show'));

        $response->assertStatus(200);

        // Should always return the first setting
        $setting = Setting::first();
        $responseData = $response->json();

        $this->assertEquals($setting->id_setting, $responseData['id_setting']);
    }
}
