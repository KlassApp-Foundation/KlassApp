<?php

namespace Tests\Feature\Reports;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Jobs\GenerateClassReportsJob;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\FeesCategories;
use App\Models\ReportGeneration;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportCardService;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Whole-class report card PDF.
 *
 * A class of StudentReportCardService::CLASS_PDF_DIRECT_MAX students or fewer
 * downloads in the request. A larger class is queued. Each page is the
 * single-student card, and fee lines stay on that student's own class and stream.
 */
class WholeClassReportCardPdfTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $classHead;

    private User $subjectTeacher;

    private User $otherUser;

    private AcademicYear $year;

    private Section $baseSection;

    private Section $streamSection;

    private Section $otherSection;

    private StandardLink $stream;

    private StandardLink $otherStream;

    private Subject $subject;

    private Exam $eot;

    private User $streamStudent;

    private User $otherStreamStudent;

    /** @var list<array{view: string, learner_id: int, fee_names: list<string>}> */
    private array $rendered = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBeTeacher::class,
            MustBeSchoolAdmin::class,
            MustBePrivilege::class,
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Class PDF School',
            'slug' => 'class-pdf-'.uniqid(),
            'email' => 'class-pdf-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'report_template' => 'warm',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->baseSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => 1,
        ]);
        $this->streamSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One A',
            'status' => 1,
        ]);
        $this->otherSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One B',
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);
        $this->classHead = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Class Head PDF',
            'status' => 'active',
        ]);
        $this->subjectTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Subject Teacher PDF',
            'status' => 'active',
        ]);
        $this->otherUser = User::factory()->create([
            'usergroup_id' => 7,
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);

        // Class head is recorded on the section, the signal ExamAuthorization reads.
        $this->streamSection->update(['class_teacher_id' => $this->classHead->id]);

        $this->link($standard, $this->baseSection, null);
        $this->stream = $this->link($standard, $this->streamSection, null);
        $this->otherStream = $this->link($standard, $this->otherSection, null);
        $this->otherSection->update(['class_teacher_id' => User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'status' => 'active',
        ])->id]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->streamSection->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->eot = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $this->streamSection->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'exam_type_id' => 2,
            'academic_term_id' => $term->id,
            'academic_year_id' => $this->year->id,
            'scheduled_at' => '2026-04-01',
            'status' => 'submitted',
        ]));

        $this->fee($standard, $this->baseSection, 'Primary One class fee', 100000);
        $this->fee($standard, $this->streamSection, 'Stream A fee', 5000);
        $this->fee($standard, $this->otherSection, 'Stream B fee', 7000);

        $this->streamStudent = $this->enrol($this->stream, 'Amina Stream', 70);
        $this->otherStreamStudent = $this->enrol($this->otherStream, 'Brian Stream', 61);

        Cache::forget('academic_year');
        Cache::forget('academic_year_for_school_'.$this->school->id);
    }

    public function test_admin_downloads_a_whole_class_pdf(): void
    {
        Queue::fake();
        $this->bindReportRenderer();

        $response = $this->actingAs($this->admin)->get(route('admin.reports.cards.merged', $this->stream));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        Queue::assertNothingPushed();
        $this->assertSame(0, ReportGeneration::query()->count());
    }

    public function test_class_head_downloads_their_own_class(): void
    {
        Queue::fake();
        $this->bindReportRenderer();

        $index = $this->actingAs($this->classHead)->get(route('teacher.reports.cards.index'));
        $index->assertOk();
        $index->assertSee('Primary One A');
        $index->assertDontSee('Primary One B');

        $show = $this->actingAs($this->classHead)->get(route('teacher.reports.cards.show', $this->stream));
        $show->assertOk();
        $show->assertSee('Download class PDF');

        $response = $this->actingAs($this->classHead)->get(route('teacher.reports.cards.class.pdf', $this->stream));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        Queue::assertNothingPushed();
    }

    public function test_class_head_is_forbidden_from_another_class(): void
    {
        $this->actingAs($this->classHead)
            ->get(route('teacher.reports.cards.class.pdf', $this->otherStream))
            ->assertForbidden();
    }

    public function test_subject_teacher_and_other_user_are_forbidden(): void
    {
        $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.reports.cards.class.pdf', $this->stream))
            ->assertForbidden();

        $this->actingAs($this->otherUser)
            ->get(route('teacher.reports.cards.class.pdf', $this->stream))
            ->assertForbidden();

        $this->actingAs($this->subjectTeacher)
            ->get(route('admin.reports.cards.merged', $this->stream))
            ->assertForbidden();
    }

    public function test_large_class_queues_a_job_and_the_job_writes_the_pdf(): void
    {
        $target = StudentReportCardService::CLASS_PDF_DIRECT_MAX + 1;
        $extra = $target - 2;
        for ($i = 0; $i < $extra; $i++) {
            $this->enrol($this->stream, sprintf('Extra Student %02d', $i), 50);
        }

        Queue::fake();

        $response = $this->actingAs($this->classHead)->get(route('teacher.reports.cards.class.pdf', $this->stream));

        $response->assertRedirect(route('teacher.reports.cards.show', $this->stream));
        $generation = ReportGeneration::query()->where('standard_link_id', $this->stream->id)->first();
        $this->assertNotNull($generation);
        $this->assertSame('pending', $generation->status);
        $this->assertSame('merged', $generation->mode);
        Queue::assertPushed(GenerateClassReportsJob::class, function (GenerateClassReportsJob $job) use ($generation) {
            return $job->generationId === $generation->id;
        });

        $this->actingAs($this->classHead)
            ->get(route('teacher.reports.cards.show', $this->stream))
            ->assertOk()
            ->assertSee('Preparing the class PDF');

        $this->bindReportRenderer();
        app(GenerateClassReportsJob::class, ['generationId' => $generation->id])->handle();

        $generation->refresh();
        $this->assertSame('completed', $generation->status, (string) $generation->error);
        $absolute = storage_path('app/'.$generation->file_path);
        $this->assertFileExists($absolute);
        $bytes = file_get_contents($absolute);
        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertSame($target, $this->pageCount($bytes));
        $this->assertCount($target, $this->rendered);

        $download = $this->actingAs($this->classHead)
            ->get(route('teacher.reports.cards.generation.download', $generation));
        $download->assertOk();
        $this->assertStringStartsWith('%PDF', file_get_contents($download->baseResponse->getFile()->getPathname()));

        $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.reports.cards.generation.download', $generation))
            ->assertForbidden();
    }

    public function test_the_pdf_has_one_report_card_per_student_and_keeps_each_students_fees(): void
    {
        $this->bindReportRenderer();

        $classPdf = $this->actingAs($this->admin)->get(route('admin.reports.cards.merged', $this->stream));
        $classPdf->assertOk();
        $this->assertSame(2, $this->pageCount($classPdf->getContent()));
        $this->assertCount(2, $this->rendered);

        $byLearner = collect($this->rendered)->keyBy('learner_id');
        $this->assertSame(
            ['admin.marks.report-templates.warm', 'admin.marks.report-templates.warm'],
            collect($this->rendered)->pluck('view')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['Primary One class fee', 'Stream A fee'],
            $byLearner[$this->streamStudent->id]['fee_names']
        );
        $this->assertEqualsCanonicalizing(
            ['Primary One class fee', 'Stream B fee'],
            $byLearner[$this->otherStreamStudent->id]['fee_names']
        );
        $this->assertNotContains('Stream B fee', $byLearner[$this->streamStudent->id]['fee_names']);
        $this->assertNotContains('Stream A fee', $byLearner[$this->otherStreamStudent->id]['fee_names']);

        $this->rendered = [];
        $single = $this->actingAs($this->classHead)->get(route('teacher.reports.cards.student.download', [
            'stdLink' => $this->stream->id,
            'learner' => $this->streamStudent->id,
        ]));
        $single->assertOk();
        $this->assertCount(1, $this->rendered);
        $this->assertSame('admin.marks.report-templates.warm', $this->rendered[0]['view']);
        $this->assertEqualsCanonicalizing(
            ['Primary One class fee', 'Stream A fee'],
            $this->rendered[0]['fee_names']
        );
    }

    public function test_another_schools_admin_cannot_download_the_class(): void
    {
        $other = School::create([
            'name' => 'Other PDF School',
            'slug' => 'other-pdf-'.uniqid(),
            'email' => 'other-pdf-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
        ]);
        $outsider = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $other->id,
            'status' => 'active',
        ]);

        $this->actingAs($outsider)
            ->get(route('admin.reports.cards.merged', $this->stream))
            ->assertNotFound();
    }

    private function bindReportRenderer(): void
    {
        $this->rendered = [];
        $tiny = $this->onePagePdf();
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use ($pdf) {
            $this->rendered[] = [
                'view' => $view,
                'learner_id' => (int) $data['learner']->id,
                'fee_names' => collect($data['fees'] ?? [])->pluck('name')->all(),
            ];

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn($tiny);
        $this->app->instance('dompdf.wrapper', $pdf);
    }

    private function onePagePdf(): string
    {
        $dompdf = new Dompdf;
        $dompdf->loadHtml('<html><body><p>Report card</p></body></html>');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function pageCount(string $pdf): int
    {
        preg_match_all('/\/Type\s*\/Page(?![s])/', $pdf, $pages);

        return count($pages[0]);
    }

    private function link(Standard $standard, Section $section, ?int $classTeacherId): StandardLink
    {
        return StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $classTeacherId,
            'status' => '1',
        ]);
    }

    private function fee(Standard $standard, Section $section, string $name, float $amount): void
    {
        FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => $name,
            'amount' => $amount,
        ]);
    }

    private function enrol(StandardLink $link, string $name, int $mark): User
    {
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => $name,
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $this->eot->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'section_id' => $this->streamSection->id,
            'marks' => $mark,
            'grade' => 'C',
        ]);

        return $student;
    }
}
