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

class TeacherImportEntryPointTest extends TestCase
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
        ]);
    }

    public function test_teacher_import_entry_points_open_the_teacher_import(): void
    {
        $school = School::create([
            'name' => 'Import Staff School',
            'email' => 'import-staff@example.test',
            'phone' => '0700111222',
            'slug' => 'import-staff-school',
            'status' => 1,
        ]);
        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Import Admin',
            'email' => 'import-admin@example.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Import',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        $teachers = $this->actingAs($admin)->get('/admin/teachers');
        $teachers->assertOk();
        $html = $teachers->getContent();
        $this->assertIsString($html);

        $this->assertMatchesRegularExpression(
            '#href="[^"]*/admin/import/teacher"[^>]*>Import list</a>#',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '#href="[^"]*/admin/import/teacher"[^>]*>Import a list</a>#',
            $html,
        );
        $this->assertDoesNotMatchRegularExpression(
            '#href="[^"]*/admin/import"[^>]*>Import list</a>#',
            $html,
        );

        $page = $this->actingAs($admin)->get('/admin/import/teacher');
        $page->assertOk();
        $page->assertSee('Import Teaching Staff', false);
        $page->assertSee('admin/importTeachers', false);
        $page->assertDontSee('admin/importUsers', false);
    }
}
