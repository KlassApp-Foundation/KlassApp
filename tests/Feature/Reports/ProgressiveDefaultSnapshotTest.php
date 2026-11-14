<?php

namespace Tests\Feature\Reports;

use App\Helpers\GradingHelper;
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
use Smalot\PdfParser\Parser;
use Tests\TestCase;

/**
 * With no stored division ranges and no stored ranking choice, the marks,
 * aggregate, division and position stay the values captured before those
 * choices existed. The fixture was recaptured when the column headings moved
 * to the school's own exam-set names. Those numbers did not change.
 */
class ProgressiveDefaultSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private string $htmlFixture;

    private string $pdfFixture;

    protected function setUp(): void
    {
        parent::setUp();

        $this->htmlFixture = dirname(__DIR__, 2).'/fixtures/reports/progressive-default-card.html';
        $this->pdfFixture = dirname(__DIR__, 2).'/fixtures/reports/progressive-default-card.pdf.txt';

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_default_rules_match_the_captured_card_and_pdf(): void
    {
        [$html, $pdfText] = $this->render();

        if (getenv('SNAPSHOT_WRITE') === '1') {
            if (! is_dir(dirname($this->htmlFixture))) {
                mkdir(dirname($this->htmlFixture), 0777, true);
            }
            file_put_contents($this->htmlFixture, $html);
            file_put_contents($this->pdfFixture, $pdfText);
        }

        $this->assertFileExists($this->htmlFixture);
        $this->assertFileExists($this->pdfFixture);
        $this->assertSame(file_get_contents($this->htmlFixture), $html);
        $this->assertSame(file_get_contents($this->pdfFixture), $pdfText);
    }

    /** @return array{0: string, 1: string} */
    private function render(): array
    {
        $school = School::create([
            'name' => 'Snapshot Primary School',
            'email' => 'snapshot-primary@example.test',
            'phone' => '0709000001',
            'slug' => 'snapshot-primary',
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
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.5',
            'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'name' => 'Snapshot Teacher',
            'email' => 'snapshot-teacher@example.test',
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
        $english = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        $maths = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'Mathematics', 'code' => 'MATH', 'type' => 'core', 'status' => 1,
        ]);
        GradingHelper::seedGradingForSchool((int) $school->id, 'primary');

        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I',
            'status' => 'current', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30',
        ]);
        $learner = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => 'Amina Kato',
            'email' => 'amina.kato@example.test', 'status' => 'active', 'registration_number' => 'KLS0090001',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'user_id' => $learner->id, 'standardLink_id' => $link->id,
        ]);

        $rows = [
            [$english, 1, '2026-03-02 09:00:00', 62],
            [$maths, 1, '2026-03-03 09:00:00', 71],
            [$english, 2, '2026-04-20 09:00:00', 80],
            [$maths, 2, '2026-04-21 09:00:00', 70],
        ];
        foreach ($rows as [$subject, $typeId, $when, $score]) {
            $exam = Exam::withoutEvents(fn () => Exam::create([
                'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => $typeId,
                'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
                'scheduled_at' => $when, 'status' => 'submitted',
            ]));
            Marks::create([
                'student_id' => $learner->id, 'exam_id' => $exam->id, 'school_id' => $school->id,
                'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
                'marks' => $score, 'grade' => 'stored',
            ]);
        }

        $service = app(StudentReportCardService::class);
        $pdf = $service->pdfForStudent((int) $school->id, $link, $learner);
        $pdfText = trim((new Parser)->parseContent($pdf)->getText());

        $captured = null;
        $wrapper = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $wrapper->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $wrapper) {
            $captured = [$view, $data];

            return $wrapper;
        });
        $wrapper->shouldReceive('setPaper')->andReturnSelf();
        $wrapper->shouldReceive('output')->andReturn('%PDF');
        $this->app->instance('dompdf.wrapper', $wrapper);
        $service->pdfForStudent((int) $school->id, $link, $learner);
        $this->assertNotNull($captured);
        $html = view($captured[0], $captured[1])->render();
        $html = preg_replace("/\n{3,}/", "\n\n", $html) ?? $html;

        return [$html, $pdfText."\n"];
    }
}
