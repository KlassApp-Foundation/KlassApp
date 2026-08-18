<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Services\RosterScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class RosterScopeServiceTest extends TestCase
{
    use RefreshDatabase;

    private RosterScopeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->service = app(RosterScopeService::class);
    }

    public function test_admin_sees_all_active_school_sections_and_requested_historical_year(): void
    {
        $school = $this->school('admin');
        $otherSchool = $this->school('other');
        $yearOne = $this->year($school, '2025');
        $yearTwo = $this->year($school, '2026');
        $otherYear = $this->year($otherSchool, '2026');
        $standard = $this->standard($school);
        $active = $this->section($school, 'P1');
        $inactive = $this->section($school, 'P2', null, 0);
        $otherSection = $this->section($otherSchool, 'P1');
        $admin = $this->admin($school);

        $yearOneStream = $this->stream($school, $yearOne, $standard, $active, null, 'A');
        $yearTwoStream = $this->stream($school, $yearTwo, $standard, $active, null, 'A');
        $otherStream = $this->stream($otherSchool, $otherYear, $this->standard($otherSchool), $otherSection, null, 'A');

        $sections = $this->service->visibleSections($admin, $school->id, $yearOne->id)->get();

        $this->assertTrue($sections->contains('id', $active->id));
        $this->assertFalse($sections->contains('id', $inactive->id));
        $this->assertFalse($sections->contains('id', $otherSection->id));
        $this->assertTrue($sections->firstWhere('id', $active->id)->standardLink->contains('id', $yearOneStream->id));

        $historicalStreams = $this->service->visibleStreams($admin, $active, $yearTwo->id);

        $this->assertTrue($historicalStreams->contains('id', $yearTwoStream->id));
        $this->assertFalse($historicalStreams->contains('id', $yearOneStream->id));
        $this->assertNotContains($otherStream->id, $historicalStreams->pluck('id')->all());
    }

    public function test_teacher_visibility_is_the_union_with_stream_precedence_and_section_fallback(): void
    {
        $school = $this->school('teacher-union');
        $year = $this->year($school, '2026');
        $standard = $this->standard($school);
        $sectionTeacher = $this->teacher($school, 'section-teacher');
        $streamTeacher = $this->teacher($school, 'stream-teacher');
        $subjectTeacher = $this->teacher($school, 'subject-teacher');
        $unrelatedTeacher = $this->teacher($school, 'unrelated-teacher');
        $section = $this->section($school, 'P1', $sectionTeacher->id);
        $specificStream = $this->stream($school, $year, $standard, $section, $streamTeacher->id, 'A');
        $fallbackStream = $this->stream($school, $year, $standard, $section, null, 'B');
        $unrelatedStream = $this->stream($school, $year, $standard, $section, $unrelatedTeacher->id, 'C');
        $subject = $this->subject($school, $year, $standard, $section);

        Teacherlink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $specificStream->id,
            'subject_id' => $subject->id,
            'teacher_id' => $subjectTeacher->id,
        ]);

        $subjectStreams = $this->service->visibleStreams($subjectTeacher, $section, $year->id);
        $sectionStreams = $this->service->visibleStreams($sectionTeacher, $section, $year->id);
        $streamStreams = $this->service->visibleStreams($streamTeacher, $section, $year->id);

        $this->assertSame([$specificStream->id], $subjectStreams->pluck('id')->all());
        $this->assertSame([$fallbackStream->id], $sectionStreams->pluck('id')->all());
        $this->assertSame([$specificStream->id], $streamStreams->pluck('id')->all());
        $this->assertFalse($sectionStreams->contains('id', $unrelatedStream->id));
        $this->assertTrue($this->service->visibleSections($subjectTeacher, $school->id, $year->id)->get()->contains('id', $section->id));
        $this->assertTrue($this->service->effectiveClassTeacher($section, $specificStream)->is($streamTeacher));
        $this->assertTrue($this->service->effectiveClassTeacher($section, $fallbackStream)->is($sectionTeacher));
    }

    public function test_subject_teacher_receives_full_roster_with_restricted_student_fields(): void
    {
        $school = $this->school('subject-roster');
        $year = $this->year($school, '2026');
        $standard = $this->standard($school);
        $section = $this->section($school, 'P1');
        $stream = $this->stream($school, $year, $standard, $section, null, 'A');
        $subjectTeacher = $this->teacher($school, 'subject-roster-teacher');
        $subject = $this->subject($school, $year, $standard, $section);
        Teacherlink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $stream->id,
            'subject_id' => $subject->id,
            'teacher_id' => $subjectTeacher->id,
        ]);
        $this->studentAcademic($school, $year, $stream, 'student-one');
        $this->studentAcademic($school, $year, $stream, 'student-two');

        $subjectRoster = $this->service->studentsForStream($stream, $school->id, $subjectTeacher)->get();
        $adminRoster = $this->service->studentsForStream($stream, $school->id, $this->admin($school))->get();

        $this->assertCount(2, $subjectRoster);
        $this->assertCount(2, $adminRoster);
        $this->assertArrayNotHasKey('medication_problems', $subjectRoster->first()->getAttributes());
        $this->assertArrayHasKey('medication_problems', $adminRoster->first()->getAttributes());
    }

    public function test_one_teacher_can_hold_multiple_section_assignments_and_empty_classes_resolve(): void
    {
        $school = $this->school('multiple-assignments');
        $year = $this->year($school, '2026');
        $teacher = $this->teacher($school, 'multi-class-teacher');
        $first = $this->section($school, 'P1', $teacher->id);
        $second = $this->section($school, 'P2', $teacher->id);
        $empty = $this->section($school, 'P3');
        $admin = $this->admin($school);

        $sections = $this->service->visibleSections($teacher, $school->id, $year->id)->get();
        $adminSections = $this->service->visibleSections($admin, $school->id, $year->id)->get();

        $this->assertTrue($sections->contains('id', $first->id));
        $this->assertTrue($sections->contains('id', $second->id));
        $this->assertFalse($sections->contains('id', $empty->id));
        $this->assertTrue($adminSections->contains('id', $empty->id));
        $this->assertCount(0, $this->service->visibleStreams($admin, $empty, $year->id));
    }

    public function test_cross_school_and_invalid_year_access_is_rejected(): void
    {
        $school = $this->school('cross-school-a');
        $otherSchool = $this->school('cross-school-b');
        $year = $this->year($otherSchool, '2026');
        $teacher = $this->teacher($school, 'cross-school-teacher');
        $otherSection = $this->section($otherSchool, 'P1');

        try {
            $this->service->visibleSections($teacher, $otherSchool->id, $year->id);
            $this->fail('Expected a cross-school 403.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        try {
            $this->service->visibleStreams($teacher, $otherSection, $year->id);
            $this->fail('Expected a cross-school 403.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->expectException(NotFoundHttpException::class);
        $this->service->visibleSections($teacher, $school->id, $year->id);
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'Roster ' . $suffix,
            'slug' => 'roster-' . $suffix . '-' . uniqid(),
            'email' => 'roster-' . $suffix . '-' . uniqid() . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);
    }

    private function year(School $school, string $name): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => $name,
            'description' => 'Roster test year',
            'start_date' => $name . '-01-01',
            'end_date' => $name . '-12-31',
            'status' => 1,
        ]);
    }

    private function standard(School $school): Standard
    {
        return Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
    }

    private function section(School $school, string $name, ?int $teacherId = null, int $status = 1): Section
    {
        return Section::create([
            'school_id' => $school->id,
            'name' => $name,
            'class_teacher_id' => $teacherId,
            'status' => $status,
        ]);
    }

    private function stream(
        School $school,
        AcademicYear $year,
        Standard $standard,
        Section $section,
        ?int $teacherId,
        string $stream
    ): StandardLink {
        return StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'class_teacher_id' => $teacherId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => $stream,
            'status' => 1,
        ]);
    }

    private function subject(School $school, AcademicYear $year, Standard $standard, Section $section): Subject
    {
        return Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'code' => 'MTC-' . $section->id,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function teacher(School $school, string $name): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => $name,
        ]);
    }

    private function admin(School $school): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'admin-' . $school->id,
        ]);
    }

    private function studentAcademic(School $school, AcademicYear $year, StandardLink $stream, string $name): StudentAcademic
    {
        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'status' => 'active',
        ]);

        return StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $stream->id,
            'academic_status' => 'pass',
        ]);
    }
}
