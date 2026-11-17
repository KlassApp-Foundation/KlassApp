<?php

namespace Tests\Feature\Reports;

use App\Helpers\GradingHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\MarksRelease;
use App\Models\Records\RecordSnapshot;
use App\Models\ReportDivisionRange;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\MarksRelease\MarksReleaseService;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A release copies the grade bands, division ranges and ranking method.
 * A later edit changes the next release only. A reprint uses the copy.
 */
class ProgressiveRulesFreezeTest extends TestCase
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
        DB::table('exam_types')->upsert([
            ['id' => 2, 'name' => 'Closing paper', 'code' => 'CLP', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        config(['queue.default' => 'sync', 'services.whatsapp.replies_enabled' => false]);
    }

    public function test_a_reprint_keeps_the_rules_from_the_release(): void
    {
        $ctx = $this->school();
        $service = app(MarksReleaseService::class);
        $release = $service->create($ctx['admin'], (int) $ctx['exam']->id, (int) $ctx['link']->id, null, 'Africa/Kampala');
        $this->assertTrue($service->run((int) $release->id));

        $before = $this->card($ctx, $ctx['lower']);
        $this->assertSame(20, $before['eotAgg']);
        $this->assertSame('2', $before['eotDivision']);
        $this->assertSame(1, $before['myPos']);

        $snapshot = RecordSnapshot::query()->where('student_id', $ctx['lower']->id)->sole();
        $this->assertSame(1, $snapshot->payload['progressive_rules']['bands'][0]['points'] ?? $this->bandPoints($snapshot->payload['progressive_rules']['bands'], 'D1'));
        $storedPoints = $this->bandPoints($snapshot->payload['progressive_rules']['bands'], 'D1');
        $this->assertSame(1, $storedPoints);

        $this->editRules($ctx);

        $after = $this->card($ctx, $ctx['lower']);
        $this->assertSame($before['eotAgg'], $after['eotAgg']);
        $this->assertSame($before['eotDivision'], $after['eotDivision']);
        $this->assertSame($before['myPos'], $after['myPos']);
        $this->assertSame($before['total'], $after['total']);

        $html = view('admin.marks.report-templates.formal', $after)->render();
        $this->assertStringContainsString('D1', $html);
        $this->assertStringContainsString('DIVISION: <strong>2</strong>', $html);

        $fresh = $release->fresh();
        $this->assertSame(1, $this->bandPoints(
            $fresh->progressive_rules['standards'][(string) $ctx['standard']->id]['bands'],
            'D1',
        ));
        $this->assertSame($storedPoints, $this->bandPoints($snapshot->fresh()->payload['progressive_rules']['bands'], 'D1'));
        $this->assertSame('aggregate', $fresh->progressive_rules['standards'][(string) $ctx['standard']->id]['ranking_basis']);
    }

    public function test_a_release_without_stored_rules_keeps_the_default_division(): void
    {
        $ctx = $this->school();
        $release = MarksRelease::create([
            'school_id' => $ctx['school']->id,
            'exam_id' => $ctx['exam']->id,
            'scope' => 'class',
            'standard_link_id' => $ctx['link']->id,
            'scheduled_at' => now(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'scoring_rule' => null,
            'progressive_rules' => null,
            'created_by' => $ctx['admin']->id,
            'timezone' => 'Africa/Kampala',
        ]);

        $this->editRules($ctx);

        $card = $this->card($ctx, $ctx['lower']);
        $this->assertSame('2', $card['eotDivision']);
        $this->assertNull($release->fresh()->progressive_rules);

        $release->delete();

        $next = $this->card($ctx, $ctx['lower']);
        $this->assertSame('Q', $next['eotDivision']);
    }

    /** @param list<array<string, mixed>> $bands */
    private function bandPoints(array $bands, string $grade): ?int
    {
        foreach ($bands as $band) {
            if ((string) ($band['grade'] ?? '') === $grade) {
                return (int) $band['points'];
            }
        }

        return null;
    }

    /** @param array<string, mixed> $ctx */
    private function editRules(array $ctx): void
    {
        ReportDivisionRange::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'label' => 'Q',
            'min_aggregate' => null,
            'max_aggregate' => null,
            'position' => 0,
        ]);
        SchoolGradingSystem::query()
            ->where('school_id', $ctx['school']->id)
            ->where('standard_id', $ctx['standard']->id)
            ->where('grade', 'D1')
            ->update(['points' => 9]);
        $ctx['standard']->forceFill(['ranking_basis' => 'total'])->save();
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    private function card(array $ctx, User $learner): array
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
        app(StudentReportCardService::class)->pdfForStudent((int) $ctx['school']->id, $ctx['link'], $learner);
        $this->assertNotNull($captured);

        return $captured;
    }

    /** @return array<string, mixed> */
    private function school(): array
    {
        $school = School::create([
            'name' => 'Freeze Primary', 'slug' => 'freeze-'.uniqid(), 'email' => uniqid().'@freeze.test',
            'phone' => '073'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => 'formal',
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
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => uniqid().'@freeze-teacher.test', 'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'usergroup_id' => 3, 'school_id' => $school->id, 'email' => uniqid().'@freeze-admin.test', 'status' => 'active',
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
        $lower = $this->learner($school, $year, $link, 'Lower Aggregate', 'KLS0096101');
        $higher = $this->learner($school, $year, $link, 'Higher Aggregate', 'KLS0096102');
        $scores = [
            $lower->id => ['ENG' => 95, 'MTH' => 95, 'SCI' => null, 'SST' => null],
            $higher->id => ['ENG' => 50, 'MTH' => 50, 'SCI' => 50, 'SST' => 50],
        ];
        $exam = null;
        foreach ($subjects as $code => $subject) {
            $row = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => 2,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => '2026-04-20 09:00:00', 'status' => 'submitted',
            ]));
            $exam ??= $row;
            foreach ($scores as $studentId => $marks) {
                if ($marks[$code] === null) {
                    continue;
                }
                Marks::create([
                    'student_id' => $studentId, 'exam_id' => $row->id, 'school_id' => $school->id,
                    'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
                    'marks' => $marks[$code], 'grade' => '',
                ]);
            }
            ExamMarksSubmission::create([
                'school_id' => $school->id, 'exam_id' => $row->id, 'class_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'status' => 'locked',
                'approval_status' => 'approved', 'submitted_at' => now(), 'approved_at' => now(),
            ]);
        }

        return compact('school', 'year', 'term', 'standard', 'section', 'link', 'teacher', 'admin', 'lower', 'higher', 'exam');
    }

    private function learner(School $school, AcademicYear $year, StandardLink $link, string $name, string $kls): User
    {
        $learner = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => $name,
            'email' => uniqid().'@freeze-learner.test', 'status' => 'active', 'registration_number' => $kls,
        ]);
        StudentAcademic::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'user_id' => $learner->id, 'standardLink_id' => $link->id,
        ]);

        return $learner;
    }
}
