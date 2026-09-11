<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cross-tenant + same-school-without-roster denials for Teacher\StudentDetailsController.
 * Full roster allow/deny matrix lives in TeacherStudentDetailsRosterScopeTest.
 */
class TeacherStudentDetailsMemberGateTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherSchoolA;

    private User $studentSchoolB;

    private User $studentSchoolA;

    /** @var list<string> */
    private array $memberGatedPaths;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $schoolA = School::create([
            'name' => 'Gate School A',
            'slug' => 'gate-a-'.uniqid(),
            'email' => 'gate-a-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $schoolB = School::create([
            'name' => 'Gate School B',
            'slug' => 'gate-b-'.uniqid(),
            'email' => 'gate-b-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->teacherSchoolA = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $schoolA->id,
            'name' => 'teacher-gate-a',
            'email' => 'teacher.gate.a@t.sch.ug',
        ]);

        $this->studentSchoolA = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $schoolA->id,
            'name' => 'student-gate-a',
            'email' => 'student.gate.a@t.sch.ug',
        ]);

        Userprofile::create([
            'user_id' => $this->studentSchoolA->id,
            'school_id' => $schoolA->id,
            'usergroup_id' => 6,
            'firstname' => 'Ada',
            'lastname' => 'SchoolA',
            'status' => 'active',
        ]);

        $this->studentSchoolB = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $schoolB->id,
            'name' => 'student-gate-b',
            'email' => 'student.gate.b@t.sch.ug',
        ]);

        Userprofile::create([
            'user_id' => $this->studentSchoolB->id,
            'school_id' => $schoolB->id,
            'usergroup_id' => 6,
            'firstname' => 'Bea',
            'lastname' => 'SchoolB',
            'status' => 'active',
        ]);

        $this->memberGatedPaths = [
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
    }

    public function test_teacher_from_school_a_is_denied_school_b_student_detail_endpoints(): void
    {
        foreach ($this->memberGatedPaths as $template) {
            $url = str_replace('{name}', $this->studentSchoolB->name, $template);

            $response = $this->actingAs($this->teacherSchoolA)->get($url);

            $this->assertSame(
                403,
                $response->getStatusCode(),
                "Expected 403 for cross-school GET {$url}, got {$response->getStatusCode()}"
            );
        }
    }

    public function test_same_school_teacher_without_roster_relationship_is_denied(): void
    {
        // School membership alone is no longer enough — no CT / Teacherlink → 403.
        $response = $this->actingAs($this->teacherSchoolA)
            ->get('/teacher/document/get/'.$this->studentSchoolA->name);

        $this->assertSame(403, $response->getStatusCode());
    }
}
