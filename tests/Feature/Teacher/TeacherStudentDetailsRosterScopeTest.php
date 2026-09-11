<?php

namespace Tests\Feature\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\RosterScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Teacher student-profile surfaces: hard deny unless student is on the teacher's
 * current-year roster (CT of stream/section or subject Teacherlink) — same
 * boundary as RosterScopeService::actorCanAccessStudent().
 */
class TeacherStudentDetailsRosterScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StandardLink $stream;

    private User $classTeacher;

    private User $subjectTeacher;

    private User $unrelatedTeacher;

    private User $rosterStudent;

    private User $outOfRosterStudent;

    /** @var list<string> */
    private array $profilePaths;

    /** @var list<string> */
    private array $safeAllowPaths;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        Cache::flush();

        $this->school = School::create([
            'name' => 'Roster Profile School',
            'slug' => 'roster-profile-'.uniqid(),
            'email' => 'roster-profile-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->classTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'ct-roster-profile',
            'email' => 'ct.roster.profile@t.sch.ug',
        ]);

        $this->subjectTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'subject-roster-profile',
            'email' => 'subject.roster.profile@t.sch.ug',
        ]);

        $this->unrelatedTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'unrelated-roster-profile',
            'email' => 'unrelated.roster.profile@t.sch.ug',
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'class_teacher_id' => $this->classTeacher->id,
            'status' => 1,
        ]);

        $this->stream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'class_teacher_id' => null,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => null,
            'status' => 1,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'code' => 'MTC-RP',
            'type' => 'core',
            'status' => 1,
        ]);

        Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->stream->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->subjectTeacher->id,
        ]);

        $this->rosterStudent = $this->makeStudent('roster-student', [
            'medication_problems' => 'Asthma',
            'medication_needs' => 'Inhaler',
            'food_allergies' => 'Peanuts',
        ]);

        $otherSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.2',
            'class_teacher_id' => null,
            'status' => 1,
        ]);

        $otherStream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'class_teacher_id' => null,
            'standard_id' => $standard->id,
            'section_id' => $otherSection->id,
            'stream' => null,
            'status' => 1,
        ]);

        $this->outOfRosterStudent = $this->makeStudent('out-of-roster-student', [], $otherStream);

        $this->profilePaths = [
            '/teacher/student/show/{name}',
            '/teacher/student/show/details/{name}',
            '/teacher/student/show/relations/{name}',
            '/teacher/student/show/siblings/{name}',
            '/teacher/student/show/discipline/{name}',
            '/teacher/student/show/attendance/{name}',
            '/teacher/student/show/libraryactivity/{name}',
            '/teacher/student/showmark/{name}',
            '/teacher/student/showallmark/{name}',
            '/teacher/student/comparemark/{name}',
            '/teacher/student/show/medicalHistory/{name}',
            '/teacher/document/get/{name}',
        ];

        $this->safeAllowPaths = [
            '/teacher/student/show/{name}',
            '/teacher/student/show/details/{name}',
            '/teacher/student/show/relations/{name}',
            '/teacher/student/show/siblings/{name}',
            '/teacher/student/show/discipline/{name}',
            '/teacher/student/show/attendance/{name}',
            '/teacher/student/show/libraryactivity/{name}',
            '/teacher/student/show/medicalHistory/{name}',
            '/teacher/document/get/{name}',
        ];

        $this->assertSame(
            (int) $this->year->id,
            (int) SiteHelper::getAcademicYear($this->school->id)->id
        );
    }

    public function test_service_matches_roster_visibility_for_ct_subject_and_unrelated(): void
    {
        $service = app(RosterScopeService::class);

        $this->assertTrue($service->actorCanAccessStudent($this->classTeacher, $this->rosterStudent));
        $this->assertTrue($service->actorCanAccessStudent($this->subjectTeacher, $this->rosterStudent));
        $this->assertFalse($service->actorCanAccessStudent($this->unrelatedTeacher, $this->rosterStudent));
        $this->assertFalse($service->actorCanAccessStudent($this->classTeacher, $this->outOfRosterStudent));
        $this->assertFalse($service->actorCanAccessStudent($this->subjectTeacher, $this->outOfRosterStudent));
    }

    public function test_subject_teacher_with_teacherlink_can_read_full_profile_including_medical(): void
    {
        $response = $this->actingAs($this->subjectTeacher)
            ->get('/teacher/student/show/medicalHistory/'.$this->rosterStudent->name);

        $response->assertOk();
        $response->assertJsonFragment([
            'medication_problems' => 'Asthma',
            'medication_needs' => 'Inhaler',
            'food_allergies' => 'Peanuts',
        ]);

        $this->actingAs($this->subjectTeacher)
            ->get('/teacher/document/get/'.$this->rosterStudent->name)
            ->assertOk();

        $this->actingAs($this->subjectTeacher)
            ->get('/teacher/student/show/'.$this->rosterStudent->name)
            ->assertOk();
    }

    public function test_class_teacher_can_access_own_students_profile_endpoints(): void
    {
        foreach ($this->safeAllowPaths as $template) {
            $url = str_replace('{name}', $this->rosterStudent->name, $template);
            $response = $this->actingAs($this->classTeacher)->get($url);
            $this->assertNotSame(
                403,
                $response->getStatusCode(),
                "CT expected non-403 for {$url}, got {$response->getStatusCode()}"
            );
        }

        $this->actingAs($this->classTeacher)
            ->get('/teacher/student/show/medicalHistory/'.$this->rosterStudent->name)
            ->assertOk()
            ->assertJsonFragment(['medication_problems' => 'Asthma']);
    }

    public function test_same_school_teacher_without_roster_gets_403_on_every_profile_endpoint(): void
    {
        foreach ($this->profilePaths as $template) {
            $url = str_replace('{name}', $this->rosterStudent->name, $template);
            $response = $this->actingAs($this->unrelatedTeacher)->get($url);
            $this->assertSame(
                403,
                $response->getStatusCode(),
                "Expected 403 for out-of-roster GET {$url}, got {$response->getStatusCode()}"
            );
        }

        foreach ($this->profilePaths as $template) {
            $url = str_replace('{name}', $this->outOfRosterStudent->name, $template);
            $response = $this->actingAs($this->classTeacher)->get($url);
            $this->assertSame(
                403,
                $response->getStatusCode(),
                "Expected 403 for peer-class GET {$url}, got {$response->getStatusCode()}"
            );
        }
    }

    public function test_cross_school_student_still_denied(): void
    {
        $otherSchool = School::create([
            'name' => 'Other Roster School',
            'slug' => 'other-roster-'.uniqid(),
            'email' => 'other-roster-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $foreign = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $otherSchool->id,
            'name' => 'foreign-roster-student',
            'email' => 'foreign.roster@t.sch.ug',
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $foreign->id,
            'school_id' => $otherSchool->id,
            'usergroup_id' => 6,
            'firstname' => 'Foreign',
            'lastname' => 'Student',
            'status' => 'active',
        ]);

        foreach ($this->profilePaths as $template) {
            $url = str_replace('{name}', $foreign->name, $template);
            $response = $this->actingAs($this->classTeacher)->get($url);
            $this->assertSame(403, $response->getStatusCode(), "Expected 403 for {$url}");
        }
    }

    public function test_ct_access_survives_cross_school_duplicate_student_name(): void
    {
        // Another school already has the same users.name — unscoped first() is ambiguous.
        $otherSchool = School::create([
            'name' => 'Collision School',
            'slug' => 'collision-'.uniqid(),
            'email' => 'collision-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $sharedName = $this->rosterStudent->name;

        User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $otherSchool->id,
            'name' => $sharedName,
            'email' => 'twin.collision@t.sch.ug',
            'status' => 'active',
        ]);

        $this->assertGreaterThan(
            1,
            User::where('name', $sharedName)->count(),
            'Fixture must collide on users.name across schools'
        );

        $this->actingAs($this->classTeacher)
            ->get('/teacher/student/show/medicalHistory/'.$sharedName)
            ->assertOk()
            ->assertJsonFragment(['medication_problems' => 'Asthma']);

        $this->actingAs($this->classTeacher)
            ->get('/teacher/document/get/'.$sharedName)
            ->assertOk();
    }

    public function test_teacher_class_browse_students_vue_strips_deep_profile_links(): void
    {
        $source = file_get_contents(resource_path('assets/js/components/academic/class/students.vue'));

        $this->assertStringContainsString("mode === 'admin'", $source);
        $this->assertStringContainsString('<span v-else>{{ student.fullname }}</span>', $source);
        $this->assertMatchesRegularExpression(
            "/v-if=\"mode === 'admin'\"[\s\S]*student\/show\//",
            $source
        );
    }

    /**
     * @param  array<string, mixed>  $medical
     */
    private function makeStudent(string $name, array $medical = [], ?StandardLink $stream = null): User
    {
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => $name,
            'email' => $name.'@t.sch.ug',
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $student->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => 'Test',
            'lastname' => $name,
            'status' => 'active',
        ]);

        StudentAcademic::create(array_merge([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $student->id,
            'standardLink_id' => ($stream ?? $this->stream)->id,
            'academic_status' => 'pass',
        ], $medical));

        return $student;
    }
}
