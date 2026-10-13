<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Soft-launch 1a: Teachers and Parents list pages must expose one-click create
 * entry points matching Students (Add student + Import list).
 */
class PeopleAddButtonsContractTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            \App\Http\Middleware\MustBePrivilege::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
        ]);

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'People Add School '.Str::random(4),
            'slug' => 'people-add-'.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('academic_years')->insert([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'status' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->schoolId,
            'name' => 'People Admin',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'firstname' => 'People',
            'lastname' => 'Admin',
        ]);
    }

    public function test_teachers_roster_blade_exposes_add_teacher_next_to_import_list(): void
    {
        $blade = file_get_contents(resource_path('views/admin/teacher/index.blade.php'));

        $this->assertStringContainsString('Import list', $blade);
        $this->assertStringContainsString('Add teacher', $blade);
        $this->assertStringContainsString("url('/admin/teacher/add')", $blade);
    }

    public function test_parents_list_vue_exposes_add_parent_to_create_route(): void
    {
        $vue = file_get_contents(resource_path('assets/js/components/parent/List.vue'));

        $this->assertStringContainsString('Add parent', $vue);
        $this->assertStringContainsString('/admin/parent/add', $vue);
        $this->assertStringNotContainsString('Import list', $vue);
    }

    public function test_teachers_page_renders_add_teacher_link_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/teachers');

        $response->assertOk();
        $response->assertSee('Add teacher', false);
        $response->assertSee('/admin/teacher/add', false);
        $response->assertSee('Import list', false);
    }

    public function test_parents_page_mounts_parent_list_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/parents');

        $response->assertOk();
        $response->assertSee('data-people-list="parents"', false);
        $response->assertSee('Add parent', false);
    }

    public function test_teacher_and_parent_add_routes_are_reachable(): void
    {
        $this->actingAs($this->admin)->get('/admin/teacher/add')->assertOk();
        $this->actingAs($this->admin)->get('/admin/parent/add')->assertOk();
    }
}
