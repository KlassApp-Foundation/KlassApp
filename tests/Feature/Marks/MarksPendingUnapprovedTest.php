<?php

namespace Tests\Feature\Marks;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Results\RangeDivisionRule;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultsPolicy;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use App\Services\Results\SchoolGradingScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Approved 2026-11-22 (R4): a subject that is not approved yet shows "Pending"
 * and does not count in Total, Aggregate or Division.
 *
 * Three of the four Division subjects are approved and carry a mark; the fourth
 * carries a mark but its submission is still a draft. Its mark must not reach
 * the Total, the Aggregate or the Division, and it must print as Pending.
 */
class MarksPendingUnapprovedTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'school admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        DB::table('exam_types')->upsert([
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->ctx = $this->school();
        $this->learner = $this->learner($this->ctx, 'Amina Kato');
    }

    public function test_an_unapproved_subject_prints_pending_not_a_mark(): void
    {
        [$approved, $draft] = $this->threeApprovedOneDraft();

        $row = $this->sheetRow();
        $this->assertSame(ResultCell::PENDING, $row->cell($draft->id)->sheetLabel(), 'the unapproved subject reads Pending');
        $this->assertSame(ResultCell::PENDING, ResultCell::printedMark($row->cell($draft->id)), 'every printed surface reads Pending');
        $this->assertSame('80', $row->cell($approved[0]->id)->sheetLabel(), 'an approved subject still prints its mark');
    }

    public function test_the_unapproved_mark_stays_out_of_total_aggregate_and_division(): void
    {
        [$approved, $draft] = $this->threeApprovedOneDraft();

        $row = $this->sheetRow();

        // english 80, maths 80, science 80 approved; social studies 95 is a draft.
        $this->assertSame(240, (int) round((float) $row->total), 'only the three approved marks add up');
        $this->assertSame(3, (int) $row->subjectsCounted, 'the draft subject is not counted');
        $this->assertSame(80, (int) round((float) $row->average), 'the average divides by three, not four');

        // 80 is C3 = 3 points on the fixture scale, three of them = 9.
        $this->assertSame(9, (int) $row->aggregate, 'the draft subject adds no points');
        $this->assertSame('1', (string) $row->division, 'the division comes from the three approved subjects (4-12 is Division 1)');
    }

    public function test_approving_the_fourth_subject_brings_its_mark_back(): void
    {
        [$approved, $draft] = $this->threeApprovedOneDraft();

        ExamMarksSubmission::query()
            ->where('exam_id', $this->examOf($draft)->id)
            ->update(['status' => 'locked', 'approval_status' => 'approved', 'locked_at' => now()]);

        $row = $this->sheetRow();
        $this->assertSame('95', $row->cell($draft->id)->sheetLabel(), 'an approved subject prints its mark');
        $this->assertSame(335, (int) round((float) $row->total));
        $this->assertSame(4, (int) $row->subjectsCounted);
        // 95 is D1 = 1 point, so 3 + 3 + 3 + 1 = 10, still Division 1.
        $this->assertSame(10, (int) $row->aggregate);
    }

    /**
     * @return array{0: list<Subject>, 1: Subject}
     */
    private function threeApprovedOneDraft(): array
    {
        $approved = [$this->ctx['english'], $this->ctx['maths'], $this->ctx['science']];
        foreach ($approved as $subject) {
            $this->sitting($subject, 80, 'approved');
        }
        $draft = $this->ctx['social'];
        $this->sitting($draft, 95, 'draft');

        return [$approved, $draft];
    }

    private function examOf(Subject $subject): Exam
    {
        return Exam::query()
            ->where('school_id', $this->ctx['school']->id)
            ->where('subject_id', $subject->id)
            ->where('academic_term_id', $this->ctx['term']->id)
            ->firstOrFail();
    }

    private function sheetRow(): object
    {
        $policy = new ResultsPolicy(
            app(SchoolGradingScale::class),
            new RangeDivisionRule([
                ['min' => 4, 'max' => 12, 'label' => '1'],
                ['min' => 13, 'max' => 24, 'label' => '2'],
                ['min' => 25, 'max' => 34, 'label' => '3'],
                ['min' => 35, 'max' => null, 'label' => '4'],
            ]),
            countMissingAsZero: true,
        );

        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $this->ctx['school']->id,
            sectionId: (int) $this->ctx['section']->id,
            academicTermId: (int) $this->ctx['term']->id,
            examTypeId: 2,
            countApprovedSubjects: true,
        ), $policy);

        foreach ($sheet->students as $student) {
            if ((int) $student->studentId === (int) $this->learner->id) {
                return $student;
            }
        }

        $this->fail('the learner is not on the sheet');
    }

    private function school(): array
    {
        $school = School::create([
            'name' => 'Pending School',
            'email' => 'pending-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'pending-'.uniqid(),
            'status' => 1,
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
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
        foreach ([[95, 100, 'D1', 1], [90, 94, 'D2', 2], [75, 89, 'C3', 3], [65, 74, 'C4', 4], [60, 64, 'C5', 5],
            [50, 59, 'C6', 6], [45, 49, 'P7', 7], [40, 44, 'P8', 8], [0, 39, 'F9', 9]] as [$min, $max, $grade, $points]) {
            SchoolGradingSystem::create([
                'school_id' => $school->id, 'standard_id' => $standard->id,
                'grade' => $grade, 'points' => $points, 'min_score' => $min, 'max_score' => $max, 'remark' => '',
            ]);
        }
        Cache::forget('school_grading_'.$school->id);

        $section = Section::create(['school_id' => $school->id, 'name' => 'P.6', 'status' => 1]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => 'ct.'.uniqid().'@t.sch.ug', 'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'stream' => 'East',
            'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        $subjects = [];
        foreach (['english' => 'English', 'maths' => 'Mathematics', 'science' => 'Science', 'social' => 'Social Studies'] as $key => $name) {
            $subjects[$key] = Subject::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'name' => $name,
                'code' => strtoupper(substr($key, 0, 4)),
                'type' => 'core',
                'status' => 1,
            ]);
        }

        return compact('school', 'year', 'term', 'standard', 'section', 'link', 'teacher') + $subjects;
    }

    private function learner(array $ctx, string $name): User
    {
        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $ctx['school']->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@t.sch.ug',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'user_id' => $user->id,
            'standardLink_id' => $ctx['link']->id,
        ]);

        return $user;
    }

    private function sitting(Subject $subject, float $score, string $status): Exam
    {
        $exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->ctx['school']->id,
            'standard_id' => $this->ctx['standard']->id,
            'section_id' => $this->ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->ctx['teacher']->id,
            'exam_type_id' => 2,
            'academic_term_id' => $this->ctx['term']->id,
            'academic_year_id' => $this->ctx['year']->id,
            'scheduled_at' => '2026-04-20 09:00:00',
            'status' => 'submitted',
        ]));

        Marks::create([
            'student_id' => $this->learner->id,
            'exam_id' => $exam->id,
            'school_id' => $this->ctx['school']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->ctx['teacher']->id,
            'section_id' => $this->ctx['section']->id,
            'marks' => $score,
            'grade' => '',
        ]);

        ExamMarksSubmission::create([
            'exam_id' => $exam->id,
            'school_id' => $this->ctx['school']->id,
            'class_id' => $this->ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->ctx['teacher']->id,
            'status' => $status === 'approved' ? 'locked' : 'draft',
            'approval_status' => $status === 'approved' ? 'approved' : 'pending',
            'submitted_at' => now(),
            'locked_at' => $status === 'approved' ? now() : null,
        ]);

        return $exam;
    }
}
