<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The KLS student number must be visible where staff look for it:
 * the student list and the student overview page.
 */
class StudentKlsVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBePrivilege::class,
            MustBeSchoolAdmin::class,
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'KLS Visibility School',
            'slug' => 'kls-visibility-' . uniqid(),
            'email' => 'kls-visibility@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'KLS year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'email' => 'kls-admin@test.sch.ug',
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary Three',
            'status' => 1,
        ]);
        $stream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'class_teacher_id' => $this->admin->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => 'A',
            'status' => 1,
        ]);

        $this->student = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'Kls Visibility Pupil',
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $this->student->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => 'Kls Visibility',
            'lastname' => 'Pupil',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $stream->id,
            'klassapp_student_id' => 'KLS7770042',
        ]);
    }

    public function test_student_list_shows_the_kls_number_column(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/students');

        $response->assertOk();
        $response->assertSee('KLS number');
        $response->assertSee('KLS7770042');
    }

    public function test_student_overview_shows_the_kls_number(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/student/show/' . $this->student->name);

        $response->assertOk();
        $response->assertSee('KLS7770042');
    }
}
