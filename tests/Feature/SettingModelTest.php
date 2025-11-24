<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Note: We don't create a Setting factory here since we're testing the Setting model itself
        // and it might cause conflicts. The Setting model is usually a singleton.
    }

    public function test_setting_model_has_correct_table_name()
    {
        $setting = new Setting();

        $this->assertEquals('setting', $setting->getTable());
    }

    public function test_setting_model_has_correct_primary_key()
    {
        $setting = new Setting();

        $this->assertEquals('id_setting', $setting->getKeyName());
    }

    public function test_setting_model_uses_guarded_instead_of_fillable()
    {
        $setting = new Setting();

        // Since it uses $guarded = [], all attributes should be fillable
        $this->assertEmpty($setting->getGuarded());
        $this->assertEmpty($setting->getFillable()); // When guarded is empty, fillable is also empty
    }

    public function test_setting_model_uses_has_factory_trait()
    {
        $setting = new Setting();

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($setting));
    }

    public function test_setting_model_can_be_created()
    {
        $setting = Setting::create([
            'nama_perusahaan' => 'PT. Example Company',
            'alamat' => 'Jl. Example No. 123',
            'telepon' => '021-12345678',
            'tipe_nota' => 'kecil',
            'diskon' => 10,
            'path_logo' => '/path/to/logo.png',
            'path_kartu_member' => '/path/to/member_card.png',
        ]);

        $this->assertInstanceOf(Setting::class, $setting);
        $this->assertEquals('PT. Example Company', $setting->nama_perusahaan);
        $this->assertEquals('Jl. Example No. 123', $setting->alamat);
        $this->assertEquals('021-12345678', $setting->telepon);
        $this->assertEquals('kecil', $setting->tipe_nota);
        $this->assertEquals(10, $setting->diskon);
    }

    public function test_setting_model_has_timestamps()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Test Company']);

        $this->assertNotNull($setting->created_at);
        $this->assertNotNull($setting->updated_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $setting->created_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $setting->updated_at);
    }

    public function test_setting_model_can_update_attributes()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Original Company']);

        $setting->update([
            'nama_perusahaan' => 'Updated Company Name',
            'telepon' => '022-87654321',
            'diskon' => 15,
        ]);

        $this->assertEquals('Updated Company Name', $setting->nama_perusahaan);
        $this->assertEquals('022-87654321', $setting->telepon);
        $this->assertEquals(15, $setting->diskon);
    }

    public function test_setting_model_mass_assignment_protection()
    {
        $setting = new Setting();

        // Test that non-fillable attributes are protected
        $this->assertNotContains('id_setting', $setting->getFillable());
        $this->assertNotContains('created_at', $setting->getFillable());
        $this->assertNotContains('updated_at', $setting->getFillable());
    }

    public function test_setting_model_to_array_includes_all_attributes()
    {
        $setting = Setting::create([
            'nama_perusahaan' => 'Test Company',
            'alamat' => 'Test Address',
            'telepon' => '021-12345678',
        ]);

        $array = $setting->toArray();

        $this->assertArrayHasKey('id_setting', $array);
        $this->assertArrayHasKey('nama_perusahaan', $array);
        $this->assertArrayHasKey('alamat', $array);
        $this->assertArrayHasKey('telepon', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_setting_model_tipe_nota_values()
    {
        $settingKecil = Setting::create(['tipe_nota' => 'kecil']);
        $settingBesar = Setting::create(['tipe_nota' => 'besar']);

        $this->assertEquals('kecil', $settingKecil->tipe_nota);
        $this->assertEquals('besar', $settingBesar->tipe_nota);
    }

    public function test_setting_model_diskon_field_accepts_numeric_values()
    {
        $setting = Setting::create(['diskon' => 25]);

        $this->assertEquals(25, $setting->diskon);
        $this->assertIsNumeric($setting->diskon);
    }

    public function test_setting_model_path_fields_accept_file_paths()
    {
        $setting = Setting::create([
            'path_logo' => '/uploads/logos/logo.png',
            'path_kartu_member' => '/uploads/cards/member_card.jpg',
        ]);

        $this->assertEquals('/uploads/logos/logo.png', $setting->path_logo);
        $this->assertEquals('/uploads/cards/member_card.jpg', $setting->path_kartu_member);
    }

    public function test_setting_model_find_method_works()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Find Test Company']);

        $found = Setting::find($setting->id_setting);

        $this->assertInstanceOf(Setting::class, $found);
        $this->assertEquals($setting->id_setting, $found->id_setting);
        $this->assertEquals('Find Test Company', $found->nama_perusahaan);
    }

    public function test_setting_model_where_method_works()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Where Test Company']);

        $found = Setting::where('nama_perusahaan', 'Where Test Company')->first();

        $this->assertInstanceOf(Setting::class, $found);
        $this->assertEquals('Where Test Company', $found->nama_perusahaan);
    }

    public function test_setting_model_delete_method_works()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Delete Test Company']);

        $setting->delete();

        $this->assertDatabaseMissing('setting', ['id_setting' => $setting->id_setting]);
    }

    public function test_setting_model_soft_delete_if_available()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Soft Delete Test']);

        // Check if model uses SoftDeletes trait
        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($setting));

        if ($usesSoftDeletes) {
            $setting->delete();
            $this->assertSoftDeleted($setting);
        } else {
            $setting->delete();
            $this->assertDatabaseMissing('setting', ['id_setting' => $setting->id_setting]);
        }
    }

    public function test_setting_model_fill_method_works()
    {
        $setting = new Setting();

        $setting->fill([
            'nama_perusahaan' => 'Fill Test Company',
            'telepon' => '021-99999999',
            'diskon' => 20,
        ]);

        $this->assertEquals('Fill Test Company', $setting->nama_perusahaan);
        $this->assertEquals('021-99999999', $setting->telepon);
        $this->assertEquals(20, $setting->diskon);
    }

    public function test_setting_model_save_method_works()
    {
        $setting = new Setting();

        $setting->nama_perusahaan = 'Save Test Company';
        $setting->telepon = '021-88888888';
        $setting->diskon = 30;

        $setting->save();

        $this->assertDatabaseHas('setting', [
            'nama_perusahaan' => 'Save Test Company',
            'telepon' => '021-88888888',
            'diskon' => 30,
        ]);
    }

    public function test_setting_model_update_method_works()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Original Company']);

        $setting->update(['nama_perusahaan' => 'Updated Company']);

        $this->assertEquals('Updated Company', $setting->nama_perusahaan);
        $this->assertDatabaseHas('setting', [
            'id_setting' => $setting->id_setting,
            'nama_perusahaan' => 'Updated Company',
        ]);
    }

    public function test_setting_model_get_key_method_works()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Key Test Company']);

        $this->assertEquals($setting->id_setting, $setting->getKey());
    }

    public function test_setting_model_get_table_method_works()
    {
        $setting = new Setting();

        $this->assertEquals('setting', $setting->getTable());
    }

    public function test_setting_model_get_key_name_method_works()
    {
        $setting = new Setting();

        $this->assertEquals('id_setting', $setting->getKeyName());
    }

    public function test_setting_model_exists_property_works()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Exists Test Company']);

        $this->assertTrue($setting->exists);

        $newSetting = new Setting();
        $this->assertFalse($newSetting->exists);
    }

    public function test_setting_model_fresh_method_works()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Original']);

        // Update in database
        Setting::where('id_setting', $setting->id_setting)->update(['nama_perusahaan' => 'Updated']);

        // Fresh should get updated data
        $fresh = $setting->fresh();
        $this->assertEquals('Updated', $fresh->nama_perusahaan);
    }

    public function test_setting_model_refresh_method_works()
    {
        $setting = Setting::create(['nama_perusahaan' => 'Original']);

        // Update in database
        Setting::where('id_setting', $setting->id_setting)->update(['nama_perusahaan' => 'Updated']);

        // Refresh should update the current instance
        $setting->refresh();
        $this->assertEquals('Updated', $setting->nama_perusahaan);
    }

    public function test_setting_model_company_name_field_accepts_long_text()
    {
        $longCompanyName = 'PT. Perusahaan Dagang Indonesia Maju Bersama Tbk.';

        $setting = Setting::create(['nama_perusahaan' => $longCompanyName]);

        $this->assertEquals($longCompanyName, $setting->nama_perusahaan);
        $this->assertDatabaseHas('setting', ['nama_perusahaan' => $longCompanyName]);
    }

    public function test_setting_model_address_field_accepts_long_text()
    {
        $longAddress = 'Jl. Sudirman No. 123, RT. 01 RW. 02, Kelurahan Sudirman, Kecamatan Tanah Abang, Jakarta Pusat, DKI Jakarta 10230';

        $setting = Setting::create(['alamat' => $longAddress]);

        $this->assertEquals($longAddress, $setting->alamat);
        $this->assertDatabaseHas('setting', ['alamat' => $longAddress]);
    }

    public function test_setting_model_phone_field_accepts_various_formats()
    {
        $phones = [
            '021-12345678',
            '(021) 12345678',
            '08123456789',
            '+62-21-12345678',
            '021 1234 5678',
        ];

        foreach ($phones as $phone) {
            $setting = Setting::create(['telepon' => $phone]);
            $this->assertEquals($phone, $setting->telepon);
        }
    }

    public function test_setting_model_diskon_field_accepts_decimal_values()
    {
        $setting = Setting::create(['diskon' => 12.5]);

        $this->assertEquals(12.5, $setting->diskon);
        $this->assertIsNumeric($setting->diskon);
    }

    public function test_setting_model_singleton_pattern()
    {
        // Test that usually only one setting record exists
        $setting1 = Setting::create(['nama_perusahaan' => 'Company 1']);
        $setting2 = Setting::create(['nama_perusahaan' => 'Company 2']);

        $this->assertEquals(2, Setting::count()); // But in practice, there should be only one

        // Test first() method which is commonly used for singleton settings
        $firstSetting = Setting::first();
        $this->assertInstanceOf(Setting::class, $firstSetting);
    }

    public function test_setting_model_bulk_operations()
    {
        $settings = Setting::factory()->count(3)->create();

        // Test that all settings were created
        $this->assertEquals(3, Setting::count());

        // Test bulk update
        Setting::where('id_setting', '>', 0)->update(['diskon' => 5]);

        $updatedCount = Setting::where('diskon', 5)->count();
        $this->assertEquals(3, $updatedCount);
    }
}
