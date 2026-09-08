<?php

namespace Tests\Feature;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Resources\ClassTeacher as ClassTeacherResource;
use App\Http\Resources\Teacher as TeacherResource;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MinorFindingsDisplayFixesTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Minor Findings School',
            'email' => 'minor.findings@t.sch.ug',
            'phone' => '0700000457',
            'slug' => 'minor-findings-'.uniqid(),
            'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
    }

    public function test_ugandan_tier_standard_section_has_no_leading_dash(): void
    {
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary Seven',
            'status' => 1,
        ]);

        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $this->assertSame('Primary Seven', $link->StandardSection);
        $this->assertStringNotContainsString(' - ', $link->StandardSection);
    }

    public function test_numeric_standard_still_prefixes_roman_section_label(): void
    {
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => '7',
            'order' => 7,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'A',
            'status' => 1,
        ]);

        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $this->assertSame('VII - A', $link->StandardSection);
    }

    public function test_teacher_resource_null_dob_does_not_become_epoch(): void
    {
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'null.dob.teacher',
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'Null',
            'lastname' => 'Dob',
            'date_of_birth' => null,
            'status' => 'active',
        ]);

        $payload = (new TeacherResource($teacher->fresh(['userprofile'])))
            ->toArray(Request::create('/'));

        $this->assertNull($payload['date_of_birth']);
        $this->assertNotSame('01 Jan 1970', $payload['date_of_birth']);
    }

    public function test_class_teacher_resource_falls_back_to_id_when_username_null(): void
    {
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => null,
            'email' => 'no.username@t.sch.ug',
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'No',
            'lastname' => 'Username',
            'status' => 'active',
        ]);

        // Observer may mint a username from the profile; force the empty-name case
        // that produces /admin/teacher/show/null in the class teachers UI.
        DB::table('users')->where('id', $teacher->id)->update(['name' => null]);
        $teacher->refresh();
        $this->assertTrue(blank($teacher->name));

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => 1,
        ]);

        $stdLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Math',
            'code' => 'MTH',
            'type' => 'core',
            'status' => 1,
        ]);

        $link = Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $stdLink->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'status' => 1,
        ]);

        $payload = (new ClassTeacherResource(
            $link->load(['teacher.userprofile', 'subject'])
        ))->toArray(Request::create('/'));

        $this->assertSame((string) $teacher->id, $payload['teacher_name']);
        $this->assertNotSame('null', $payload['teacher_name']);
    }

    public function test_teacher_show_resolves_by_numeric_id_when_username_missing(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        $admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
        ]);

        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => null,
            'email' => 'id.only.teacher@t.sch.ug',
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'Id',
            'lastname' => 'Only',
            'status' => 'active',
        ]);

        DB::table('users')->where('id', $teacher->id)->update(['name' => null]);
        $teacher->refresh();

        $response = $this->actingAs($admin)
            ->get('/admin/teacher/show/'.$teacher->id);

        $response->assertOk();
        $response->assertViewHas('user', function ($user) use ($teacher) {
            return $user !== null && (int) $user->id === (int) $teacher->id;
        });
    }
}
