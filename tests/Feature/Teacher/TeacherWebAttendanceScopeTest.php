<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeTeacher;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherWebAttendanceScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $classTeacher;

    private User $peerTeacher;

    private User $ownStudent;

    private User $otherStudent;

    private AcademicYear $year;

    private StandardLink $ownStream;

    private StandardLink $otherStream;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Attendance Scope School',
            'slug' => 'att-scope-' . uniqid(),
            'email' => 'att-scope-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'email' => 'admin.attscope@t.sch.ug',
        ]);

        $this->classTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'email' => 'ct.attscope@t.sch.ug',
        ]);

        $this->peerTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'email' => 'peer.attscope@t.sch.ug',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => (string) now()->year,
            'description' => 'Current Academic Year',
            'start_date' => now()->subMonths(2)->startOfDay(),
            'end_date' => now()->addMonths(6)->endOfDay(),
            'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$this->school->id);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $ownSection = Section::create(['school_id' => $this->school->id, 'name' => 'P.7', 'status' => 1]);
        $otherSection = Section::create(['school_id' => $this->school->id, 'name' => 'P.6', 'status' => 1]);

        $this->ownStream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $ownSection->id,
            'class_teacher_id' => $this->classTeacher->id,
            'stream' => 'A',
            'status' => 1,
        ]);

        $this->otherStream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $otherSection->id,
            'class_teacher_id' => $this->peerTeacher->id,
            'stream' => 'A',
            'status' => 1,
        ]);

        $this->ownStudent = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'status' => 'active',
            'email' => 'own.student.att@t.sch.ug',
        ]);
        Userprofile::create([
            'user_id' => $this->ownStudent->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => 'Own',
            'lastname' => 'Student',
            'status' => 'active',
        ]);
        // Remove status => 1 from StudentAcademic create - use no status field
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'user_id' => $this->ownStudent->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->ownStream->id,
        ]);

        $this->otherStudent = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'status' => 'active',
            'email' => 'other.student.att@t.sch.ug',
        ]);
        Userprofile::create([
            'user_id' => $this->otherStudent->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => 'Other',
            'lastname' => 'Student',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'user_id' => $this->otherStudent->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->otherStream->id,
        ]);
    }

    public function test_class_teacher_list_only_includes_own_class(): void
    {
        $response = $this->actingAs($this->classTeacher)->getJson('/teacher/attendance/list');

        $response->assertOk();
        $payload = $response->json();

        $linkIds = collect($payload['standardlist'])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains((int) $this->ownStream->id, $linkIds);
        $this->assertNotContains((int) $this->otherStream->id, $linkIds);

        $this->assertArrayHasKey((string) $this->ownStream->id, $payload['studentlist']);
        $this->assertArrayNotHasKey((string) $this->otherStream->id, $payload['studentlist']);
    }

    public function test_teacher_cannot_store_attendance_for_class_they_are_not_ct_of(): void
    {
        $response = $this->actingAs($this->classTeacher)->postJson('/teacher/attendance/add', [
            'standardLink_id' => $this->otherStream->id,
            'date' => now()->format('Y-m-d'),
            'session' => 'forenoon',
            'absentCount' => 0,
            'presentCount' => 1,
            'present_id0' => $this->otherStudent->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('attendances', [
            'standardLink_id' => $this->otherStream->id,
            'user_id' => $this->otherStudent->id,
        ]);
    }

    public function test_class_teacher_can_store_attendance_for_own_class(): void
    {
        $ay = \App\Helpers\SiteHelper::getAcademicYear($this->school->id);
        $attDate = \Carbon\Carbon::parse($ay->start_date)->addDay()->format('Y-m-d');
        if (\Carbon\Carbon::parse($attDate)->gt(\Carbon\Carbon::today())) {
            $attDate = \Carbon\Carbon::today()->format('Y-m-d');
        }

        $response = $this->actingAs($this->classTeacher)->postJson('/teacher/attendance/add', [
            'standardLink_id' => $this->ownStream->id,
            'date' => $attDate,
            'session' => 'forenoon',
            'absentCount' => 0,
            'presentCount' => 1,
            'present_id0' => $this->ownStudent->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('attendances', [
            'school_id' => $this->school->id,
            'standardLink_id' => $this->ownStream->id,
            'user_id' => $this->ownStudent->id,
            'status' => 1,
        ]);
    }

    public function test_admin_attendance_store_remains_unscoped(): void
    {
        $ay = \App\Helpers\SiteHelper::getAcademicYear($this->school->id);
        $attDate = \Carbon\Carbon::parse($ay->start_date)->addDay()->format('Y-m-d');
        if (\Carbon\Carbon::parse($attDate)->gt(\Carbon\Carbon::today())) {
            $attDate = \Carbon\Carbon::today()->format('Y-m-d');
        }

        $response = $this->actingAs($this->admin)->postJson('/admin/attendance/add', [
            'standardLink_id' => $this->otherStream->id,
            'date' => $attDate,
            'session' => 'afternoon',
            'absentCount' => 0,
            'presentCount' => 1,
            'present_id0' => $this->otherStudent->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('attendances', [
            'school_id' => $this->school->id,
            'standardLink_id' => $this->otherStream->id,
            'user_id' => $this->otherStudent->id,
            'status' => 1,
        ]);
    }
}
