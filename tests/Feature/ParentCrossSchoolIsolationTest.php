<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentCrossSchoolIsolationTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolA;
    private int $schoolB;
    private User $adminA;
    private User $adminB;
    private string $parentName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        // Seed usergroups
        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // School A
        $this->schoolA = DB::table('schools')->insertGetId([
            'name'                 => 'School A',
            'slug'                 => 'school-a',
            'email'                => 'a@test.sch.ug',
            'phone'                => '+256700000001',
            'status'               => 1,
            'registration_country' => 'Uganda',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // School B
        $this->schoolB = DB::table('schools')->insertGetId([
            'name'                 => 'School B',
            'slug'                 => 'school-b',
            'email'                => 'b@test.sch.ug',
            'phone'                => '+256700000002',
            'status'               => 1,
            'registration_country' => 'Uganda',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Admin for School A (usergroup_id=3)
        $this->adminA = User::factory()->create([
            'usergroup_id' => 3,
            'school_id'    => $this->schoolA,
            'name'         => 'Admin A',
            'email'        => 'admin.a@test.sch.ug',
            'password'     => bcrypt('password'),
            'status'       => 'active',
            'email_verified' => 1,
        ]);

        // Admin for School B (usergroup_id=3)
        $this->adminB = User::factory()->create([
            'usergroup_id' => 3,
            'school_id'    => $this->schoolB,
            'name'         => 'Admin B',
            'email'        => 'admin.b@test.sch.ug',
            'password'     => bcrypt('password'),
            'status'       => 'active',
            'email_verified' => 1,
        ]);

        // Seed academic years and standards to satisfy MustBePrivilege middleware
        foreach ([$this->schoolA, $this->schoolB] as $sid) {
            DB::table('academic_years')->insert([
                'school_id'   => $sid,
                'name'        => '2026',
                'description' => 'Current Academic Year',
                'status'      => 1,
                'start_date'  => '2026-01-01',
                'end_date'    => '2026-12-31',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('standards')->insert([
                'school_id'  => $sid,
                'name'       => 'Primary 1',
                'order'      => 1,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Parent in School A only (usergroup_id=7)
        $this->parentName = 'parent-from-school-a';
        User::factory()->create([
            'usergroup_id' => 7,
            'school_id'    => $this->schoolA,
            'name'         => $this->parentName,
            'email'        => 'parent.a@test.sch.ug',
            'password'     => bcrypt('password'),
            'status'       => 'active',
            'email_verified' => 1,
        ]);
    }

    /** @test */
    public function school_b_admin_cannot_view_school_a_parent_show(): void
    {
        $this->actingAs($this->adminB);

        $this->get('/admin/parent/show/' . $this->parentName)
            ->assertNotFound();
    }

    /** @test */
    public function school_b_admin_cannot_view_school_a_parent_edit(): void
    {
        $this->actingAs($this->adminB);

        $this->get('/admin/parent/edit/' . $this->parentName)
            ->assertNotFound();
    }

    /** @test */
    public function school_b_admin_cannot_update_school_a_parent(): void
    {
        $this->actingAs($this->adminB);

        $this->post('/admin/parent/edit/' . $this->parentName, [
            '_token'           => csrf_token(),
            'firstname'        => 'Hacked',
            'lastname'         => 'Name',
            'alternate_no'     => '7123456789',
            'profession'       => 'business',
            'relation'         => 'father',
            'sub_occupation'   => 'Manager',
            'designation'      => 'Manager',
            'organization_name'=> 'TestCorp',
            'annual_income'    => '5000000',
        ])->assertNotFound();
    }

    /** @test */
    public function school_b_admin_cannot_delete_school_a_parent(): void
    {
        $this->actingAs($this->adminB);

        $this->delete('/admin/parent/delete/' . $this->parentName)
            ->assertNotFound();
    }

    /** @test */
    public function school_a_admin_can_view_own_parents(): void
    {
        $this->actingAs($this->adminA);

        $this->get('/admin/parent/show/' . $this->parentName)
            ->assertSuccessful();
    }
}
