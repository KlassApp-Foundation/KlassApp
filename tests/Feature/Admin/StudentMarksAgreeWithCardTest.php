<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
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
use App\Services\Results\ResultCell;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The per-student marks page uses the same sheet as the report card.
 * A set is named, an unapproved subject is a dash, an approved blank is
 * Missing, and the combined figure is the sheet total the card prints.
 */
class StudentMarksAgreeWithCardTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicTerm $term;

    private Section $section;

    private User $admin;

    private User $student;

    private Subject $english;

    private Subject $mathematics;

    private Subject $socialStudies;

    private Exam $openingEnglish;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 1, 'name' => 'Opening Paper', 'code' => 'OPN', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Practice Paper', 'code' => 'PRC', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Middle Paper', 'code' => 'MDL', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Closing Paper', 'code' => 'CLS', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Final Paper', 'code' => 'FIN', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Agree Page School',
            'email' => 'agree-page@test.sch.ug',
            'phone' => '0700000093',
            'slug' => 'agree-page-school',
            'status' => 1,
            'report_composition' => [
                '1' => ['include' => true, 'weight' => 40],
                '2' => ['include' => false],
                '3' => ['include' => true, 'weight' => 30],
                '4' => ['include' => true, 'weight' => 30],
                '5' => ['include' => false],
            ],
        ]);
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term III',
            'status' => 'current',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-06-30',
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_upper',
            'order' => 2,
            'status' => '1',
        ]);
        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.4',
            'status' => 1,
        ]);
        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'email' => 'admin.agree-page@t.sch.ug',
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'email' => 'teacher.agree-page@t.sch.ug',
        ]);
        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $teacher->id,
            'status' => '1',
        ]);
        $this->english = $this->subject($year->id, $standard->id, 'English', 'ENG');
        $this->mathematics = $this->subject($year->id, $standard->id, 'Mathematics', 'MTC');
        $this->socialStudies = $this->subject($year->id, $standard->id, 'Social Studies', 'SST');
        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Lena Okello',
            'email' => 'lena.agree-page@t.sch.ug',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $link->id,
        ]);

        $this->openingEnglish = $this->paper(1, '2026-02-10', $this->english, $teacher, $year->id, $standard->id);
        $openingMaths = $this->paper(1, '2026-02-10', $this->mathematics, $teacher, $year->id, $standard->id);
        $practiceMaths = $this->paper(2, '2026-03-10', $this->mathematics, $teacher, $year->id, $standard->id);
        $middleEnglish = $this->paper(3, '2026-04-10', $this->english, $teacher, $year->id, $standard->id);
        $middleMaths = $this->paper(3, '2026-04-10', $this->mathematics, $teacher, $year->id, $standard->id);
        $middleSocial = $this->paper(3, '2026-04-10', $this->socialStudies, $teacher, $year->id, $standard->id);
        $closingEnglish = $this->paper(4, '2026-05-10', $this->english, $teacher, $year->id, $standard->id);
        $closingMaths = $this->paper(4, '2026-05-10', $this->mathematics, $teacher, $year->id, $standard->id);
        $finalMaths = $this->paper(5, '2026-06-10', $this->mathematics, $teacher, $year->id, $standard->id);

        $this->score($this->openingEnglish, $this->english, 90);
        $this->score($practiceMaths, $this->mathematics, 77);
        $this->score($middleEnglish, $this->english, 90);
        $this->score($middleMaths, $this->mathematics, 89);
        $this->absent($middleSocial, $this->socialStudies);
        $this->score($closingEnglish, $this->english, 90);
        $this->score($closingMaths, $this->mathematics, 89);
        $this->score($finalMaths, $this->mathematics, 55);

        $this->approve($this->openingEnglish, $this->english);
        $this->approve($openingMaths, $this->mathematics);
        $this->approve($middleEnglish, $this->english);
        $this->approve($middleMaths, $this->mathematics);
        $this->approve($middleSocial, $this->socialStudies);
        $this->approve($closingEnglish, $this->english);
        $this->approve($closingMaths, $this->mathematics);
        ExamMarksSubmission::create([
            'school_id' => $this->school->id,
            'exam_id' => $practiceMaths->id,
            'class_id' => $this->section->id,
            'subject_id' => $this->mathematics->id,
            'teacher_id' => $teacher->id,
            'status' => 'draft',
            'approval_status' => 'pending',
            'submitted_at' => null,
        ]);

        foreach ([
            $this->openingEnglish, $openingMaths,
            $middleEnglish, $middleMaths, $middleSocial,
            $closingEnglish, $closingMaths,
        ] as $exam) {
            $this->release($exam, $link->id);
        }
    }

    public function test_student_page_matches_the_sheet_and_the_card_total(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.marks.student.class', [$this->student, $this->section->id, $this->openingEnglish->id]))
            ->assertOk()
            ->assertSee('Lena Okello')
            ->assertSee('Opening Paper')
            ->assertSee('Practice Paper')
            ->assertSee('Middle Paper')
            ->assertSee('Closing Paper')
            ->assertSee('Final Paper')
            ->assertDontSee('FEBRUARY')
            ->assertDontSee('MARCH')
            ->assertDontSee('JUNE')
            ->assertSee('Missing')
            ->assertSee('>–<', false)
            ->assertDontSee('>77<', false)
            ->getContent();

        $sheet = $this->sheet(true);
        $learner = $sheet->student($this->student->id);
        $this->assertNotNull($learner);
        $cardTotal = (string) $learner->total;

        $opening = $this->sitting($sheet, 'Opening Paper');
        $practice = $this->sitting($sheet, 'Practice Paper');
        $middle = $this->sitting($sheet, 'Middle Paper');
        $this->assertSame(ResultCell::MISSING, $opening->student($this->student->id)->cell($this->mathematics->id)->status);
        $this->assertSame(ResultCell::UNAPPROVED, $practice->student($this->student->id)->cell($this->mathematics->id)->status);
        $this->assertNull($practice->student($this->student->id)->total);
        $this->assertSame(ResultCell::ABSENT, $middle->student($this->student->id)->cell($this->socialStudies->id)->status);
        $this->assertNotNull($opening->student($this->student->id)->total);

        $classSheet = $this->sheet(false);
        $classPractice = $this->sitting($classSheet, 'Practice Paper');
        $this->assertSame(77.0, $classPractice->student($this->student->id)->cell($this->mathematics->id)->mark);

        $this->assertMatchesRegularExpression(
            '/data-testid="student-result-total">\s*'.preg_quote($cardTotal, '/').'\s*</',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-testid="student-sitting-total-2026-02">\s*'.preg_quote((string) $opening->student($this->student->id)->total, '/').'\s*</',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-testid="student-sitting-total-2026-03">\s*—\s*</',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-testid="student-result-total">\s*'.preg_quote((string) $learner->displayTotal, '/').'\s*</',
            $html
        );
    }

    private function sheet(bool $forStudent): \App\Services\Results\ResultSheet
    {
        return app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $this->school->id,
            sectionId: (int) $this->section->id,
            academicTermId: (int) $this->term->id,
            studentId: $forStudent ? (int) $this->student->id : null,
            countApprovedSubjects: true,
        ), app(ReportScoring::class)->displayPolicy(
            (int) $this->school->id,
            (int) $this->section->id,
            (int) $this->term->id,
            null,
            true,
        ));
    }

    private function sitting(\App\Services\Results\ResultSheet $sheet, string $name): \App\Services\Results\ResultSitting
    {
        foreach ($sheet->sittings as $sitting) {
            if ($sitting->name === $name) {
                return $sitting;
            }
        }

        $this->fail('Sitting '.$name.' was not on the sheet.');
    }

    private function subject(int $yearId, int $standardId, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $yearId,
            'standard_id' => $standardId,
            'section_id' => $this->section->id,
            'name' => $name,
            'code' => $code,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function paper(int $typeId, string $when, Subject $subject, User $teacher, int $yearId, int $standardId): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $standardId,
            'section_id' => $this->section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => $typeId,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $yearId,
            'scheduled_at' => $when,
            'status' => 'submitted',
        ]));
    }

    private function score(Exam $exam, Subject $subject, int $mark): void
    {
        Marks::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'teacher_id' => $exam->teacher_id,
            'section_id' => $this->section->id,
            'marks' => $mark,
            'grade' => 'C3',
        ]);
    }

    private function absent(Exam $exam, Subject $subject): void
    {
        Marks::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam->id,
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'teacher_id' => $exam->teacher_id,
            'section_id' => $this->section->id,
            'marks' => null,
            'grade' => 'AB',
            'absent' => 1,
        ]);
    }

    private function approve(Exam $exam, Subject $subject): void
    {
        ExamMarksSubmission::create([
            'school_id' => $this->school->id,
            'exam_id' => $exam->id,
            'class_id' => $this->section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $exam->teacher_id,
            'status' => 'locked',
            'approval_status' => 'approved',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);
    }

    private function release(Exam $exam, int $linkId): void
    {
        MarksRelease::create([
            'school_id' => $this->school->id,
            'exam_id' => $exam->id,
            'scope' => 'class',
            'standard_link_id' => $linkId,
            'scheduled_at' => now(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'scoring_rule' => ReportScoring::MISSING_AS_ZERO,
            'created_by' => $this->admin->id,
        ]);
    }
}
