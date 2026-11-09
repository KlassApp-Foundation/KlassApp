<?php

namespace Tests\Feature\Exams;

use App\Contracts\Results\ReleaseGate;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * An exam set creates one exam per class on the base section ("Primary One"),
 * while students sit in its streams ("Primary One E" / "W"). A release on the
 * class has to reach the streams, and a stream's report card has to resolve the
 * class's exam and list only that stream's students.
 */
class BaseClassReportAndReleaseTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Section $base;

    private StandardLink $baseLink;

    private StandardLink $streamE;

    private StandardLink $streamW;

    private User $studentE;

    private User $studentW;

    private User $admin;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'End of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Base Class School', 'slug' => 'base-class-'.uniqid(), 'email' => uniqid().'@t.sch.ug',
            'phone' => '0700000009', 'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026', 'description' => 'Current',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term 3', 'status' => 'current', 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-01',
        ]);

        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 2, 'status' => 1]);

        $this->base = Section::create(['school_id' => $this->school->id, 'name' => 'Primary One', 'status' => 1]);
        $streamE = Section::create(['school_id' => $this->school->id, 'name' => 'Primary One E', 'status' => 1]);
        $streamW = Section::create(['school_id' => $this->school->id, 'name' => 'Primary One W', 'status' => 1]);

        $this->baseLink = $this->link($standard, $this->base);
        $this->streamE = $this->link($standard, $streamE);
        $this->streamW = $this->link($standard, $streamW);

        $this->admin = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => 3, 'name' => 'Admin',
            'email' => 'admin-'.uniqid().'@test.ug', 'password' => bcrypt('secret'), 'status' => 'active',
        ]);

        $this->studentE = $this->student('Amina', $this->streamE);
        $this->studentW = $this->student('Brian', $this->streamW);

        $subject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id, 'section_id' => $this->base->id,
            'name' => 'Mathematics', 'code' => 'MAT', 'type' => 'core', 'status' => 1,
        ]);

        // The exam sits on the class, not on a stream.
        $this->exam = Exam::create([
            'school_id' => $this->school->id, 'standard_id' => $standard->id, 'section_id' => $this->base->id,
            'academic_year_id' => $this->year->id, 'academic_term_id' => $this->term->id,
            'subject_id' => $subject->id, 'exam_type_id' => 1, 'teacher_id' => $this->admin->id, 'status' => 'undone',
        ]);

        foreach ([$this->studentE, $this->studentW] as $i => $student) {
            Marks::create([
                'school_id' => $this->school->id, 'exam_id' => $this->exam->id, 'student_id' => $student->id,
                'subject_id' => $subject->id, 'section_id' => $this->base->id, 'teacher_id' => $this->admin->id,
                'marks' => 60 + $i * 10, 'grade' => 'C',
            ]);
        }
    }

    public function test_a_release_on_the_class_reaches_a_student_in_its_stream(): void
    {
        MarksRelease::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'scope' => 'class',
            'standard_link_id' => $this->baseLink->id,
            'scheduled_at' => now(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'created_by' => $this->studentE->id,
            'timezone' => 'Africa/Kampala',
        ]);

        $gate = app(ReleaseGate::class);

        $this->assertTrue($gate->isReleased($this->studentE, $this->exam), 'the E stream sees the class release');
        $this->assertTrue($gate->isReleased($this->studentW, $this->exam), 'the W stream sees the class release');
    }

    public function test_a_streams_report_resolves_the_class_exam_and_lists_only_that_stream(): void
    {
        $service = app(StudentReportCardService::class);

        $resolved = $service->resolveExam($this->school->id, $this->streamE, $this->term->id);
        $this->assertNotNull($resolved);
        $this->assertSame($this->exam->id, $resolved->id, 'the stream resolves the class exam');

        $learners = $service->reportLearners($this->school->id, $this->streamE, $this->term->id);
        $this->assertSame([$this->studentE->id], $learners->pluck('id')->all(), 'only the E stream');
    }

    private function link(Standard $standard, Section $section): StandardLink
    {
        return StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1,
        ]);
    }

    private function student(string $name, StandardLink $link): User
    {
        $user = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => $name,
            'email' => strtolower($name).'-'.uniqid().'@test.ug',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $this->school->id, 'user_id' => $user->id, 'usergroup_id' => 6,
            'firstname' => $name, 'lastname' => 'Learner',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'user_id' => $user->id, 'standardLink_id' => $link->id,
        ]);

        return $user;
    }
}
