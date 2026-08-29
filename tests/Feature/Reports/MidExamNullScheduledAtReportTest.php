<?php

namespace Tests\Feature\Reports;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression: report cards must render when a MID exam in the same term has
 * null scheduled_at (CT create form / admin create allow nullable scheduled_at).
 * Reproduces school 124 shakedown crash via pdfForStudent().
 */
class MidExamNullScheduledAtReportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $student;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Standard $standard;

    private Section $section;

    private StandardLink $stdLink;

    private Subject $subject;

    private Exam $eot;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'MID Null Date School',
            'email' => 'mid-null@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'slug' => 'mid-null-' . uniqid(),
            'status' => 1,
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_upper',
            'order' => 7,
            'status' => '1',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary Seven',
            'status' => 1,
        ]);

        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'email' => 'ct.midnull@t.sch.ug',
        ]);

        $this->stdLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $teacher->id,
            'status' => '1',
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Null Mid Student',
            'email' => 'student.midnull@t.sch.ug',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $this->stdLink->id,
        ]);

        $this->eot = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => 2,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => '2026-06-15',
            'status' => 'done',
        ]));

        Marks::create([
            'student_id' => $this->student->id,
            'exam_id' => $this->eot->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $this->section->id,
            'marks' => 72,
            'grade' => 'D1',
        ]);
    }

    public function test_pdf_for_student_succeeds_when_mid_exam_has_null_scheduled_at(): void
    {
        $teacherId = $this->eot->teacher_id;

        Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacherId,
            'exam_type_id' => 1,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => null,
            'status' => 'undone',
        ]));

        $pdf = app(StudentReportCardService::class)->pdfForStudent(
            $this->school->id,
            $this->stdLink,
            $this->student
        );

        $this->assertNotSame('', $pdf);
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_eot_only_report_still_generates_after_mid_label_helpers_exist(): void
    {
        $pdf = app(StudentReportCardService::class)->pdfForStudent(
            $this->school->id,
            $this->stdLink,
            $this->student
        );

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_mid_exam_label_helpers_handle_null_and_dated_exams(): void
    {
        $dated = Exam::withoutEvents(fn () => Exam::make([
            'scheduled_at' => '2026-04-10',
        ]));
        $dated->setRelation('examType', (object) ['code' => 'MID']);

        $nullDate = Exam::withoutEvents(fn () => Exam::make([
            'scheduled_at' => null,
        ]));

        $this->assertSame('APR MID', StudentReportCardService::midExamControlColumnLabel($dated));
        $this->assertSame('MID', StudentReportCardService::midExamControlColumnLabel($nullDate));
        $this->assertSame('APRIL', StudentReportCardService::midExamMonthRowLabel($dated));
        $this->assertSame('MID TERM', StudentReportCardService::midExamMonthRowLabel($nullDate));
    }
}
