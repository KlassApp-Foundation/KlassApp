<?php

namespace Tests\Feature\Results;

use App\AiAgents\Tools\Student\ViewMarksTool;
use App\Exports\CombinedMarksheetExport;
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
use App\Services\OutboundWhatsAppService;
use App\Services\Results\ResultsPolicy;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

/**
 * A derived mark is a whole number on every parent-facing channel.
 * The stored row keeps the decimals the teacher entered.
 */
class WholeNumberChannelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBePrivilege::class,
            MustBeSchoolAdmin::class,
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'school admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_the_report_card_pdf_text_has_no_decimal(): void
    {
        $ctx = $this->school();
        $learner = $this->learner($ctx, 'Amina Kato');
        $this->sitting($ctx, $ctx['english'], 53.5);
        $this->sitting($ctx, $ctx['maths'], null);

        $pdf = app(StudentReportCardService::class)->pdfForStudent($ctx['school']->id, $ctx['link'], $learner);
        $text = (new Parser)->parseContent($pdf)->getText();

        $this->assertStringContainsString('54', $text);
        $this->assertStringContainsString('–', $text);
        $this->assertDoesNotMatchRegularExpression('/\d+\.\d+/', $text);
        $this->assertEquals(53.5, Marks::query()->where('student_id', $learner->id)->value('marks'));
    }

    public function test_the_marks_grid_html_has_no_decimal(): void
    {
        $ctx = $this->school();
        $this->learner($ctx, 'Amina Kato');
        $examEng = $this->sitting($ctx, $ctx['english'], 53.5);
        $examMaths = $this->sitting($ctx, $ctx['maths'], null);
        foreach ([$examEng, $examMaths] as $exam) {
            ExamMarksSubmission::create([
                'school_id' => $ctx['school']->id, 'exam_id' => $exam->id, 'class_id' => $ctx['section']->id,
                'subject_id' => $exam->subject_id, 'teacher_id' => $ctx['teacher']->id, 'status' => 'locked',
                'approval_status' => 'approved', 'submitted_at' => now(), 'approved_at' => now(),
            ]);
        }
        $exam = $examEng;

        $html = $this->actingAs($ctx['admin'])->get('/admin/marks/filter?'.http_build_query([
            'class' => $ctx['section']->id,
            'term' => $ctx['term']->id,
            'examType' => $exam->exam_type_id,
        ]))->assertOk()->getContent();

        preg_match_all('/data-testid="marks-cell"[^>]*>(.*?)<\/td>/s', $html, $cells);
        preg_match('/data-testid="marks-total"[^>]*>(.*?)<\/td>/s', $html, $total);
        preg_match('/data-testid="marks-average"[^>]*>(.*?)<\/td>/s', $html, $average);
        $cellText = trim(preg_replace('/\s+/', ' ', strip_tags(implode(' ', $cells[1] ?? []))) ?? '');
        $totalText = trim(strip_tags($total[1] ?? ''));
        $averageText = trim(strip_tags($average[1] ?? ''));
        $printed = $cellText.' '.$totalText.' '.$averageText;

        $this->assertStringContainsString('54', $cellText);
        $this->assertStringContainsString('–', $cellText);
        $this->assertSame((string) $this->sumOfPrintedNumbers($cellText), $totalText);
        $this->assertSame('27', $averageText);
        $this->assertDoesNotMatchRegularExpression('/\d+\.\d+/', $printed);
    }

    public function test_the_export_file_has_no_decimal(): void
    {
        $ctx = $this->school();
        $this->learner($ctx, 'Amina Kato');
        $this->sitting($ctx, $ctx['english'], 53.5);
        $this->sitting($ctx, $ctx['maths'], null);

        $binary = Excel::raw(new CombinedMarksheetExport($ctx['link'], $ctx['term']), \Maatwebsite\Excel\Excel::XLSX);
        $tmp = tempnam(sys_get_temp_dir(), 'whole').'.xlsx';
        file_put_contents($tmp, $binary);
        $sheet = IOFactory::load($tmp)->getSheet(0);
        @unlink($tmp);

        $values = [];
        foreach ($sheet->toArray() as $row) {
            foreach ($row as $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $values[] = (string) $value;
            }
        }
        $printed = implode(' ', $values);

        $this->assertContains('54', $values);
        $this->assertDoesNotMatchRegularExpression('/\d+\.\d+/', $printed);
    }

    public function test_the_whatsapp_report_text_has_no_decimal(): void
    {
        $student = new User(['name' => 'Amina Kato']);
        $student->setRelation('studentAcademic', null);
        $text = app(OutboundWhatsAppService::class)->composeGradesOverview($student, 'End of term', [
            ['name' => 'English', 'score' => 53.4, 'total' => 100, 'grade' => 'C'],
            ['name' => 'Mathematics', 'score' => 53.6, 'total' => 100, 'grade' => 'C'],
        ]);

        $this->assertStringContainsString('English: 53/100', $text);
        $this->assertStringContainsString('Mathematics: 54/100', $text);
        $this->assertStringContainsString('Average: 54%', $text);
        $this->assertDoesNotMatchRegularExpression('/\d+\.\d+/', $text);
        $this->assertSame(54.0, ResultsPolicy::roundTo((53.0 + 54.0) / 2));
    }

    public function test_the_toshi_tool_output_has_no_decimal(): void
    {
        $ctx = $this->school();
        $learner = $this->learner($ctx, 'Amina Kato');
        $exam = $this->sitting($ctx, $ctx['english'], 53.5);
        MarksRelease::create([
            'school_id' => $ctx['school']->id,
            'exam_id' => $exam->id,
            'scope' => 'class',
            'standard_link_id' => $ctx['link']->id,
            'scheduled_at' => now(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'timezone' => 'UTC',
        ]);

        $this->actingAs($learner);
        $text = (string) (new ViewMarksTool)->handle(new Request([]));

        $this->assertStringContainsString('54', $text);
        $this->assertDoesNotMatchRegularExpression('/\d+\.\d+/', $text);
        $this->assertEquals(53.5, Marks::query()->where('student_id', $learner->id)->value('marks'));
    }

    public function test_the_webhook_json_has_no_decimal(): void
    {
        $ctx = $this->school();
        $ctx['school']->forceFill(['is_demo' => 1])->save();
        $learner = $this->learner($ctx, 'Amina Kato');
        $exam = $this->sitting($ctx, $ctx['english'], 53.5);
        $release = MarksRelease::create([
            'school_id' => $ctx['school']->id,
            'exam_id' => $exam->id,
            'scope' => 'class',
            'standard_link_id' => $ctx['link']->id,
            'scheduled_at' => now(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'timezone' => 'UTC',
        ]);

        config([
            'klassapp.demo.release_webhook_enabled' => true,
            'klassapp.demo.release_webhook_url' => 'https://hooks.example.test/marks',
            'klassapp.demo.release_webhook_secret' => 'test-only-webhook-secret',
        ]);
        Http::fake(['hooks.example.test/*' => Http::response(['ok' => true], 200)]);

        $status = app(\App\Services\MarksRelease\MarksReleaseService::class)->sendDemoWebhook($release->id, 1, 1);

        $this->assertSame('sent', $status);
        $body = Http::recorded()[0][0]->body();
        $payload = json_decode($body, true);
        $this->assertSame(['event', 'release_id', 'exam', 'learners', 'classes'], array_keys($payload));
        $this->assertSame('marks.released', $payload['event']);
        $this->assertIsInt($payload['learners']);
        $this->assertIsInt($payload['classes']);
        $this->assertDoesNotMatchRegularExpression('/\d+\.\d+/', $body);
        $this->assertEquals(53.5, Marks::query()->where('student_id', $learner->id)->value('marks'));
    }

    /**
     * @return array{school: School, year: AcademicYear, term: AcademicTerm, standard: Standard, section: Section, link: StandardLink, teacher: User, admin: User, english: Subject, maths: Subject}
     */
    private function school(): array
    {
        $school = School::create([
            'name' => 'Whole Number School',
            'email' => 'whole-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'whole-'.uniqid(),
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
        $section = Section::create([
            'school_id' => $school->id, 'name' => 'P.4', 'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => 'ct.'.uniqid().'@t.sch.ug', 'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'usergroup_id' => 3, 'school_id' => $school->id, 'email' => 'admin.'.uniqid().'@t.sch.ug', 'status' => 'active',
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
        $english = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        $maths = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'Mathematics', 'code' => 'MATH', 'type' => 'core', 'status' => 1,
        ]);

        return compact('school', 'year', 'term', 'standard', 'section', 'link', 'teacher', 'admin', 'english', 'maths');
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

    private function sitting(array $ctx, Subject $subject, ?float $score): Exam
    {
        $exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'exam_type_id' => 2,
            'academic_term_id' => $ctx['term']->id,
            'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => '2026-04-20 09:00:00',
            'status' => 'submitted',
        ]));
        if ($score !== null) {
            Marks::create([
                'student_id' => User::query()->where('school_id', $ctx['school']->id)->where('usergroup_id', 6)->value('id'),
                'exam_id' => $exam->id,
                'school_id' => $ctx['school']->id,
                'subject_id' => $subject->id,
                'teacher_id' => $ctx['teacher']->id,
                'section_id' => $ctx['section']->id,
                'marks' => $score,
                'grade' => '',
            ]);
        }

        return $exam;
    }

    private function sumOfPrintedNumbers(string $printed): int
    {
        preg_match_all('/\d+/', $printed, $numbers);

        return array_sum(array_map(intval(...), $numbers[0]));
    }
}
