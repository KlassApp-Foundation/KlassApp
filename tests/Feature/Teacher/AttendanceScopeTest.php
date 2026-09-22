<?php

namespace Tests\Feature\Teacher;

use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the per-school attendance_scope setting (see
 * docs/plans/attendance-scope-configurable-setting-plan.md):
 * class_teacher_only | classes_i_teach (default) | school_wide.
 */
class AttendanceScopeTest extends TestCase
{
    use RefreshDatabase;

    private $school;
    private $year;
    private $classTeacher;
    private $peerTeacher;
    private $outsider;
    private $ownStream;
    private $otherStream;
    private $inactiveStream;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Scope Test School',
            'slug' => 'scope-'.uniqid(),
            'email' => 'scope-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $otherSchool = School::create([
            'name' => 'Other Scope School',
            'slug' => 'scope-other-'.uniqid(),
            'email' => 'scope-other-'.uniqid().'@t.sch.ug',
            'phone' => '071'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->classTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'ct.scope@t.sch.ug',
        ]);
        $this->peerTeacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'peer.scope@t.sch.ug',
        ]);
        $this->outsider = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $otherSchool->id, 'email' => 'outside.scope@t.sch.ug',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => (string) now()->year,
            'description' => 'Current Academic Year',
            'start_date' => now()->subMonths(2)->startOfDay(),
            'end_date' => now()->addMonths(6)->endOfDay(),
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $ownSection = Section::create(['school_id' => $this->school->id, 'name' => 'P.7', 'status' => 1]);
        $otherSection = Section::create(['school_id' => $this->school->id, 'name' => 'P.6', 'status' => 1]);
        $offSection = Section::create(['school_id' => $this->school->id, 'name' => 'P.5', 'status' => 1]);

        // Homeroom class of classTeacher.
        $this->ownStream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $ownSection->id,
            'class_teacher_id' => $this->classTeacher->id,
            'stream' => 'A',
            'status' => 1,
        ]);

        // Homeroom of peerTeacher. classTeacher teaches a subject here but is NOT its homeroom.
        $this->otherStream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $otherSection->id,
            'class_teacher_id' => $this->peerTeacher->id,
            'stream' => 'A',
            'status' => 1,
        ]);

        $this->inactiveStream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $offSection->id,
            'class_teacher_id' => $this->peerTeacher->id,
            'stream' => 'A',
            'status' => 0,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $otherSection->id,
            'name' => 'Science',
            'status' => 1,
        ]);

        // The subject-teacher assignment: classTeacher teaches Science in otherStream.
        Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->otherStream->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->classTeacher->id,
        ]);

        Cache::flush();
    }

    private function setScope(string $value): void
    {
        $this->school->setDetailValue(SiteHelper::ATTENDANCE_SCOPE_KEY, $value);
        SiteHelper::forgetAttendanceScope($this->school->id);
    }

    public function test_missing_scope_resolves_to_classes_i_teach(): void
    {
        $this->assertSame('classes_i_teach', SiteHelper::resolveAttendanceScope($this->school->id));
    }

    public function test_unknown_scope_value_falls_back_to_classes_i_teach(): void
    {
        $this->setScope('bogus-value');
        $this->assertSame('classes_i_teach', SiteHelper::resolveAttendanceScope($this->school->id));
    }

    public function test_class_teacher_only_denies_subject_taught_class(): void
    {
        $this->setScope('class_teacher_only');

        $this->assertTrue(SiteHelper::canTeacherRecordAttendance($this->school->id, $this->classTeacher->id, $this->ownStream->id));
        $this->assertFalse(SiteHelper::canTeacherRecordAttendance($this->school->id, $this->classTeacher->id, $this->otherStream->id));
    }

    public function test_classes_i_teach_allows_subject_taught_class_and_homeroom(): void
    {
        $this->setScope('classes_i_teach');

        $this->assertTrue(SiteHelper::canTeacherRecordAttendance($this->school->id, $this->classTeacher->id, $this->ownStream->id));
        // The key case: teaches the subject here, is not the homeroom teacher.
        $this->assertTrue(SiteHelper::canTeacherRecordAttendance($this->school->id, $this->classTeacher->id, $this->otherStream->id));
    }

    public function test_school_wide_allows_any_active_class(): void
    {
        $this->setScope('school_wide');

        $this->assertTrue(SiteHelper::canTeacherRecordAttendance($this->school->id, $this->classTeacher->id, $this->otherStream->id));
    }

    public function test_school_wide_denies_inactive_class(): void
    {
        $this->setScope('school_wide');

        $this->assertFalse(SiteHelper::canTeacherRecordAttendance($this->school->id, $this->classTeacher->id, $this->inactiveStream->id));
    }

    public function test_cross_school_teacher_is_denied_in_every_scope(): void
    {
        foreach (SiteHelper::ATTENDANCE_SCOPES as $scope) {
            $this->setScope($scope);

            // Realistic: the outsider authenticates with their own school, so the check runs
            // against that school and cannot reach this school's classes.
            $this->assertFalse(SiteHelper::canTeacherRecordAttendance($this->outsider->school_id, $this->outsider->id, $this->ownStream->id));

            // Mismatched pair: a caller that passed this school with another school's teacher
            // must still fail closed (defence in depth, added 2026-09-22).
            $this->assertFalse(SiteHelper::canTeacherRecordAttendance($this->school->id, $this->outsider->id, $this->ownStream->id));
            $this->assertFalse(SiteHelper::canTeacherRecordAttendance($this->school->id, $this->outsider->id, $this->otherStream->id));
        }
    }

    public function test_web_and_api_listings_agree_under_the_default_scope(): void
    {
        $this->setScope('classes_i_teach');

        $web = $this->actingAs($this->classTeacher)->getJson('/teacher/attendance/list');
        $web->assertOk();
        $webIds = collect($web->json('standardlist'))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();

        $api = $this->actingAs($this->classTeacher, 'sanctum')->getJson('/api/teacher/attendance/list');
        $api->assertOk();
        $apiIds = collect($api->json('standardlist'))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();

        $this->assertSame($webIds->all(), $apiIds->all());
        $this->assertContains($this->ownStream->id, $webIds->all());
        $this->assertContains($this->otherStream->id, $webIds->all());
    }

    public function test_web_list_excludes_subject_class_under_class_teacher_only(): void
    {
        $this->setScope('class_teacher_only');

        $web = $this->actingAs($this->classTeacher)->getJson('/teacher/attendance/list');
        $web->assertOk();
        $ids = collect($web->json('standardlist'))->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($this->ownStream->id, $ids);
        $this->assertNotContains($this->otherStream->id, $ids);
    }
}
