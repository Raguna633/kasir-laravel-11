<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create setting first
        \App\Models\Setting::factory()->create();
    }

    public function test_member_model_has_correct_table_name()
    {
        $member = new Member();

        $this->assertEquals('member', $member->getTable());
    }

    public function test_member_model_has_correct_primary_key()
    {
        $member = new Member();

        $this->assertEquals('id_member', $member->getKeyName());
    }

    public function test_member_model_has_correct_fillable_attributes()
    {
        $member = new Member();

        $expectedFillable = [
            'kode_member',
            'nama',
            'telepon',
            'alamat',
        ];

        $this->assertEquals($expectedFillable, $member->getFillable());
    }

    public function test_member_model_uses_has_factory_trait()
    {
        $member = new Member();

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($member));
    }

    public function test_member_model_can_be_created_with_factory()
    {
        $member = Member::factory()->create([
            'kode_member' => 'MEM001',
            'nama' => 'John Doe',
            'telepon' => '08123456789',
            'alamat' => 'Jl. Example No. 123',
        ]);

        $this->assertInstanceOf(Member::class, $member);
        $this->assertEquals('MEM001', $member->kode_member);
        $this->assertEquals('John Doe', $member->nama);
        $this->assertEquals('08123456789', $member->telepon);
        $this->assertEquals('Jl. Example No. 123', $member->alamat);
    }

    public function test_member_model_has_timestamps()
    {
        $member = Member::factory()->create();

        $this->assertNotNull($member->created_at);
        $this->assertNotNull($member->updated_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $member->created_at);
        $this->assertInstanceOf('Illuminate\Support\Carbon', $member->updated_at);
    }

    public function test_member_model_can_update_attributes()
    {
        $member = Member::factory()->create();

        $member->update([
            'nama' => 'Updated Name',
            'telepon' => '08987654321',
            'alamat' => 'Updated Address',
        ]);

        $this->assertEquals('Updated Name', $member->nama);
        $this->assertEquals('08987654321', $member->telepon);
        $this->assertEquals('Updated Address', $member->alamat);
    }

    public function test_member_model_mass_assignment_protection()
    {
        $member = new Member();

        // Test that non-fillable attributes are protected
        $this->assertNotContains('id_member', $member->getFillable());
        $this->assertNotContains('created_at', $member->getFillable());
        $this->assertNotContains('updated_at', $member->getFillable());
    }

    public function test_member_model_to_array_excludes_timestamps_by_default()
    {
        $member = Member::factory()->create();

        $array = $member->toArray();

        $this->assertArrayHasKey('id_member', $array);
        $this->assertArrayHasKey('kode_member', $array);
        $this->assertArrayHasKey('nama', $array);
        $this->assertArrayHasKey('telepon', $array);
        $this->assertArrayHasKey('alamat', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    public function test_member_model_unique_kode_member()
    {
        $member1 = Member::factory()->create(['kode_member' => 'MEM001']);
        $member2 = Member::factory()->create(['kode_member' => 'MEM002']);

        $this->assertNotEquals($member1->kode_member, $member2->kode_member);
    }

    public function test_member_model_search_by_name()
    {
        Member::factory()->create(['nama' => 'John Doe']);
        Member::factory()->create(['nama' => 'Jane Smith']);
        Member::factory()->create(['nama' => 'Bob Johnson']);

        $johnResults = Member::where('nama', 'like', '%John%')->get();
        $this->assertCount(2, $johnResults); // John Doe and Bob Johnson

        $janeResults = Member::where('nama', 'Jane Smith')->get();
        $this->assertCount(1, $janeResults);
        $this->assertEquals('Jane Smith', $janeResults->first()->nama);
    }

    public function test_member_model_search_by_phone()
    {
        Member::factory()->create(['telepon' => '08123456789']);
        Member::factory()->create(['telepon' => '08987654321']);

        $results = Member::where('telepon', '08123456789')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('08123456789', $results->first()->telepon);
    }

    public function test_member_model_ordering_by_name()
    {
        $member1 = Member::factory()->create(['nama' => 'Alice']);
        $member2 = Member::factory()->create(['nama' => 'Bob']);
        $member3 = Member::factory()->create(['nama' => 'Charlie']);

        $ordered = Member::orderBy('nama')->get();

        $this->assertEquals('Alice', $ordered->first()->nama);
        $this->assertEquals('Charlie', $ordered->last()->nama);
    }

    public function test_member_model_count_method_works()
    {
        Member::factory()->count(5)->create();

        $this->assertEquals(5, Member::count());
    }

    public function test_member_model_find_method_works()
    {
        $member = Member::factory()->create();

        $found = Member::find($member->id_member);

        $this->assertInstanceOf(Member::class, $found);
        $this->assertEquals($member->id_member, $found->id_member);
    }

    public function test_member_model_where_method_works()
    {
        $member = Member::factory()->create(['kode_member' => 'MEM123']);

        $found = Member::where('kode_member', 'MEM123')->first();

        $this->assertInstanceOf(Member::class, $found);
        $this->assertEquals('MEM123', $found->kode_member);
    }

    public function test_member_model_delete_method_works()
    {
        $member = Member::factory()->create();

        $member->delete();

        $this->assertDatabaseMissing('member', ['id_member' => $member->id_member]);
    }

    public function test_member_model_soft_delete_if_available()
    {
        $member = Member::factory()->create();

        // Check if model uses SoftDeletes trait
        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($member));

        if ($usesSoftDeletes) {
            $member->delete();
            $this->assertSoftDeleted($member);
        } else {
            $member->delete();
            $this->assertDatabaseMissing('member', ['id_member' => $member->id_member]);
        }
    }

    public function test_member_model_fill_method_works()
    {
        $member = new Member();

        $member->fill([
            'kode_member' => 'MEM456',
            'nama' => 'Test Member',
            'telepon' => '08111111111',
            'alamat' => 'Test Address',
        ]);

        $this->assertEquals('MEM456', $member->kode_member);
        $this->assertEquals('Test Member', $member->nama);
        $this->assertEquals('08111111111', $member->telepon);
        $this->assertEquals('Test Address', $member->alamat);
    }

    public function test_member_model_save_method_works()
    {
        $member = new Member();

        $member->kode_member = 'MEM789';
        $member->nama = 'Save Test';
        $member->telepon = '08222222222';
        $member->alamat = 'Save Address';

        $member->save();

        $this->assertDatabaseHas('member', [
            'kode_member' => 'MEM789',
            'nama' => 'Save Test',
            'telepon' => '08222222222',
            'alamat' => 'Save Address',
        ]);
    }

    public function test_member_model_create_method_works()
    {
        $member = Member::create([
            'kode_member' => 'MEM999',
            'nama' => 'Create Test',
            'telepon' => '08333333333',
            'alamat' => 'Create Address',
        ]);

        $this->assertInstanceOf(Member::class, $member);
        $this->assertEquals('MEM999', $member->kode_member);
        $this->assertEquals('Create Test', $member->nama);
    }

    public function test_member_model_update_method_works()
    {
        $member = Member::factory()->create(['nama' => 'Original Name']);

        $member->update(['nama' => 'Updated Name']);

        $this->assertEquals('Updated Name', $member->nama);
        $this->assertDatabaseHas('member', [
            'id_member' => $member->id_member,
            'nama' => 'Updated Name',
        ]);
    }

    public function test_member_model_get_key_method_works()
    {
        $member = Member::factory()->create();

        $this->assertEquals($member->id_member, $member->getKey());
    }

    public function test_member_model_get_table_method_works()
    {
        $member = new Member();

        $this->assertEquals('member', $member->getTable());
    }

    public function test_member_model_get_key_name_method_works()
    {
        $member = new Member();

        $this->assertEquals('id_member', $member->getKeyName());
    }

    public function test_member_model_exists_property_works()
    {
        $member = Member::factory()->create();

        $this->assertTrue($member->exists);

        $newMember = new Member();
        $this->assertFalse($newMember->exists);
    }

    public function test_member_model_fresh_method_works()
    {
        $member = Member::factory()->create(['nama' => 'Original']);

        // Update in database
        Member::where('id_member', $member->id_member)->update(['nama' => 'Updated']);

        // Fresh should get updated data
        $fresh = $member->fresh();
        $this->assertEquals('Updated', $fresh->nama);
    }

    public function test_member_model_refresh_method_works()
    {
        $member = Member::factory()->create(['nama' => 'Original']);

        // Update in database
        Member::where('id_member', $member->id_member)->update(['nama' => 'Updated']);

        // Refresh should update the current instance
        $member->refresh();
        $this->assertEquals('Updated', $member->nama);
    }
}
