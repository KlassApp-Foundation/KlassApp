<?php

namespace Tests\Feature\Toshi;

use App\Models\User;
use App\AiAgents\Tools\ListTeachersTool;
use App\AiAgents\Tools\GetStudentCountTool;
use App\AiAgents\Tools\AddStudentTool;
use App\AiAgents\Tools\FindStudentTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToshiSdkV2AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $schoolAdmin;
    private User $superadmin;
    private int $schoolId;
    private int $usergroupSchoolAdmin;
    private int $usergroupSuperadmin;
    private int $usergroupTeacher;
    private int $usergroupStudent;

    protected function setUp(): void
    {
        parent::setUp();

        // ── Insert usergroups with production IDs ──
        // Production schema uses hardcoded IDs: 1=siteadmin, 2=sitesubadmin,
        // 3=schooladmin, 4=schoolsubadmin, 5=teacher, 6=student
        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $this->usergroupSuperadmin = 1;
        $this->usergroupSchoolAdmin = 3;
        $this->usergroupTeacher = 5;
        $this->usergroupStudent = 6;

        // ── Create school ──
        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Auth Test School', 'slug' => 'auth-test',
            'email' => 'auth@test.sch.ug', 'phone' => '+256700000099',
            'status' => 1, 'registration_country' => 'Uganda',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ── Create users ──
        $this->superadmin = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => $this->usergroupSuperadmin,
            'email' => 'super@test.sch.ug',
        ]);
        $this->schoolAdmin = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => $this->usergroupSchoolAdmin,
            'email' => 'admin@test.sch.ug',
        ]);

        // Seed a student and a teacher so query tools have data
        DB::table('users')->insert([
            'school_id' => $this->schoolId, 'usergroup_id' => $this->usergroupStudent,
            'name' => 'Test Student', 'email' => 'student@test.sch.ug',
            'password' => bcrypt('password'), 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'school_id' => $this->schoolId, 'usergroup_id' => $this->usergroupTeacher,
            'name' => 'Test Teacher', 'email' => 'teacher@test.sch.ug',
            'password' => bcrypt('password'), 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ── Authorization scenario tests ──

    /** @test */
    public function school_admin_can_access_tools()
    {
        $this->actingAs($this->schoolAdmin);
        $tool = app(ListTeachersTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([]));
        $this->assertStringNotContainsString('unauthenticated', $result);
        $this->assertStringNotContainsString('not authorized', $result);
        $this->assertStringNotContainsString('denied', $result);
        $this->assertStringContainsString('Teacher', $result,
            'SchoolAdmin should see teacher data, not an auth error');
    }

    /** @test */
    public function superadmin_not_impersonating_is_denied()
    {
        $this->actingAs($this->superadmin);
        $tool = app(ListTeachersTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([]));

        $this->assertStringContainsString('not authorized', strtolower($result),
            'Superadmin without impersonation must be denied');
    }

    /** @test */
    public function superadmin_impersonating_schooladmin_is_authorized()
    {
        $this->actingAs($this->superadmin);
        $this->superadmin->setImpersonating($this->schoolAdmin->id);

        $tool = app(ListTeachersTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([]));

        $this->assertStringContainsString('Teacher', $result,
            'Impersonating superadmin should see teacher data');
    }

    /** @test */
    public function unauthenticated_user_is_denied()
    {
        $tool = app(GetStudentCountTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([]));

        $this->assertStringContainsString('authentication required', strtolower($result),
            'Unauthenticated user must be denied with appropriate message');
    }

    // ── Role-based denial tests ──

    /** @test */
    public function teacher_is_denied()
    {
        $teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => $this->usergroupTeacher,
            'email' => 'denied-teacher@test.sch.ug',
        ]);
        $this->actingAs($teacher);

        $tool = app(GetStudentCountTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([]));

        $this->assertStringContainsString('not authorized', strtolower($result),
            'Teacher must be denied');
    }

    /** @test */
    public function student_is_denied()
    {
        $student = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => $this->usergroupStudent,
            'email' => 'denied-student@test.sch.ug',
        ]);
        $this->actingAs($student);

        $tool = app(GetStudentCountTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([]));

        $this->assertStringContainsString('not authorized', strtolower($result),
            'Student must be denied');
    }

    // ── Tool coverage tests: different tool types ──

    /** @test */
    public function add_student_tool_authorized_for_school_admin()
    {
        $this->actingAs($this->schoolAdmin);
        $tool = app(AddStudentTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([
            'name' => 'New Student',
            'class' => 'P1',
        ]));

        $this->assertStringNotContainsString('not authorized', strtolower($result),
            'SchoolAdmin should be able to add students');
    }

    /** @test */
    public function find_student_tool_authorized_for_school_admin()
    {
        $this->actingAs($this->schoolAdmin);
        $tool = app(FindStudentTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([
            'query' => 'Test Student',
        ]));

        $this->assertStringNotContainsString('not authorized', strtolower($result),
            'SchoolAdmin should be able to find students');
    }

    /** @test */
    public function add_student_tool_denied_for_teacher()
    {
        $teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => $this->usergroupTeacher,
            'email' => 'write-deny-teacher@test.sch.ug',
        ]);
        $this->actingAs($teacher);

        $tool = app(AddStudentTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([
            'name' => 'Evil Student',
            'class' => 'P1',
        ]));

        $this->assertStringContainsString('not authorized', strtolower($result),
            'Teacher must not be able to add students');
    }

    /** @test */
    public function find_student_tool_denied_for_teacher()
    {
        $teacher = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => $this->usergroupTeacher,
            'email' => 'read-deny-teacher@test.sch.ug',
        ]);
        $this->actingAs($teacher);

        $tool = app(FindStudentTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([
            'query' => 'Test Student',
        ]));

        $this->assertStringContainsString('not authorized', strtolower($result),
            'Teacher must not be able to find students');
    }

    /** @test */
    public function superadmin_impersonating_can_write_tools()
    {
        $this->actingAs($this->superadmin);
        $this->superadmin->setImpersonating($this->schoolAdmin->id);

        $tool = app(AddStudentTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([
            'name' => 'Impersonated Add',
            'class' => 'P1',
        ]));

        $this->assertStringNotContainsString('not authorized', strtolower($result),
            'Impersonating superadmin should be able to write via tools');
    }
}
