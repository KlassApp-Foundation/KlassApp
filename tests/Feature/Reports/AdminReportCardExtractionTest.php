<?php

namespace Tests\Feature\Reports;

use App\Http\Controllers\Admin\ReportCardsController;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
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
use App\Services\ReportCardCommentService;
use App\Services\StudentReportCardService;
use App\Services\StudentReportHelperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PR-A: admin report generation must be byte-identical through the
 * extracted StudentReportCardService vs the controller facade / routes.
 */
class AdminReportCardExtractionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $student;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Standard $standard;

    private Section $section;

    private StandardLink $stdLink;

    private Subject $subject;

    private Exam $eot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeSchoolAdmin::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Report Extract School',
            'email' => 'report-extract@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'slug' => 'report-extract-' . uniqid(),
            'status' => 1,
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
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
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => '1',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.2 Extract',
            'status' => 1,
        ]);

        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'email' => 'ct.extract@t.sch.ug',
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'email' => 'admin.extract@t.sch.ug',
        ]);

        $this->stdLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $teacher->id,
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

        $this->eot = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => 2,
            'academic_term_id' => $this->term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => '2026-04-01',
            'status' => 'submitted',
        ]));

        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Extract Student',
            'email' => 'student.extract@t.sch.ug',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $this->stdLink->id,
        ]);

        Marks::create([
            'student_id' => $this->student->id,
            'exam_id' => $this->eot->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $this->section->id,
            'marks' => 72,
            'grade' => 'D1',
        ]);
    }

    public function test_controller_generate_pdf_matches_service_byte_for_byte(): void
    {
        $service = app(StudentReportCardService::class);
        $helper = app(StudentReportHelperService::class);
        $svc = new ReportCardCommentService;
        $total = 1;
        $myPos = 1;

        $viaService = $service->generatePdf(
            $this->student->id,
            $this->eot,
            $this->stdLink,
            $this->school->id,
            $helper,
            $svc,
            $total,
            $myPos
        );

        $viaController = ReportCardsController::generatePdf(
            $this->student->id,
            $this->eot,
            $this->stdLink,
            $this->school->id,
            $helper,
            $svc,
            $total,
            $myPos
        );

        $this->assertNotSame('', $viaService);
        $this->assertStringStartsWith('%PDF', $viaService);
        $this->assertPdfPayloadEqual($viaService, $viaController);
    }

    public function test_pdf_for_student_matches_manual_generate_pdf_pipeline(): void
    {
        $service = app(StudentReportCardService::class);
        $helper = app(StudentReportHelperService::class);
        $svc = new ReportCardCommentService;

        $viaPdfForStudent = $service->pdfForStudent($this->school->id, $this->stdLink, $this->student);

        $exam = $service->resolveExam($this->school->id, $this->stdLink);
        $this->assertNotNull($exam);
        $studentIds = $service->studentIds($exam);
        $positionMap = $service->computePositionMap($exam, $this->school->id);
        $viaManual = $service->generatePdf(
            $this->student->id,
            $exam,
            $this->stdLink,
            $this->school->id,
            $helper,
            $svc,
            $studentIds->count(),
            $positionMap[$this->student->id] ?? 0
        );

        $this->assertPdfPayloadEqual($viaManual, $viaPdfForStudent);
    }

    public function test_admin_preview_and_download_routes_match_service_pdf(): void
    {
        $service = app(StudentReportCardService::class);
        $expected = $service->pdfForStudent($this->school->id, $this->stdLink, $this->student);

        $preview = $this->actingAs($this->admin)->get(route('admin.reports.cards.student.preview', [
            'stdLink' => $this->stdLink->id,
            'learner' => $this->student->id,
        ]));

        $preview->assertOk();
        $this->assertSame('application/pdf', $preview->headers->get('Content-Type'));
        $this->assertPdfPayloadEqual($expected, $preview->getContent());

        $download = $this->actingAs($this->admin)->get(route('admin.reports.cards.student.download', [
            'stdLink' => $this->stdLink->id,
            'learner' => $this->student->id,
        ]));

        $download->assertOk();
        $this->assertSame('application/pdf', $download->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $download->headers->get('Content-Disposition'));
        $this->assertPdfPayloadEqual($expected, $download->getContent());
    }

    public function test_templates_registry_aliased_from_service(): void
    {
        $this->assertSame(StudentReportCardService::TEMPLATES, ReportCardsController::TEMPLATES);
        $this->assertArrayHasKey('formal', ReportCardsController::TEMPLATES);
        $this->assertArrayHasKey('warm', ReportCardsController::TEMPLATES);
    }

    /**
     * DomPDF stamps CreationDate/ModDate and a random /ID on every render.
     * Strip those so we can assert the payload is otherwise identical.
     */
    private function assertPdfPayloadEqual(string $a, string $b): void
    {
        $this->assertSame($this->normalizePdfPayload($a), $this->normalizePdfPayload($b));
    }

    private function normalizePdfPayload(string $pdf): string
    {
        $pdf = preg_replace('/\/CreationDate \(D:[^)]+\)/', '/CreationDate (D:FIXED)', $pdf) ?? $pdf;
        $pdf = preg_replace('/\/ModDate \(D:[^)]+\)/', '/ModDate (D:FIXED)', $pdf) ?? $pdf;
        $pdf = preg_replace('/\/ID\[[^\]]+\]/', '/ID[<FIXED><FIXED>]', $pdf) ?? $pdf;

        return $pdf;
    }
}
