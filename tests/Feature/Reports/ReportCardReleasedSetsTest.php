<?php

namespace Tests\Feature\Reports;

use App\Helpers\GradingHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Reports\ReportScoring;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

/**
 * A report card names a set only when that set is released and included.
 * An excluded set stays off the card even after it is released. An
 * unreleased set stays off once any set for the class is released. A
 * missing mark on a released included set still prints Missing and counts as 0.
 */
class ReportCardReleasedSetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_excluded_set_and_an_unreleased_set_stay_off_the_card(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Released Sets School',
            'slug' => 'released-sets-'.uniqid(),
            'email' => uniqid().'@released-sets.test',
            'phone' => '077'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
            'report_template' => 'formal',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
            'grading_style' => 'aggregate',
        ]);
        GradingHelper::seedGradingForSchool((int) $school->id, 'primary');
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.7',
            'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'email' => uniqid().'@released-sets-teacher.test',
            'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'stream' => 'A',
            'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term III',
            'status' => 'current',
            'starts_on' => '2026-09-07',
            'ends_on' => '2026-12-04',
        ]);
        $learner = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $school->id,
            'name' => 'Amina Kato',
            'email' => uniqid().'@released-sets-learner.test',
            'status' => 'active',
            'registration_number' => 'KLS0093101',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $learner->id,
            'standardLink_id' => $link->id,
        ]);
        $english = $this->subject($school, $year, $standard, $section, 'English', 'ENG');
        $maths = $this->subject($school, $year, $standard, $section, 'Mathematics', 'MTH');

        $opening = $this->type($school, 'Opening Paper', 'OPP', false);
        $excluded = $this->type($school, 'Excluded Paper', 'EXC', false);
        $unreleased = $this->type($school, 'Unreleased Paper', 'UNR', true);
        $school->forceFill([
            'report_composition' => [
                (string) $opening->id => ['include' => true, 'weight' => 60],
                (string) $excluded->id => ['include' => false],
                (string) $unreleased->id => ['include' => true],
            ],
        ])->save();

        $openingEnglish = $this->exam($school, $year, $term, $standard, $section, $teacher, $english, $opening, '2026-09-08');
        $this->exam($school, $year, $term, $standard, $section, $teacher, $maths, $opening, '2026-09-08');
        $excludedEnglish = $this->exam($school, $year, $term, $standard, $section, $teacher, $english, $excluded, '2026-10-01');
        $excludedMaths = $this->exam($school, $year, $term, $standard, $section, $teacher, $maths, $excluded, '2026-10-01');
        $unreleasedEnglish = $this->exam($school, $year, $term, $standard, $section, $teacher, $english, $unreleased, '2026-11-01');
        $unreleasedMaths = $this->exam($school, $year, $term, $standard, $section, $teacher, $maths, $unreleased, '2026-11-01');

        $this->mark($school, $section, $teacher, $learner, $openingEnglish, $english, 80);
        $this->mark($school, $section, $teacher, $learner, $excludedEnglish, $english, 91);
        $this->mark($school, $section, $teacher, $learner, $excludedMaths, $maths, 92);
        $this->mark($school, $section, $teacher, $learner, $unreleasedEnglish, $english, 77);
        $this->mark($school, $section, $teacher, $learner, $unreleasedMaths, $maths, 66);

        foreach ([$openingEnglish, $excludedEnglish] as $releasedExam) {
            MarksRelease::create([
                'school_id' => $school->id,
                'exam_id' => $releasedExam->id,
                'scope' => 'school',
                'scheduled_at' => now()->subHour(),
                'released_at' => now(),
                'status' => MarksRelease::STATUS_RELEASED,
                'timezone' => 'UTC',
                'scoring_rule' => ReportScoring::MISSING_AS_ZERO,
            ]);
        }

        $pdf = app(StudentReportCardService::class)->pdfForStudent((int) $school->id, $link, $learner);
        $text = (new Parser)->parseContent($pdf)->getText();

        $this->assertStringContainsString('Opening Paper', $text);
        $this->assertStringContainsString('Missing', $text);
        $this->assertStringContainsString('80', $text);
        $this->assertStringNotContainsString('Excluded Paper', $text);
        $this->assertStringNotContainsString('Unreleased Paper', $text);
        $this->assertStringNotContainsString('Not released yet', $text);
        $this->assertStringNotContainsString('183', $text);
        $this->assertStringNotContainsString('143', $text);
    }

    private function type(School $school, string $name, string $code, bool $contributes): ExamType
    {
        return ExamType::create([
            'school_id' => $school->id,
            'name' => $name,
            'code' => $code,
            'contributes_to_report_total' => $contributes,
        ]);
    }

    private function subject(School $school, AcademicYear $year, Standard $standard, Section $section, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => $name,
            'code' => $code,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function exam(
        School $school,
        AcademicYear $year,
        AcademicTerm $term,
        Standard $standard,
        Section $section,
        User $teacher,
        Subject $subject,
        ExamType $type,
        string $when,
    ): Exam {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => $type->id,
            'academic_term_id' => $term->id,
            'academic_year_id' => $year->id,
            'scheduled_at' => $when,
            'status' => 'submitted',
        ]));
    }

    private function mark(
        School $school,
        Section $section,
        User $teacher,
        User $learner,
        Exam $exam,
        Subject $subject,
        int $score,
    ): void {
        Marks::create([
            'student_id' => $learner->id,
            'exam_id' => $exam->id,
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'marks' => $score,
            'grade' => 'stored',
        ]);
    }
}
