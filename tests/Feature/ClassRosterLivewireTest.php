<?php

namespace Tests\Feature;

use App\Livewire\ClassRoster\Index;
use App\Livewire\ClassRoster\Show;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ClassRosterLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_teacher_index_shows_only_scoped_classes_and_show_blocks_cross_school_section(): void
    {
        $school = $this->school('teacher-index');
        $otherSchool = $this->school('teacher-other');
        $year = $this->year($school);
        $otherYear = $this->year($otherSchool);
        $teacher = $this->teacher($school, 'Scoped Teacher');
        $otherTeacher = $this->teacher($otherSchool, 'Other Teacher');
        $section = $this->section($school, 'P1', $teacher->id);
        $otherSection = $this->section($otherSchool, 'P1', $otherTeacher->id);
        $standard = $this->standard($school);
        $otherStandard = $this->standard($otherSchool);
        $this->stream($school, $year, $standard, $section, null, 'A');
        $this->stream($otherSchool, $otherYear, $otherStandard, $otherSection, $otherTeacher->id, 'A');

        Livewire::actingAs($teacher)
            ->test(Index::class)
            ->set('selectedAcademicYearId', $year->id)
            ->assertSee('P1')
            ->assertDontSee('Other Teacher');

        Livewire::actingAs($teacher)->test(Show::class, [
            'sectionId' => $otherSection->id,
            'academicYearId' => $otherYear->id,
        ])->assertStatus(404);
    }

    public function test_admin_sees_empty_class_and_subject_teacher_projection_is_limited(): void
    {
        $school = $this->school('admin-roster');
        $year = $this->year($school);
        $admin = $this->admin($school);
        $subjectTeacher = $this->teacher($school, 'Subject Teacher');
        $standard = $this->standard($school);
        $this->section($school, 'P2');
        $assignedSection = $this->section($school, 'P1');
        $stream = $this->stream($school, $year, $standard, $assignedSection, null, 'A');
        $subject = $this->subject($school, $year, $standard, $assignedSection);
        Teacherlink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $stream->id,
            'subject_id' => $subject->id,
            'teacher_id' => $subjectTeacher->id,
        ]);
        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Subject Roster Student',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $stream->id,
            'medication_problems' => 'private detail',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('selectedAcademicYearId', $year->id)
            ->assertSee('P2')
            ->assertSee('0 streams');

        Livewire::actingAs($subjectTeacher)
            ->test(Show::class, [
                'sectionId' => $assignedSection->id,
                'academicYearId' => $year->id,
            ])
            ->assertSee('Subject view')
            ->assertSee('Subject Roster Student')
            ->assertDontSee('Academic status');
    }

    public function test_admin_show_renders_full_roster_and_route_names_resolve(): void
    {
        $school = $this->school('route-roster');
        $year = $this->year($school);
        $admin = $this->admin($school);
        $section = $this->section($school, 'P3');
        $standard = $this->standard($school);
        $stream = $this->stream($school, $year, $standard, $section, null, 'A');
        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Full Roster Student',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $stream->id,
            'academic_status' => 'pass',
        ]);

        $this->assertSame('/admin/classes', parse_url(route('admin.classes.index'), PHP_URL_PATH));
        $this->assertSame('/admin/classes/' . $section->id, parse_url(route('admin.classes.show', ['section' => $section]), PHP_URL_PATH));

        Livewire::actingAs($admin)
            ->test(Show::class, [
                'sectionId' => $section->id,
                'academicYearId' => $year->id,
            ])
            ->assertSee('Full Roster Student')
            ->assertSee('Academic status');
    }

    private function school(string $suffix): School
    {
        return School::create([
            'name' => 'Portal ' . $suffix,
            'slug' => 'portal-' . $suffix . '-' . uniqid(),
            'email' => 'portal-' . $suffix . '-' . uniqid() . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);
    }

    private function year(School $school): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Portal year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
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

    private function section(School $school, string $name, ?int $teacherId = null): Section
    {
        return Section::create([
            'school_id' => $school->id,
            'name' => $name,
            'class_teacher_id' => $teacherId,
            'status' => 1,
        ]);
    }

    private function stream(School $school, AcademicYear $year, Standard $standard, Section $section, ?int $teacherId, string $stream): StandardLink
    {
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
            'name' => 'Admin ' . $school->id,
        ]);
    }
}
