<?php

namespace Tests\Feature\Reports;

use App\Contracts\Results\GradingScale;
use App\Helpers\GradingHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\Records\RecordSnapshot;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Records\SnapshotService;
use App\Services\Reports\ReportScoring;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultsPolicy;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use App\Services\Results\ThresholdDivisionRule;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A blank and an absence score as 0 (F9, 9 points) on an unreleased progressive
 * card. A set nobody has started is left out. A release already stored keeps
 * the older maths, and its snapshot payload is not rewritten.
 */
class ProgressiveMissingMarksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('exam_types')->insert([
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Opening paper', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_two_fails_plus_two_blanks_move_from_division_2_to_u(): void
    {
        [$school, $ctx, $learner] = $this->school();

        $skipped = $this->sheet($school, $ctx, false)->student((int) $learner->id);
        $this->assertSame(18, $skipped->aggregate);
        $this->assertSame('2', $skipped->division);

        $counted = $this->sheet($school, $ctx, true)->student((int) $learner->id);
        $this->assertSame(36, $counted->aggregate);
        $this->assertSame('U', $counted->division);
        $this->assertSame(30.0, (float) $counted->total);
        $this->assertSame(ResultCell::ABSENT, $counted->cell((int) $ctx['science']->id)->status);
        $this->assertSame(9, $counted->cell((int) $ctx['science']->id)->points);
        $this->assertNull($counted->cell((int) $ctx['science']->id)->mark);
        $this->assertSame(ResultCell::MISSING, $counted->cell((int) $ctx['social']->id)->status);
        $this->assertSame(9, $counted->cell((int) $ctx['social']->id)->points);
        $unstarted = fn ($sheet) => array_values(array_filter(
            $sheet->sittings,
            fn ($sitting) => $sitting->name === 'Opening paper'
        ));
        $this->assertCount(1, $unstarted($this->sheet($school, $ctx, false)));
        $this->assertNull($unstarted($this->sheet($school, $ctx, false))[0]->student((int) $learner->id)->total);
        $this->assertSame([], $unstarted($this->sheet($school, $ctx, true)));

        $card = $this->card($school, $ctx, $learner);
        $this->assertSame('U', $card['eotDivision']);
        $this->assertSame(36, $card['eotAgg']);
        $this->assertSame([], $card['monthlySets']);
        $this->assertSame(['End Of Term'], array_column($card['termSets'], 'label'));
        $html = view('admin.marks.report-templates.formal', $card)->render();
        $this->assertStringContainsString('Missing', $html);
        $this->assertStringContainsString('ABS', $html);
        $this->assertStringContainsString('F9', $html);
        $this->assertStringNotContainsString('MONTHLY RESULTS', $html);
        $this->assertStringNotContainsString('Opening paper', $html);
    }

    public function test_a_released_report_keeps_skip_blank_maths_and_its_snapshot(): void
    {
        [$school, $ctx, $learner] = $this->school();
        $release = MarksRelease::create([
            'school_id' => $school->id,
            'exam_id' => $ctx['englishExam']->id,
            'scope' => 'class',
            'standard_link_id' => $ctx['link']->id,
            'scheduled_at' => '2026-04-21 09:00:00',
            'released_at' => '2026-04-21 10:00:00',
            'status' => MarksRelease::STATUS_RELEASED,
            'created_by' => $ctx['teacher']->id,
            'timezone' => 'Africa/Kampala',
            'scoring_rule' => null,
        ]);
        $snapshot = RecordSnapshot::create([
            'school_id' => $school->id,
            'student_id' => $learner->id,
            'academic_year_id' => $ctx['year']->id,
            'academic_term_id' => $ctx['term']->id,
            'version' => 1,
            'payload' => ['aggregate' => 18, 'division' => '2'],
            'release_id' => $release->id,
            'state' => RecordSnapshot::STATE_CURRENT,
        ]);

        $card = $this->card($school, $ctx, $learner);
        $this->assertSame('2', $card['eotDivision']);
        $this->assertSame(18, $card['eotAgg']);
        $html = view('admin.marks.report-templates.formal', $card)->render();
        $this->assertStringNotContainsString('Missing', $html);

        $fresh = app(ReportScoring::class)->freeze($release->fresh());
        $this->assertSame(ReportScoring::MISSING_AS_ZERO, $fresh);
        app(SnapshotService::class)->capture($release->fresh(), 'correction');

        $this->assertSame(18, $snapshot->fresh()->payload['aggregate']);
        $this->assertSame(RecordSnapshot::STATE_SUPERSEDED, $snapshot->fresh()->state);
        $next = RecordSnapshot::query()->where('student_id', $learner->id)->where('version', 2)->sole();
        $this->assertSame(36, $next->payload['aggregate']);
        $this->assertSame('U', $next->payload['division']);
        $this->assertSame(ReportScoring::MISSING_AS_ZERO, $next->payload['scoring_rule']);
        $this->assertSame(ReportScoring::MISSING_AS_ZERO, $release->fresh()->scoring_rule);
    }

    /**
     * @return array{0: School, 1: array<string, mixed>, 2: User}
     */
    private function school(): array
    {
        $school = School::create([
            'name' => 'Blank Primary', 'slug' => 'blank-'.uniqid(), 'email' => uniqid().'@blank.test',
            'phone' => '075'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => 'formal',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        GradingHelper::seedGradingForSchool((int) $school->id, 'primary');
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5', 'status' => 1]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'name' => 'Blank Teacher',
            'email' => uniqid().'@blank-teacher.test', 'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I',
            'status' => 'current', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30',
        ]);
        $subjects = [];
        foreach (['English' => 'ENG', 'Mathematics' => 'MTH', 'Science' => 'SCI', 'Social Studies' => 'SST'] as $name => $code) {
            $subjects[$code] = Subject::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
                'section_id' => $section->id, 'name' => $name, 'code' => $code, 'type' => 'core', 'status' => 1,
            ]);
        }
        $learner = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => 'Amina Kato',
            'email' => uniqid().'@blank-learner.test', 'status' => 'active', 'registration_number' => 'KLS0092001',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'user_id' => $learner->id, 'standardLink_id' => $link->id,
        ]);

        $exams = [];
        foreach ($subjects as $code => $subject) {
            $exams[$code] = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => 2,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => '2026-04-20 09:00:00', 'status' => 'submitted',
            ]));
            Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => 3,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => '2026-02-10 09:00:00', 'status' => 'submitted',
            ]));
        }
        $this->mark($school, $exams['ENG'], $subjects['ENG'], $teacher, $section, $learner, 20, false);
        $this->mark($school, $exams['MTH'], $subjects['MTH'], $teacher, $section, $learner, 10, false);
        $this->mark($school, $exams['SCI'], $subjects['SCI'], $teacher, $section, $learner, null, true);

        $ctx = [
            'year' => $year, 'standard' => $standard, 'section' => $section, 'link' => $link,
            'teacher' => $teacher, 'term' => $term, 'english' => $subjects['ENG'], 'maths' => $subjects['MTH'],
            'science' => $subjects['SCI'], 'social' => $subjects['SST'], 'englishExam' => $exams['ENG'],
        ];

        return [$school, $ctx, $learner];
    }

    private function mark(School $school, Exam $exam, Subject $subject, User $teacher, Section $section, User $learner, ?float $score, bool $absent): void
    {
        Marks::create([
            'student_id' => $learner->id, 'exam_id' => $exam->id, 'school_id' => $school->id,
            'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
            'marks' => $score, 'grade' => $absent ? 'AB' : 'stored', 'absent' => $absent,
        ]);
    }

    private function sheet(School $school, array $ctx, bool $countMissingAsZero)
    {
        return app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $school->id,
            sectionId: (int) $ctx['section']->id,
            academicTermId: (int) $ctx['term']->id,
            standardLinkId: (int) $ctx['link']->id,
        ), new ResultsPolicy(
            app(GradingScale::class),
            new ThresholdDivisionRule,
            ResultsPolicy::BASIS_AGGREGATE,
            higherIsBetter: false,
            countMissingAsZero: $countMissingAsZero,
        ));
    }

    /** @return array<string, mixed> */
    private function card(School $school, array $ctx, User $learner): array
    {
        $captured = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF');
        $this->app->instance('dompdf.wrapper', $pdf);
        app(StudentReportCardService::class)->pdfForStudent((int) $school->id, $ctx['link'], $learner);

        return $captured ?? [];
    }
}
