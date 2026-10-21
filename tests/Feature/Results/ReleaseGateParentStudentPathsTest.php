<?php

namespace Tests\Feature\Results;

use App\Contracts\Results\ReleaseGate;
use App\Http\Controllers\Api\WhatsAppController;
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
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\Parent\ParentPortalService;
use App\Services\StudentReportCardService;
use App\Services\Toshi\AlumniActionService;
use App\Services\Toshi\ParentActionService;
use App\Services\Toshi\StudentActionService;
use App\Services\WhatsAppBusinessService;
use App\Services\WhatsAppReportCardDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Each parent/student marks path must hide a sitting until a marks_releases
 * row for that sitting is released. These tests fail on main, where the
 * same stored mark is shown with no release.
 */
class ReleaseGateParentStudentPathsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicTerm $term;

    private StandardLink $link;

    private Subject $subject;

    private Exam $exam;

    private User $student;

    private User $parent;

    private User $alumni;

    private string $parentPhone = '+256700111201';

    private string $studentPhone = '+256700111202';

    /** @var list<string> */
    private array $waBodies = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'name' => 'alumni', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Gate Primary',
            'email' => 'gate@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'gate-'.uniqid(),
            'status' => 1,
            'is_demo' => 0,
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term I',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.5',
            'status' => 1,
        ]);
        $teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'email' => 'gate.teacher@t.sch.ug',
        ]);
        $this->link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'status' => 1,
        ]);

        $examType = ExamType::create([
            'name' => 'End Of Term',
            'code' => 'EOT-GATE',
            'contributes_to_report_total' => 1,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Gatemark',
            'code' => 'GATE',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => $examType->id,
            'scheduled_at' => '2026-04-01',
            'status' => 'done',
        ]));

        $this->student = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'Gate Student',
            'email' => 'gate.student@t.sch.ug',
            'status' => 'active',
        ]);
        $this->profile($this->student, 6, 'Gate', 'Student');
        $this->enroll($this->student, $this->link);

        $this->parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'name' => 'Gate Parent',
            'email' => 'gate.parent@t.sch.ug',
            'status' => 'active',
        ]);
        $this->profile($this->parent, 7, 'Gate', 'Parent');
        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $this->parent->id,
            'student_id' => $this->student->id,
            'status' => 1,
        ]);

        $this->alumni = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 9,
            'name' => 'Gate Alumni',
            'email' => 'gate.alumni@t.sch.ug',
            'status' => 'active',
        ]);
        $this->profile($this->alumni, 9, 'Gate', 'Alumni');
        $this->enroll($this->alumni, $this->link);

        $this->mark($this->student, 91);
        $this->mark($this->alumni, 91);

        WhatsAppUser::create([
            'phone' => $this->parentPhone,
            'user_id' => $this->parent->id,
            'school_id' => $this->school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);
        WhatsAppUser::create([
            'phone' => $this->studentPhone,
            'user_id' => $this->student->id,
            'school_id' => $this->school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        Cache::flush();
    }

    public function test_L1_parent_portal_grades_hide_unreleased_marks(): void
    {
        $page = fn () => $this->actingAs($this->parent)->get(route('parent.children.grades', $this->student));

        $page()->assertOk()->assertDontSee('GATEMARK');

        $this->release('scheduled');
        $page()->assertOk()->assertDontSee('GATEMARK');

        $other = $this->otherLink();
        $this->release('released', 'class', $other->id);
        $page()->assertOk()->assertDontSee('GATEMARK');

        $this->release('released', 'class', $this->link->id);
        $page()->assertOk()->assertSee('GATEMARK');

        $service = app(ParentPortalService::class)->grades($this->parent, null, $this->student->id);
        $this->assertSame('GATEMARK', $service['data']['exam_groups'][0]['subjects'][0]['name']);
    }

    public function test_L2_parent_dashboard_panel_hides_unreleased_marks(): void
    {
        $page = fn () => $this->actingAs($this->parent)->get(route('parent.dashboard', ['child' => $this->student->id]));

        $page()->assertOk()->assertDontSee('GATEMARK');

        $this->release();
        $page()->assertOk()->assertSee('GATEMARK')->assertSee('parent-grades-panel');
    }

    public function test_L3_whatsapp_parent_grades_and_marks_hide_unreleased_marks(): void
    {
        $this->mockWhatsApp();
        $this->inbound($this->parentPhone, 'GRADES');
        $this->assertStringNotContainsString('GATEMARK', $this->waText());

        $this->mockWhatsApp();
        $this->inbound($this->parentPhone, 'MARKS');
        $this->assertStringNotContainsString('GATEMARK', $this->waText());

        $this->release();

        $this->mockWhatsApp();
        $this->inbound($this->parentPhone, 'GRADES');
        $this->assertStringContainsString('GATEMARK', $this->waText());

        $this->mockWhatsApp();
        $this->inbound($this->parentPhone, 'MARKS');
        $this->assertStringContainsString('GATEMARK', $this->waText());
    }

    public function test_L4_whatsapp_student_marks_hide_unreleased_marks(): void
    {
        $this->mockWhatsApp();
        $this->inbound($this->studentPhone, 'MARKS');
        $this->assertStringNotContainsString('GATEMARK', $this->waText());

        $this->release();

        $this->mockWhatsApp();
        $this->inbound($this->studentPhone, 'MARKS');
        $this->assertStringContainsString('GATEMARK', $this->waText());
    }

    public function test_L5_whatsapp_report_hides_unreleased_marks(): void
    {
        $before = app(WhatsAppReportCardDeliveryService::class)->prepareForStudent($this->parent, $this->student);
        $this->assertFalse($before['ok']);
        $this->assertSame('report_none', $before['flow_type']);

        $this->release();

        $after = app(WhatsAppReportCardDeliveryService::class)->prepareForStudent($this->parent, $this->student);
        $this->assertTrue($after['ok'] ?? false, json_encode($after));
    }

    public function test_L7_student_marks_page_hides_unreleased_marks(): void
    {
        $page = fn () => $this->actingAs($this->student)->get(route('student.marks'));

        $page()->assertOk()->assertDontSee('GATEMARK');

        $this->release();
        $page()->assertOk()->assertSee('GATEMARK');
    }

    public function test_L8_toshi_parent_grades_hide_unreleased_marks(): void
    {
        $hidden = ParentActionService::grades($this->parent);
        $this->assertStringNotContainsString('GATEMARK', $hidden['message']);

        $this->release();

        $shown = ParentActionService::grades($this->parent);
        $this->assertStringContainsString('GATEMARK', $shown['message']);
    }

    public function test_L8_toshi_student_view_marks_hides_unreleased_marks(): void
    {
        $hidden = StudentActionService::viewMarks($this->student);
        $this->assertStringNotContainsString('score 91', $hidden['message']);

        $this->release();

        $shown = StudentActionService::viewMarks($this->student);
        $this->assertStringContainsString('score 91', $shown['message']);
    }

    public function test_L8_toshi_student_dashboard_count_hides_unreleased_marks(): void
    {
        $hidden = StudentActionService::viewDashboard($this->student);
        $this->assertSame(0, $hidden['data']['marks']);

        $this->release();

        $shown = StudentActionService::viewDashboard($this->student);
        $this->assertSame(1, $shown['data']['marks']);
    }

    public function test_L9_alumni_marks_hide_unreleased_marks(): void
    {
        $records = AlumniActionService::examRecords($this->alumni);
        $this->assertStringNotContainsString('GATEMARK', $records['message']);

        $summary = AlumniActionService::academicSummary($this->alumni);
        $this->assertSame(0, $summary['data']['exam_records']);

        $this->actingAs($this->alumni)->get(route('alumni.marks'))->assertOk()->assertDontSee('GATEMARK');
        $this->actingAs($this->alumni)->get(route('alumni.dashboard'))->assertOk()->assertDontSee('GATEMARK');

        $this->release();

        $shown = AlumniActionService::examRecords($this->alumni);
        $this->assertStringContainsString('GATEMARK', $shown['message']);
        $this->assertSame(1, AlumniActionService::academicSummary($this->alumni)['data']['exam_records']);
        $this->actingAs($this->alumni)->get(route('alumni.marks'))->assertOk()->assertSee('GATEMARK');
    }

    public function test_L10_alumni_report_pdf_hides_unreleased_marks(): void
    {
        $snapshots = [];
        $this->bindPdf(function (string $view, array $data) use (&$snapshots) {
            $snapshots[] = $data['marks'];
        }, 'download');

        $this->actingAs($this->alumni)->get(route('alumni.report-card.download'))->assertOk();
        $this->assertNotEmpty($snapshots);
        $this->assertFalse(
            $snapshots[0]->contains(fn ($mark) => (float) $mark->marks === 91.0),
            'Unreleased mark was passed into the alumni report PDF.',
        );

        $this->release();

        $this->actingAs($this->alumni)->get(route('alumni.report-card.download'))->assertOk();
        $this->assertTrue($snapshots[1]->contains(fn ($mark) => (float) $mark->marks === 91.0));
    }

    public function test_past_enrollment_class_release_stays_visible_without_a_backfill(): void
    {
        $pastYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025',
            'description' => 'Past',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 0,
        ]);
        $pastTerm = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $pastYear->id,
            'name' => 'Term III',
            'starts_on' => '2025-09-01',
            'ends_on' => '2025-12-01',
            'status' => 'last',
        ]);
        $pastSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.4',
            'status' => 1,
        ]);
        $pastLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $pastYear->id,
            'standard_id' => $this->link->standard_id,
            'section_id' => $pastSection->id,
            'status' => 1,
        ]);
        $this->enroll($this->student, $pastLink);

        $pastSubject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $pastYear->id,
            'standard_id' => $this->link->standard_id,
            'section_id' => $pastSection->id,
            'name' => 'Oldmark',
            'type' => 'core',
            'status' => 1,
        ]);
        $pastExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->link->standard_id,
            'section_id' => $pastSection->id,
            'academic_year_id' => $pastYear->id,
            'academic_term_id' => $pastTerm->id,
            'subject_id' => $pastSubject->id,
            'teacher_id' => $this->exam->teacher_id,
            'exam_type_id' => $this->exam->exam_type_id,
            'status' => 'done',
        ]));
        Marks::create([
            'student_id' => $this->student->id,
            'subject_id' => $pastSubject->id,
            'section_id' => $pastSection->id,
            'exam_id' => $pastExam->id,
            'teacher_id' => $this->exam->teacher_id,
            'school_id' => $this->school->id,
            'marks' => 80,
            'grade' => 'C3',
        ]);
        $this->release('released', 'class', $pastLink->id, $pastExam->id);

        $marks = StudentActionService::viewMarks($this->student);
        $this->assertStringNotContainsString('score 91', $marks['message']);
        $this->assertStringContainsString('score 80', $marks['message']);
    }

    public function test_a_moved_student_does_not_see_the_new_class_unreleased_exam(): void
    {
        $newLink = $this->otherLink();
        $this->enroll($this->student, $newLink);

        $newSubject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->link->standard_id,
            'section_id' => $newLink->section_id,
            'name' => 'Newmark',
            'type' => 'core',
            'status' => 1,
        ]);
        $newExam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->link->standard_id,
            'section_id' => $newLink->section_id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $newSubject->id,
            'teacher_id' => $this->exam->teacher_id,
            'exam_type_id' => $this->exam->exam_type_id,
            'scheduled_at' => '2026-04-15',
            'status' => 'done',
        ]));
        Marks::create([
            'student_id' => $this->student->id,
            'subject_id' => $newSubject->id,
            'section_id' => $newLink->section_id,
            'exam_id' => $newExam->id,
            'teacher_id' => $this->exam->teacher_id,
            'school_id' => $this->school->id,
            'marks' => 70,
            'grade' => 'C3',
        ]);
        $this->release();

        $gate = app(ReleaseGate::class);

        $this->assertSame(
            $this->exam->id,
            $gate->latestReleasedExam($this->student, (int) $this->link->section_id)?->id,
        );
        $this->assertNull($gate->latestReleasedExam($this->student, (int) $newLink->section_id));
    }

    public function test_staff_report_exam_resolution_stays_ungated(): void
    {
        $resolved = app(StudentReportCardService::class)->resolveExam($this->school->id, $this->link);

        $this->assertNotNull($resolved);
        $this->assertSame($this->exam->id, $resolved->id);
    }

    private function profile(User $user, int $group, string $first, string $last): void
    {
        Userprofile::create([
            'user_id' => $user->id,
            'usergroup_id' => $group,
            'school_id' => $user->school_id,
            'firstname' => $first,
            'lastname' => $last,
            'status' => 'active',
        ]);
    }

    private function enroll(User $user, StandardLink $link): void
    {
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $user->id,
            'standardLink_id' => $link->id,
        ]);
    }

    private function mark(User $user, int $score): void
    {
        Marks::create([
            'student_id' => $user->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->link->section_id,
            'exam_id' => $this->exam->id,
            'teacher_id' => $this->exam->teacher_id,
            'school_id' => $this->school->id,
            'marks' => $score,
            'grade' => 'D1',
        ]);
    }

    private function otherLink(): StandardLink
    {
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.6',
            'status' => 1,
        ]);

        return StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->link->standard_id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
    }

    private function release(string $status = 'released', string $scope = 'class', ?int $linkId = null, ?int $examId = null): void
    {
        MarksRelease::create([
            'school_id' => $this->school->id,
            'exam_id' => $examId ?? $this->exam->id,
            'scope' => $scope,
            'standard_link_id' => $scope === 'class' ? ($linkId ?? $this->link->id) : null,
            'scheduled_at' => now()->subHour(),
            'released_at' => $status === MarksRelease::STATUS_RELEASED ? now() : null,
            'status' => $status,
            'timezone' => 'UTC',
        ]);
    }

    private function mockWhatsApp(): void
    {
        $this->waBodies = [];
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')->andReturnUsing(function ($phone, $message) {
            $this->waBodies[] = (string) $message;

            return ['success' => true, 'message_id' => 't'];
        });
        $whatsApp->shouldReceive('sendList')->andReturn(['success' => true, 'message_id' => 'l']);
        $whatsApp->shouldReceive('sendInteractiveButtons')->andReturn(['success' => true, 'message_id' => 'b']);
        $whatsApp->shouldReceive('sendDocument')->andReturn(['success' => true, 'message_id' => 'd']);
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);
    }

    private function inbound(string $phone, string $body): void
    {
        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'processMetaMessage');
        $method->invoke($controller, $phone, $body, 'm-'.uniqid(), 'Tester');
    }

    private function waText(): string
    {
        return implode("\n", $this->waBodies);
    }

    /**
     * The PDF facade builds a fresh wrapper on every call, so the test binds
     * that wrapper and records the view data instead of rendering a PDF.
     */
    private function bindPdf(\Closure $capture, string $terminal): void
    {
        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use ($capture, $pdf) {
            $capture($view, $data);

            return $pdf;
        });
        if ($terminal === 'save') {
            $pdf->shouldReceive('save')->andReturnUsing(fn () => $pdf);
        } else {
            $pdf->shouldReceive('download')->andReturn(response('pdf', 200));
        }
        $this->app->instance('dompdf.wrapper', $pdf);
    }
}
