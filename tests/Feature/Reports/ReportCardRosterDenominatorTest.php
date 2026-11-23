<?php

namespace Tests\Feature\Reports;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
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
use Smalot\PdfParser\Parser;
use Tests\TestCase;

/**
 * "Pupils in class" and "Nth of M" count the class roster. A learner with no
 * mark for the sitting keeps a card, prints dashes, and holds no position.
 */
class ReportCardRosterDenominatorTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Standard $standard;

    private Section $section;

    private StandardLink $stdLink;

    private Subject $subject;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            ['id' => 2, 'name' => 'End of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Roster Denominator School',
            'slug' => 'roster-denominator-'.uniqid(),
            'email' => uniqid().'@roster.test',
            'phone' => '077'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
            'report_template' => 'progressive',
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
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 4,
            'status' => '1',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.5 Blue',
            'status' => 1,
        ]);

        $this->teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);

        $this->stdLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $this->teacher->id,
            'status' => '1',
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);

        foreach ([
            ['grade' => 'D1', 'points' => 1, 'min' => 80, 'max' => 100],
            ['grade' => 'F9', 'points' => 9, 'min' => 0, 'max' => 79],
        ] as $band) {
            SchoolGradingSystem::create([
                'school_id' => $this->school->id,
                'standard_id' => $this->standard->id,
                'grade' => $band['grade'],
                'points' => $band['points'],
                'min_score' => $band['min'],
                'max_score' => $band['max'],
                'remark' => $band['grade'].' band',
            ]);
        }

        $this->exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => 2,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => '2026-04-01',
            'status' => 'submitted',
        ]));
    }

    private Exam $exam;

    private function learner(string $name, ?int $score): User
    {
        $learner = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => $name,
            'status' => 'active',
            'registration_number' => 'KLS001'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $learner->id,
            'standardLink_id' => $this->stdLink->id,
        ]);

        if ($score !== null) {
            Marks::create([
                'student_id' => $learner->id,
                'exam_id' => $this->exam->id,
                'school_id' => $this->school->id,
                'subject_id' => $this->subject->id,
                'teacher_id' => $this->teacher->id,
                'section_id' => $this->section->id,
                'marks' => $score,
                'grade' => $score >= 80 ? 'D1' : 'F9',
            ]);
        }

        return $learner;
    }

    private function text(User $learner): string
    {
        $pdf = app(StudentReportCardService::class)->pdfForStudent(
            (int) $this->school->id,
            $this->stdLink,
            $learner,
        );

        // Dompdf wraps lines; compare on one line so a label and its value match.
        return trim((string) preg_replace('/\s+/', ' ', (new Parser)->parseContent($pdf)->getText()));
    }

    /**
     * The missing-marks report exists to find the learners who have no mark at
     * all. Building its list from mark rows made it blind to exactly those.
     */
    public function test_the_missing_marks_report_lists_the_learner_with_no_marks(): void
    {
        $this->withoutMiddleware();

        $marked = $this->learner('Marked Learner', 90);
        $blank = $this->learner('Blank Sitting Learner', null);

        $response = $this->actingAs($this->teacher)->get(route('admin.reports.cards.missing', [
            'term' => $this->term->id,
        ]));

        $response->assertOk();

        $text = trim((string) preg_replace('/\s+/', ' ', (new Parser)->parseContent($response->getContent())->getText()));

        $this->assertStringContainsString('BLANK SITTING LEARNER', $text);
        $this->assertStringNotContainsString('MARKED LEARNER', $text);
        $this->assertStringContainsString('1 students missing marks', $text);
    }

    public function test_the_printed_card_counts_the_whole_roster_and_a_blank_sitting_holds_no_place(): void
    {
        $first = $this->learner('First Learner', 90);
        $second = $this->learner('Second Learner', 60);
        $blank = $this->learner('No Marks Learner', null);

        $firstText = $this->text($first);
        $this->assertStringContainsString('PUPILS IN CLASS 3', $firstText);
        $this->assertStringContainsString('of 3', $firstText);

        $blankText = $this->text($blank);
        $this->assertStringContainsString('PUPILS IN CLASS 3', $blankText);
        $this->assertStringContainsString('Incomplete: End of Term.', $blankText);
        $this->assertStringContainsString('POSITION -', $blankText);
    }
}
