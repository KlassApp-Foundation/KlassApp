<?php

namespace Tests\Feature\Toshi;

use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AddParentProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Toshi Parent Prov School',
            'slug' => 'toshi-parent-prov',
            'email' => 'toshi-parent-prov@test.sch.ug',
            'phone' => '+256700000088',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->schoolId,
            'email' => 'toshi-admin@test.sch.ug',
            'password' => bcrypt('admin-secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_add_parent_uses_random_password_and_is_reset(): void
    {
        $result = ToshiActionService::addParent($this->admin, [
            'name' => 'Toshi Prov Parent',
            'phone' => '+256700123456',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('parent_id', $result);

        $parent = User::find($result['parent_id']);

        $this->assertNotNull($parent);
        $this->assertSame(7, (int) $parent->usergroup_id);
        $this->assertFalse(Hash::check('password', $parent->password));
        $this->assertSame(1, (int) $parent->fresh()->is_reset);
    }
}
