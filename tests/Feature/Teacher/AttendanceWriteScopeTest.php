<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Durable regression cover for the CONTROLLER write path, not the helper.
 *
 * The defect fixed in #801 lived here: AttendanceController::store() and ::export()
 * kept their own hardcoded isClassTeacherOfStandardLink() checks after the request's
 * authorize() had been made scope-aware, so subject-taught and school-wide writes
 * passed authorization and were then refused. The helper was already well covered,
 * which is exactly why that gap stayed hidden. These tests exercise the controller
 * action for real, per mode, over all four class relationships.
 */
class AttendanceWriteScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $peer;

    private User $student;

    private AcademicYear $year;

    private StandardLink $homeroom;

    private StandardLink $subjectTaught;

    private StandardLink $unrelated;

    private StandardLink $inactive;

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
            'name' => 'Write Scope School',
            'slug' => 'write-scope-'.uniqid(),
            'email' => 'write-scope-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->teacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'writer.scope@t.sch.ug']);
        $this->peer = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'peer.writer.scope@t.sch.ug']);
        $this->student = User::factory()->create(['usergroup_id' => 6, 'school_id' => $this->school->id, 'status' => 'active', 'email' => 'student.writer.scope@t.sch.ug']);

        Userprofile::create([
            'user_id' => $this->student->id, 'school_id' => $this->school->id, 'usergroup_id' => 6,
            'firstname' => 'Write', 'lastname' => 'Scope', 'status' => 'active',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => (string) now()->year,
            'description' => 'Current Academic Year',
            'start_date' => now()->subMonths(3)->startOfDay(),
            'end_date' => now()->addMonths(6)->endOfDay(),
            'status' => 1,
        ]);
        Cache::flush();

        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = fn (string $n) => Section::create(['school_id' => $this->school->id, 'name' => $n, 'status' => 1]);

        $make = function (Section $s, User $ct, int $status, string $stream) use ($standard) {
            return StandardLink::create([
                'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
                'standard_id' => $standard->id, 'section_id' => $s->id,
                'class_teacher_id' => $ct->id, 'stream' => $stream, 'status' => $status,
            ]);
        };

        $this->homeroom = $make($section('WS P7'), $this->teacher, 1, 'A');
        $this->subjectTaught = $make($section('WS P6'), $this->peer, 1, 'A');
        $this->unrelated = $make($section('WS P5'), $this->peer, 1, 'A');
        $this->inactive = $make($section('WS P4'), $this->peer, 0, 'A');

        $subject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id, 'section_id' => $this->subjectTaught->section_id,
            'name' => 'Science', 'status' => 1,
        ]);
        Teacherlink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->subjectTaught->id, 'subject_id' => $subject->id, 'teacher_id' => $this->teacher->id,
        ]);
    }

    private function setScope(string $scope): void
    {
        $this->school->setDetailValue(SiteHelper::ATTENDANCE_SCOPE_KEY, $scope);
        SiteHelper::forgetAttendanceScope($this->school->id);
    }

    /** Three distinct, valid attendance dates (the duplicate-session rule blocks reuse). */
    private function dateFor(int $offset): string
    {
        $d = Carbon::parse($this->year->start_date)->addDays($offset + 1);
        if ($d->gt(Carbon::today())) {
            $d = Carbon::today();
        }

        return $d->format('Y-m-d');
    }

    private function postAttendance(StandardLink $link, int $offset): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->teacher)->postJson('/teacher/attendance/add', [
            'standardLink_id' => $link->id,
            'date' => $this->dateFor($offset),
            'session' => 'forenoon',
            'absentCount' => 0,
            'presentCount' => 1,
            'present_id0' => $this->student->id,
        ]);
    }

    public function test_homeroom_class_is_allowed_in_every_mode(): void
    {
        foreach (['class_teacher_only', 'classes_i_teach', 'school_wide'] as $i => $mode) {
            $this->setScope($mode);
            $r = $this->postAttendance($this->homeroom, $i);
            $this->assertSame(200, $r->status(), "homeroom write should be allowed under {$mode}");
        }
    }

    public function test_subject_taught_class_denied_under_class_teacher_only_and_allowed_under_classes_i_teach(): void
    {
        $this->setScope('class_teacher_only');
        $this->assertSame(403, $this->postAttendance($this->subjectTaught, 1)->status(), 'subject-taught class must be refused under class_teacher_only');

        $this->setScope('classes_i_teach');
        $this->assertSame(200, $this->postAttendance($this->subjectTaught, 2)->status(), 'subject-taught class must be allowed under classes_i_teach');
    }

    public function test_unrelated_class_denied_in_narrow_modes_and_allowed_under_school_wide(): void
    {
        $this->setScope('class_teacher_only');
        $this->assertSame(403, $this->postAttendance($this->unrelated, 1)->status(), 'unrelated class must be refused under class_teacher_only');

        $this->setScope('classes_i_teach');
        $this->assertSame(403, $this->postAttendance($this->unrelated, 2)->status(), 'unrelated class must still be refused under classes_i_teach');

        $this->setScope('school_wide');
        $this->assertSame(200, $this->postAttendance($this->unrelated, 3)->status(), 'unrelated class must be allowed under school_wide');
    }

    public function test_inactive_class_is_refused_in_every_mode(): void
    {
        foreach (['class_teacher_only', 'classes_i_teach', 'school_wide'] as $i => $mode) {
            $this->setScope($mode);
            $r = $this->postAttendance($this->inactive, $i);
            $this->assertSame(403, $r->status(), "inactive class must be refused under {$mode}");
            $this->assertDatabaseMissing('attendances', ['standardLink_id' => $this->inactive->id]);
        }
    }

    public function test_refused_writes_create_no_rows(): void
    {
        $this->setScope('classes_i_teach');

        $this->postAttendance($this->unrelated, 1)->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'school_id' => $this->school->id,
            'standardLink_id' => $this->unrelated->id,
        ]);
    }

    public function test_export_path_follows_the_same_scope(): void
    {
        $this->setScope('classes_i_teach');
        $this->actingAs($this->teacher)->get('/teacher/attendance/export/'.$this->unrelated->id)->assertForbidden();

        $this->actingAs($this->teacher)->get('/teacher/attendance/export/'.$this->homeroom->id)->assertOk();

        $this->setScope('school_wide');
        $this->actingAs($this->teacher)->get('/teacher/attendance/export/'.$this->unrelated->id)->assertOk();
    }
}
