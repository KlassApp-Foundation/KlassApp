<?php

namespace Tests\Feature\Results;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
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
use App\Models\Userprofile;
use App\Services\Results\ReportComposition;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A school names its own exam sets. Two of them count toward one combined
 * report. The others are shown on their own. A missing mark is 0 and is
 * labelled Missing. ABS is 0 and stays ABS.
 */
class SchoolExamSetReportTest extends TestCase
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
    }

    public function test_five_school_named_sets_follow_the_combined_report_rule(): void
    {
        $ctx = $this->school();
        $types = $this->fiveSets($ctx);
        $term = $ctx['term'];
        $ada = $this->student($ctx, 'Ada Lane');
        $bea = $this->student($ctx, 'Bea Harbour');

        $northEnglish = $this->exam($ctx, $types['north'], $ctx['english'], '2026-02-02');
        $northMaths = $this->exam($ctx, $types['north'], $ctx['maths'], '2026-02-03');
        $southEnglish = $this->exam($ctx, $types['south'], $ctx['english'], '2026-03-02');
        $southMaths = $this->exam($ctx, $types['south'], $ctx['maths'], '2026-03-03');
        $quizEnglish = $this->exam($ctx, $types['quiz'], $ctx['english'], '2026-03-16');
        $quizMaths = $this->exam($ctx, $types['quiz'], $ctx['maths'], '2026-03-17');
        $this->exam($ctx, $types['month'], $ctx['english'], '2026-04-06');
        $this->exam($ctx, $types['month'], $ctx['maths'], '2026-04-07');
        $lateEnglish = $this->exam($ctx, $types['late'], $ctx['english'], '2026-05-04');
        $lateMaths = $this->exam($ctx, $types['late'], $ctx['maths'], '2026-05-05');

        $this->mark($ctx, $northEnglish, $ctx['english'], $ada, 40, '');
        $this->mark($ctx, $northEnglish, $ctx['english'], $bea, 80, '');
        $this->mark($ctx, $northMaths, $ctx['maths'], $bea, 80, '');
        $this->mark($ctx, $southEnglish, $ctx['english'], $ada, 40, '');
        $this->mark($ctx, $southMaths, $ctx['maths'], $ada, 40, '');
        $this->mark($ctx, $southEnglish, $ctx['english'], $bea, 80, '');
        $this->mark($ctx, $southMaths, $ctx['maths'], $bea, 80, '');
        $this->absent($ctx, $quizEnglish, $ctx['english'], $ada);
        $this->mark($ctx, $quizMaths, $ctx['maths'], $ada, 70, '');
        $this->mark($ctx, $quizEnglish, $ctx['english'], $bea, 10, '');
        $this->mark($ctx, $quizMaths, $ctx['maths'], $bea, 10, '');
        $this->mark($ctx, $lateEnglish, $ctx['english'], $ada, 30, '');
        $this->mark($ctx, $lateMaths, $ctx['maths'], $ada, 30, '');
        $this->mark($ctx, $lateEnglish, $ctx['english'], $bea, 30, '');
        $this->mark($ctx, $lateMaths, $ctx['maths'], $bea, 30, '');

        $first = $this->actingAs($ctx['admin'])->get('/admin/settings/exam-types')->assertOk()->getContent();
        $this->assertSame(
            ['North Paper', 'South Paper', 'Quiz Hour', 'Month Check', 'Late Paper'],
            $this->namesInOrder($first, ['North Paper', 'South Paper', 'Quiz Hour', 'Month Check', 'Late Paper']),
        );
        $this->assertStringContainsString('Made up of: Late Paper 100%', $first);
        $this->assertStringContainsString('Last set only', $first);
        $this->assertStringContainsString('All sets equally', $first);
        $this->assertStringContainsString('Counts toward the combined report', $first);
        $this->assertStringContainsString('Shown on its own', $first);
        $this->refuseWorkarounds($first);

        $this->actingAs($ctx['admin'])
            ->post('/admin/settings/exam-types', [
                'mode' => [
                    $types['north']->id => 'count',
                    $types['south']->id => 'count',
                    $types['quiz']->id => 'alone',
                    $types['month']->id => 'alone',
                    $types['late']->id => 'alone',
                ],
                'weight' => [
                    $types['north']->id => '40',
                    $types['south']->id => '60',
                ],
            ])
            ->assertRedirect(route('admin.settings.exam-types'));

        $saved = $ctx['school']->fresh()->report_composition;
        $this->assertTrue($saved[(string) $types['north']->id]['include']);
        $this->assertEquals(40.0, $saved[(string) $types['north']->id]['weight']);
        $this->assertTrue($saved[(string) $types['south']->id]['include']);
        $this->assertEquals(60.0, $saved[(string) $types['south']->id]['weight']);
        foreach (['quiz', 'month', 'late'] as $key) {
            $this->assertFalse($saved[(string) $types[$key]->id]['include']);
            $this->assertNull($saved[(string) $types[$key]->id]['weight']);
        }
        $this->assertSame([['name' => 'Term report']], $saved['_reports']);

        $reportId = DB::table('combined_reports')->where('school_id', $ctx['school']->id)->value('id');
        $this->assertNotNull($reportId);
        $components = DB::table('combined_report_components')
            ->where('combined_report_id', $reportId)
            ->get()
            ->keyBy('exam_type_id');
        $this->assertSame('count', $components[$types['north']->id]->mode);
        $this->assertEquals(40.0, (float) $components[$types['north']->id]->weight);
        $this->assertSame('count', $components[$types['south']->id]->mode);
        $this->assertEquals(60.0, (float) $components[$types['south']->id]->weight);
        $this->assertSame('alone', $components[$types['quiz']->id]->mode);
        $this->assertNull($components[$types['quiz']->id]->weight);
        $this->assertSame('alone', $components[$types['month']->id]->mode);
        $this->assertSame('alone', $components[$types['late']->id]->mode);

        $again = $this->actingAs($ctx['admin'])->get('/admin/settings/exam-types')->assertOk()->getContent();
        $this->assertStringContainsString('Made up of: North Paper 40% · South Paper 60%', $again);
        $this->refuseWorkarounds($again);

        $blocked = app(ReportComposition::class)->blockingSets($ctx['school']->id, $ctx['section']->id, $term->id);
        $this->assertSame(['North Paper', 'South Paper'], array_column($blocked, 'name'));

        $this->release($ctx, $northEnglish, '2026-05-10 08:00:00');
        $this->release($ctx, $northMaths, '2026-05-10 08:05:00');
        $this->release($ctx, $lateEnglish, '2026-05-20 08:00:00');
        $this->release($ctx, $lateMaths, '2026-05-20 08:05:00');

        $blockedAfterRelease = app(ReportComposition::class)->blockingSets($ctx['school']->id, $ctx['section']->id, $term->id);
        $this->assertSame(['South Paper'], array_column($blockedAfterRelease, 'name'));

        $sheet = $this->sheet($ctx, $term);
        $adaRow = $sheet->student($ada->id);
        $beaRow = $sheet->student($bea->id);
        $this->assertSame(40.0, $adaRow->cell($ctx['english']->id)->mark);
        $this->assertSame(24.0, $adaRow->cell($ctx['maths']->id)->mark);
        $this->assertSame(64.0, $adaRow->total);
        $this->assertSame(2, $adaRow->subjectsCounted);
        $this->assertSame(160.0, $beaRow->total);
        $this->assertSame(1, $beaRow->classPosition);
        $this->assertSame(2, $adaRow->classPosition);

        $sittings = [];
        foreach ($sheet->sittings as $sitting) {
            $sittings[$sitting->name] = $sitting;
        }
        $this->assertSame('missing', $sittings['North Paper']->student($ada->id)->cell($ctx['maths']->id)->status);
        $this->assertSame('Missing', $sittings['North Paper']->student($ada->id)->cell($ctx['maths']->id)->sheetLabel());
        $this->assertSame('absent', $sittings['Quiz Hour']->student($ada->id)->cell($ctx['english']->id)->status);
        $this->assertSame('ABS', $sittings['Quiz Hour']->student($ada->id)->cell($ctx['english']->id)->sheetLabel());
        $this->assertSame(70.0, $sittings['Quiz Hour']->student($ada->id)->total);
        $this->assertSame(0.0, $sittings['Month Check']->student($ada->id)->total);

        $northPage = $this->actingAs($ctx['admin'])->get('/admin/marks/filter?'.http_build_query([
            'class' => $ctx['section']->id,
            'term' => $term->id,
            'examType' => $types['north']->id,
        ]))->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="marks-cell-missing"', $northPage);
        $this->assertStringContainsString('Missing', $northPage);
        $this->refuseWorkarounds($northPage);

        $quizPage = $this->actingAs($ctx['admin'])->get('/admin/marks/filter?'.http_build_query([
            'class' => $ctx['section']->id,
            'term' => $term->id,
            'examType' => $types['quiz']->id,
        ]))->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="marks-cell-absent"', $quizPage);
        $this->assertStringContainsString('>ABS<', $quizPage);
        $this->refuseWorkarounds($quizPage);

        $learnerPage = $this->actingAs($ctx['admin'])
            ->get('/admin/marks/student/'.$ada->id.'/class/'.$ctx['section']->id.'/'.$northEnglish->id)
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Missing', $learnerPage);
        $this->assertStringContainsString('ABS', $learnerPage);
        $this->assertMatchesRegularExpression('/data-testid="student-result-total">\s*64(?:\.0)?\s*</', $learnerPage);
        $this->assertMatchesRegularExpression('/data-testid="student-result-position">\s*2\s*</', $learnerPage);
        $this->refuseWorkarounds($learnerPage);

        $report = $this->renderReport($ctx, $ada);
        $this->assertStringContainsString('Missing', $report);
        $this->assertStringContainsString('ABS', $report);
        $this->assertStringContainsString('Made up of: North Paper 40% · South Paper 60%', $report);
        $this->assertStringContainsString('Exam sets shown on their own', $report);
        $this->assertStringContainsString('Combined report', $report);
        $this->refuseWorkarounds($report);

        $frozen = MarksRelease::query()
            ->where('exam_id', $northEnglish->id)
            ->firstOrFail();
        $ctx['school']->forceFill([
            'report_composition' => [
                (string) $types['late']->id => ['include' => true, 'weight' => 100.0],
                (string) $types['north']->id => ['include' => false, 'weight' => null],
                (string) $types['south']->id => ['include' => false, 'weight' => null],
                (string) $types['quiz']->id => ['include' => false, 'weight' => null],
                (string) $types['month']->id => ['include' => false, 'weight' => null],
            ],
        ])->save();

        $frozen->refresh();
        $this->assertEquals(40.0, $frozen->report_composition[(string) $types['north']->id]['weight']);
        $this->assertEquals(60.0, $frozen->report_composition[(string) $types['south']->id]['weight']);
        $kept = $this->sheet($ctx, $term);
        $this->assertSame(24.0, $kept->student($ada->id)->cell($ctx['maths']->id)->mark);
        $this->assertSame(64.0, $kept->student($ada->id)->total);
        $stillBlocked = app(ReportComposition::class)->blockingSets($ctx['school']->id, $ctx['section']->id, $term->id);
        $this->assertSame(['South Paper'], array_column($stillBlocked, 'name'));
    }

    public function test_a_counting_set_with_no_marks_does_not_block(): void
    {
        $ctx = $this->school();
        $empty = ExamType::create([
            'school_id' => $ctx['school']->id,
            'name' => 'Field Note',
            'code' => 'FN',
            'short_name' => 'Field',
            'contributes_to_report_total' => true,
        ]);
        $quiz = ExamType::create([
            'school_id' => $ctx['school']->id,
            'name' => 'Desk Quiz',
            'code' => 'DQ',
            'short_name' => 'Desk',
            'contributes_to_report_total' => false,
        ]);
        $this->exam($ctx, $empty, $ctx['english'], '2026-02-02');
        $desk = $this->exam($ctx, $quiz, $ctx['english'], '2026-03-02');
        $ada = $this->student($ctx, 'Ada Lane');
        $this->mark($ctx, $desk, $ctx['english'], $ada, 15, '');

        $ctx['school']->forceFill([
            'report_composition' => [
                (string) $empty->id => ['include' => true, 'weight' => 100.0],
                (string) $quiz->id => ['include' => false, 'weight' => null],
            ],
        ])->save();

        $blocked = app(ReportComposition::class)->blockingSets($ctx['school']->id, $ctx['section']->id, $ctx['term']->id);
        $this->assertSame([], $blocked);
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    private function namesInOrder(string $html, array $names): array
    {
        $found = [];
        foreach ($names as $name) {
            $at = strpos($html, $name);
            $this->assertNotFalse($at, $name);
            $found[$at] = $name;
        }
        ksort($found);

        return array_values($found);
    }

    private function refuseWorkarounds(string $html): void
    {
        $this->assertStringNotContainsString('incomplete', strtolower($html));
        $this->assertStringNotContainsString('Remind subject teachers', $html);
        $this->assertStringNotContainsString('re-weight', strtolower($html));
        $this->assertStringNotContainsString('workaround', strtolower($html));
    }

    private function renderReport(array $ctx, User $student): string
    {
        $captured = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = ['view' => $view, 'data' => $data];

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF-1.4 report');
        $this->app->instance('dompdf.wrapper', $pdf);

        app(StudentReportCardService::class)->pdfForStudent($ctx['school']->id, $ctx['link'], $student);
        $this->assertNotNull($captured);

        return view($captured['view'], $captured['data'])->render();
    }

    /**
     * @return array{school: School, year: AcademicYear, term: AcademicTerm, standard: Standard, section: Section, link: StandardLink, teacher: User, admin: User, english: Subject, maths: Subject}
     */
    private function school(): array
    {
        $school = School::create([
            'name' => 'Harbour Lane School',
            'email' => 'harbour-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'harbour-'.uniqid(),
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
        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
            'grading_style' => 'aggregate',
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.4',
            'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'email' => 'ct.'.uniqid().'@t.sch.ug',
        ]);
        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Harbour Admin',
            'email' => 'admin.'.uniqid().'@t.sch.ug',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Harbour',
            'lastname' => 'Admin',
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
        $english = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);
        $maths = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'type' => 'core',
            'status' => 1,
        ]);

        return compact('school', 'year', 'term', 'standard', 'section', 'link', 'teacher', 'admin', 'english', 'maths');
    }

    /**
     * @return array{north: ExamType, south: ExamType, quiz: ExamType, month: ExamType, late: ExamType}
     */
    private function fiveSets(array $ctx): array
    {
        $make = function (string $name, string $code, string $short, bool $counts) use ($ctx): ExamType {
            return ExamType::create([
                'school_id' => $ctx['school']->id,
                'name' => $name,
                'code' => $code,
                'short_name' => $short,
                'contributes_to_report_total' => $counts,
            ]);
        };

        return [
            'north' => $make('North Paper', 'NP', 'North', true),
            'south' => $make('South Paper', 'SP', 'South', true),
            'quiz' => $make('Quiz Hour', 'QH', 'Quiz', false),
            'month' => $make('Month Check', 'MC', 'Month', false),
            'late' => $make('Late Paper', 'LP', 'Late', false),
        ];
    }

    private function student(array $ctx, string $name): User
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

    private function exam(array $ctx, ExamType $type, Subject $subject, string $when): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'exam_type_id' => $type->id,
            'academic_term_id' => $ctx['term']->id,
            'academic_year_id' => $ctx['year']->id,
            'scheduled_at' => $when,
            'status' => 'submitted',
        ]));
    }

    private function mark(array $ctx, Exam $exam, Subject $subject, User $student, float $score, string $grade): void
    {
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $ctx['school']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'section_id' => $exam->section_id,
            'marks' => $score,
            'grade' => $grade,
        ]);
    }

    private function absent(array $ctx, Exam $exam, Subject $subject, User $student): void
    {
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $ctx['school']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'section_id' => $exam->section_id,
            'marks' => null,
            'grade' => 'AB',
            'absent' => true,
        ]);
    }

    private function release(array $ctx, Exam $exam, string $at): void
    {
        $release = MarksRelease::create([
            'school_id' => $ctx['school']->id,
            'exam_id' => $exam->id,
            'scope' => 'school',
            'scheduled_at' => $at,
            'released_at' => $at,
            'status' => MarksRelease::STATUS_RELEASED,
            'created_by' => $ctx['admin']->id,
        ]);
        app(ReportComposition::class)->freezeOn($release);
        $release->save();
    }

    private function sheet(array $ctx, AcademicTerm $term): \App\Services\Results\ResultSheet
    {
        return app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: $ctx['school']->id,
            sectionId: $ctx['section']->id,
            academicTermId: $term->id,
        ));
    }
}
