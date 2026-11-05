<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportEntryPointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            MustBePrivilege::class,
            MustBeSchoolAdmin::class,
        ]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_every_import_entry_point_opens_its_own_import_page(): void
    {
        $school = School::create([
            'name' => 'Entry Point School',
            'email' => 'entry@example.test',
            'phone' => '0700111222',
            'slug' => 'entry-point-school',
            'status' => 1,
            'toshi_enabled' => 0,
        ]);
        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Entry Admin',
            'email' => 'entry-admin@example.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Entry',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        $dashboard = $this->actingAs($admin)->get('/admin/dashboard');
        $dashboard->assertOk();
        $dashboard->assertSee('data-testid="dashboard-v2-import-students"', false);
        $dashboard->assertSee('/admin/import"', false);
        $dashboard->assertDontSee('/admin/student/import', false);

        $students = $this->actingAs($admin)->get('/admin/students');
        $students->assertOk();
        $this->assertMatchesRegularExpression('#href="[^"]*/admin/import"[^>]*>Import list</a>#', $students->getContent());
        $this->assertDoesNotMatchRegularExpression('#/admin/student/import#', $students->getContent());

        $studentImport = $this->actingAs($admin)->get('/admin/import');
        $studentImport->assertOk();
        $studentImport->assertSee('admin/importUsers', false);
        $studentImport->assertDontSee('admin/importTeachers', false);
        $studentImport->assertDontSee('admin/importParents', false);

        $teachers = $this->actingAs($admin)->get('/admin/teachers');
        $teachers->assertOk();
        $this->assertMatchesRegularExpression('#href="[^"]*/admin/import/teacher"[^>]*>Import list</a>#', $teachers->getContent());
        $teacherImport = $this->actingAs($admin)->get('/admin/import/teacher');
        $teacherImport->assertOk();
        $teacherImport->assertSee('admin/importTeachers', false);
        $teacherImport->assertDontSee('admin/importUsers', false);

        $parents = $this->actingAs($admin)->get('/admin/parents');
        $parents->assertOk();
        $this->assertMatchesRegularExpression('#href="[^"]*/admin/import/parents"[^>]*>Import list</a>#', $parents->getContent());
        $this->assertDoesNotMatchRegularExpression('#href="[^"]*/admin/import"[^>]*>Import list</a>#', $parents->getContent());
        $parentImport = $this->actingAs($admin)->get('/admin/import/parents');
        $parentImport->assertOk();
        $parentImport->assertSee('Import Parents', false);
        $parentImport->assertSee('admin/importParents', false);
        $parentImport->assertDontSee('admin/importUsers', false);
    }
}
