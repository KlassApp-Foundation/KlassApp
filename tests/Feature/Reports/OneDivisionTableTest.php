<?php

namespace Tests\Feature\Reports;

use App\Exports\CombinedMarksheetExport;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\ReportDivisionRange;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\MarksRelease\MarksReleaseService;
use App\Services\Parent\ParentPortalService;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use App\Services\Results\ThresholdDivisionRule;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * One division table. A school whose ranges are not the defaults shows
 * that same division on every screen that prints one.
 */
class OneDivisionTableTest extends TestCase
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

    public function test_every_screen_uses_the_schools_division_ranges(): void
    {
        $ctx = $this->school();
        $this->assertSame('1', (new ThresholdDivisionRule)->division(6, 1));

        $this->assertSame('Q', $this->sheetDivision($ctx));
        $this->assertSame('Q', $this->card($ctx)['eotDivision']);
        $this->assertSame('Q', $this->exportedDivision($ctx));
        $this->assertMatchesRegularExpression('/data-testid="student-report-division">\s*Q\s*</', $this->legacyReport($ctx));

        $admin = $this->actingAs($ctx['admin']);
        $sheet = $admin->get(route('admin.marks.filter', [
            'class' => $ctx['section']->id,
            'term' => $ctx['term']->id,
            'examType' => 2,
        ]))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-testid="marks-division">\s*Q\s*</', $sheet);
        $this->assertMatchesRegularExpression('/data-testid="marks-phone-division">\s*Division Q\s*</', $sheet);

        $classMarks = $admin->get(route('admin.marks.classes', $ctx['section']))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-testid="class-marks-division">\s*Division Q\s*</', $classMarks);

        $studentPage = $admin->get(route('admin.marks.student.class', [
            'user' => $ctx['learner']->id,
            'class' => $ctx['section']->id,
            'exam' => $ctx['exam']->id,
        ]))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-testid="student-result-division">\s*Q\s*</', $studentPage);

        $service = app(MarksReleaseService::class);
        $release = $service->create($ctx['admin'], (int) $ctx['exam']->id, (int) $ctx['link']->id, null, 'Africa/Kampala');
        $this->assertTrue($service->run((int) $release->id));

        ReportDivisionRange::query()->where('school_id', $ctx['school']->id)->update([
            'label' => 'Z',
            'min_aggregate' => null,
            'max_aggregate' => null,
        ]);
        SchoolGradingSystem::query()->where('school_id', $ctx['school']->id)->update(['points' => 9]);

        $this->assertSame('Q', $this->sheetDivision($ctx));
        $this->assertSame('Q', $this->card($ctx)['eotDivision']);
        $this->assertSame('Q', $this->exportedDivision($ctx));
        $this->assertMatchesRegularExpression('/data-testid="student-report-division">\s*Q\s*</', $this->legacyReport($ctx));

        $cards = $this->actingAs($ctx['admin'])->get(route('admin.reports.cards.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/class="rc-pill">\s*Q\s*</', $cards);

        $bundle = app(ParentPortalService::class)->bundle($ctx['learner'], (int) $ctx['school']->id);
        $this->assertSame('Q', $bundle['grades']['exam_groups'][0]['division'] ?? null);
        $parent = view('portal.partials.family-marks', [
            'bundle' => $bundle,
            'links' => ['report' => '/parent/report'],
        ])->render();
        $this->assertMatchesRegularExpression('/data-testid="family-division">\s*Division Q\s*</', $parent);

        $release->delete();
        $this->assertSame('Z', $this->sheetDivision($ctx));
        $this->assertSame('Z', $this->card($ctx)['eotDivision']);
    }

    /** @param array<string, mixed> $ctx */
    private function sheetDivision(array $ctx): ?string
    {
        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $ctx['school']->id,
            sectionId: (int) $ctx['section']->id,
            academicTermId: (int) $ctx['term']->id,
        ));

        $row = $sheet->student((int) $ctx['learner']->id);
        $this->assertNotNull($row);
        $this->assertSame(6, $row->aggregate);

        return $row->division;
    }

    /** @param array<string, mixed> $ctx */
    private function card(array $ctx): array
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
        app(StudentReportCardService::class)->pdfForStudent((int) $ctx['school']->id, $ctx['link'], $ctx['learner']);
        $this->assertNotNull($captured);

        return $captured;
    }

    /** @param array<string, mixed> $ctx */
    private function exportedDivision(array $ctx): string
    {
        $binary = Excel::raw(new CombinedMarksheetExport($ctx['link'], $ctx['term']), \Maatwebsite\Excel\Excel::XLSX);
        $tmp = tempnam(sys_get_temp_dir(), 'div').'.xlsx';
        file_put_contents($tmp, $binary);
        $sheet = IOFactory::load($tmp)->getSheetByName('END OF TERM');
        @unlink($tmp);
        $this->assertNotNull($sheet);
        $rows = $sheet->toArray();
        $this->assertSame('DIVISION', $rows[0][array_key_last($rows[0])]);

        return (string) $rows[1][array_key_last($rows[1])];
    }

    /** @param array<string, mixed> $ctx */
    private function legacyReport(array $ctx): string
    {
        $learner = User::with('school')->find($ctx['learner']->id);
        $learner->setRelation('marks', Marks::query()->where('student_id', $learner->id)->get());

        return view('admin.marks.student-report', [
            'learner' => $learner,
            'class_name' => 'P.5',
            'grade' => ['agg' => 6],
            'isNursery' => false,
            'midExams' => collect(),
            'eotExams' => collect([$ctx['exam']]),
            'subjects' => collect([$ctx['subject']]),
            'grading_system' => collect(),
            'stdLink' => $ctx['link'],
            'examinedSubjectCount' => 1,
            'total' => 50,
            'myPos' => 1,
            'totalLearners' => 1,
            'nextTerm' => null,
        ])->render();
    }

    /** @return array<string, mixed> */
    private function school(): array
    {
        $school = School::create([
            'name' => 'Division Primary', 'slug' => 'division-'.uniqid(), 'email' => uniqid().'@division.test',
            'phone' => '074'.random_int(1000000, 9999999), 'status' => 1, 'report_template' => 'formal',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'Current Academic Year',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        SchoolGradingSystem::create([
            'school_id' => $school->id, 'standard_id' => $standard->id,
            'grade' => '6', 'points' => 6, 'min_score' => 0, 'max_score' => 100, 'remark' => 'Band',
        ]);
        ReportDivisionRange::create([
            'school_id' => $school->id, 'standard_id' => $standard->id,
            'label' => 'Q', 'min_aggregate' => 6, 'max_aggregate' => 6, 'position' => 0,
        ]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.5', 'status' => 1]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5, 'school_id' => $school->id, 'email' => uniqid().'@division-teacher.test', 'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'usergroup_id' => 3, 'school_id' => $school->id, 'email' => uniqid().'@division-admin.test', 'status' => 'active',
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'class_teacher_id' => $teacher->id, 'stream' => 'A', 'status' => 1,
        ]);
        $term = AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term I',
            'status' => 'current', 'starts_on' => '2026-02-02', 'ends_on' => '2026-04-30',
        ]);
        $subject = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
            'section_id' => $section->id, 'name' => 'English', 'code' => 'ENG', 'type' => 'core', 'status' => 1,
        ]);
        $learner = User::factory()->create([
            'usergroup_id' => 6, 'school_id' => $school->id, 'name' => 'Queue Learner',
            'email' => uniqid().'@division-learner.test', 'status' => 'active', 'registration_number' => 'KLS0096201',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'user_id' => $learner->id, 'standardLink_id' => $link->id,
        ]);
        $exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id,
            'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'exam_type_id' => 2,
            'academic_term_id' => $term->id, 'academic_year_id' => $year->id,
            'scheduled_at' => '2026-04-20 09:00:00', 'status' => 'submitted',
        ]));
        Marks::create([
            'student_id' => $learner->id, 'exam_id' => $exam->id, 'school_id' => $school->id,
            'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'section_id' => $section->id,
            'marks' => 50, 'grade' => '',
        ]);
        ExamMarksSubmission::create([
            'school_id' => $school->id, 'exam_id' => $exam->id, 'class_id' => $section->id,
            'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'status' => 'locked',
            'approval_status' => 'approved', 'submitted_at' => now(), 'approved_at' => now(),
        ]);

        return compact('school', 'year', 'term', 'standard', 'section', 'link', 'teacher', 'admin', 'learner', 'exam', 'subject');
    }
}
