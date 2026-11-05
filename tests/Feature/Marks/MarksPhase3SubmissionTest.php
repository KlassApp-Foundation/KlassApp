<?php

namespace Tests\Feature\Marks;

use App\Exceptions\MarksLockedException;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\MarksSubmissionNotification;
use App\Services\Marks\ExamMarkWriter;
use App\Services\Marks\MarksSubmissionService;
use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MarksPhase3SubmissionTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $teacherA;

    private User $adminA;

    private User $studentA;

    private Exam $examA;

    private Section $sectionA;

    private Subject $subjectA;

    private Standard $standardA;

    private AcademicYear $yearA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0,
                'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create([
            'name' => 'School A', 'slug' => 'p3-a',
            'email' => 'a@p3.test', 'phone' => '+256700000021',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $this->schoolB = School::create([
            'name' => 'School B', 'slug' => 'p3-b',
            'email' => 'b@p3.test', 'phone' => '+256700000022',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        $this->yearA = AcademicYear::create([
            'school_id' => $this->schoolA->id, 'name' => '2026 Test',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        $termA = AcademicTerm::create([
            'school_id' => $this->schoolA->id, 'academic_year_id' => $this->yearA->id,
            'name' => 'Term 1', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);
        $this->standardA = Standard::create([
            'school_id' => $this->schoolA->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $this->sectionA = Section::create([
            'school_id' => $this->schoolA->id, 'name' => 'P.1', 'status' => 1,
        ]);

        $this->teacherA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 5,
            'email' => 'teacher.a@p3.test', 'name' => 'Teacher A', 'status' => 'active',
        ]);
        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 3,
            'email' => 'admin.a@p3.test', 'name' => 'Admin A', 'status' => 'active',
        ]);
        $this->studentA = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 6,
            'email' => 'student.a@p3.test', 'name' => 'Ada A', 'status' => 'active',
        ]);

        $this->subjectA = Subject::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'standard_id' => $this->standardA->id,
            'section_id' => $this->sectionA->id,
            'name' => 'Math A',
            'type' => 'core',
        ]);

        $this->examA = Exam::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'academic_term_id' => $termA->id,
            'standard_id' => $this->standardA->id,
            'section_id' => $this->sectionA->id,
            'subject_id' => $this->subjectA->id,
            'teacher_id' => $this->teacherA->id,
            'exam_type_id' => 1,
            'status' => 'undone',
        ]);
    }

    public function test_submit_refuses_an_empty_subject_and_then_closes_the_writer(): void
    {
        Mail::fake();

        $service = app(MarksSubmissionService::class);

        try {
            $service->submit($this->examA, $this->teacherA);
            $this->fail('an empty subject must not submit');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('submission', $e->errors());
        }
        $this->assertDatabaseCount('exam_marks_submissions', 0);

        $this->savedMark();
        $this->actingAs($this->teacherA)
            ->post(route('teacher.exam.marks.submit', $this->examA))
            ->assertRedirect(route('teacher.exam.marks.view', $this->examA));

        $row = ExamMarksSubmission::first();
        $this->assertSame('submitted', $row->status);
        $this->assertSame('pending', $row->approval_status);
        $this->assertSame('undone', $this->examA->fresh()->status);

        $log = ActivityLog::where('description', 'marks.submission.submitted')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->teacherA->id, (int) $log->causer_id);
        $this->assertSame((int) $this->schoolA->id, (int) $log->properties['school_id']);
        $this->assertNotEmpty($log->properties['at']);

        $notice = DB::table('notifications')->where('notifiable_id', $this->adminA->id)->first();
        $this->assertNotNull($notice);
        $this->assertSame(MarksSubmissionNotification::class, $notice->type);
        $this->assertStringContainsString('submitted', (string) $notice->data);
        $this->assertSame(['database'], (new MarksSubmissionNotification('submitted', 1, 'submitted'))->via($this->adminA));
        Mail::assertNothingSent();

        try {
            app(ExamMarkWriter::class)->save($this->teacherA, $this->examA, $this->studentA->id, 80, 'A long correction reason that must not unlock this.');
            $this->fail('submitted marks must stay closed');
        } catch (MarksLockedException $e) {
            $this->assertStringContainsString('return or reopen', $e->getMessage());
        }

        $this->actingAs($this->teacherA)
            ->from(route('teacher.exam.marks.enter', $this->examA))
            ->post(route('teacher.exam.marks.save', $this->examA), [
                'marks' => [$this->studentA->id => 80],
                'correction_reason' => 'A long correction reason that must not unlock this.',
            ])
            ->assertSessionHasErrors('marks_locked');

        $enter = $this->actingAs($this->teacherA)
            ->get(route('teacher.exam.marks.enter', $this->examA))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="marks-entry-state"', $enter);
        $this->assertStringContainsString('Submitted', $enter);
        $this->assertStringNotContainsString('data-testid="marks-submit-button"', $enter);

        $view = $this->actingAs($this->teacherA)
            ->get(route('teacher.exam.marks.view', $this->examA))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="marks-view-status"', $view);
        $this->assertStringContainsString('Submitted', $view);
        $this->assertStringNotContainsString('Unmarked work', $view);
    }

    public function test_approve_return_lock_and_reopen_follow_the_state_machine(): void
    {
        $this->savedMark();
        $service = app(MarksSubmissionService::class);
        $service->submit($this->examA, $this->teacherA);

        try {
            $service->approve($this->teacherA, $this->examA, $this->sectionA->id, $this->subjectA->id);
            $this->fail('a teacher cannot approve');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $adminB = User::factory()->create([
            'school_id' => $this->schoolB->id, 'usergroup_id' => 3,
            'email' => 'admin.b@p3.test', 'status' => 'active',
        ]);
        $this->actingAs($adminB)
            ->post(route('admin.marks.submissions.approve', $this->examA), [
                'class_id' => $this->sectionA->id,
                'subject_id' => $this->subjectA->id,
            ])
            ->assertForbidden();

        $this->actingAs($this->adminA)
            ->post(route('admin.marks.submissions.approve', $this->examA), [
                'class_id' => $this->sectionA->id,
                'subject_id' => $this->subjectA->id,
            ])
            ->assertRedirect();

        $approved = ExamMarksSubmission::first();
        $this->assertSame('approved', $approved->approval_status);
        $this->assertSame($this->adminA->id, (int) $approved->approved_by);
        $approvedLog = ActivityLog::where('description', 'marks.submission.approved')->first();
        $this->assertSame($this->adminA->id, (int) $approvedLog->causer_id);
        $this->assertNotEmpty($approvedLog->properties['at']);

        $teacherNotice = DB::table('notifications')->where('notifiable_id', $this->teacherA->id)->first();
        $this->assertNotNull($teacherNotice);
        $this->assertStringContainsString('approved', (string) $teacherNotice->data);

        try {
            $service->lock($this->adminA, $this->examA, $this->sectionA->id, $this->subjectA->id);
            $this->fail('an approved subject is not locked again');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('submission', $e->errors());
        }

        $this->actingAs($this->adminA)
            ->from(route('admin.marks.submissions.show', $this->examA))
            ->post(route('admin.marks.submissions.reject', $this->examA), [
                'class_id' => $this->sectionA->id,
                'subject_id' => $this->subjectA->id,
                'return_reason' => 'short',
            ])
            ->assertSessionHasErrors('return_reason');

        $reason = 'Please recheck the arithmetic.';
        $this->actingAs($this->adminA)
            ->post(route('admin.marks.submissions.reject', $this->examA), [
                'class_id' => $this->sectionA->id,
                'subject_id' => $this->subjectA->id,
                'return_reason' => $reason,
            ])
            ->assertRedirect();

        $returned = ExamMarksSubmission::first();
        $this->assertTrue($returned->isReturned());
        $this->assertSame('Returned', $returned->reviewLabel());
        $this->assertSame($reason, $returned->rejection_reason);
        $this->assertSame($this->adminA->id, (int) $returned->rejected_by);

        $entry = $this->actingAs($this->teacherA)
            ->get(route('teacher.exam.marks.enter', $this->examA))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="marks-return-reason"', $entry);
        $this->assertStringContainsString($reason, $entry);
        $this->assertStringContainsString('data-testid="marks-submit-button"', $entry);

        $classTeacher = User::factory()->create([
            'school_id' => $this->schoolA->id, 'usergroup_id' => 5,
            'email' => 'class.a@p3.test', 'name' => 'Class Teacher', 'status' => 'active',
        ]);
        StandardLink::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'standard_id' => $this->standardA->id,
            'section_id' => $this->sectionA->id,
            'class_teacher_id' => $classTeacher->id,
            'status' => 1,
        ]);
        $hidden = $this->actingAs($classTeacher)
            ->get(route('teacher.exam.marks.enter', $this->examA))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString($reason, $hidden);
        $this->assertStringNotContainsString('data-testid="marks-return-reason"', $hidden);

        app(ExamMarkWriter::class)->save($this->teacherA, $this->examA, $this->studentA->id, 81);
        $this->assertSame(81, (int) Marks::where('student_id', $this->studentA->id)->value('marks'));

        $service->submit($this->examA, $this->teacherA);
        $service->lock($this->adminA, $this->examA, $this->sectionA->id, $this->subjectA->id);
        $this->assertTrue(ExamMarksSubmission::first()->isLocked());

        try {
            app(ExamMarkWriter::class)->save($this->teacherA, $this->examA, $this->studentA->id, 82);
            $this->fail('locked marks must stay closed');
        } catch (MarksLockedException) {
        }

        $reopenReason = 'The class teacher asked for a recount.';
        $this->actingAs($this->adminA)
            ->post(route('admin.marks.submissions.reopen', $this->examA), [
                'class_id' => $this->sectionA->id,
                'subject_id' => $this->subjectA->id,
                'reopen_reason' => $reopenReason,
            ])
            ->assertRedirect();

        $reopened = ExamMarksSubmission::first();
        $this->assertSame('reopened', $reopened->status);
        $this->assertSame('pending', $reopened->approval_status);
        $this->assertSame($this->adminA->id, (int) $reopened->reopened_by);
        $this->assertSame($reopenReason, $reopened->reopen_reason);
        $reopenLog = ActivityLog::where('description', 'marks.submission.reopened')->first();
        $this->assertSame($this->adminA->id, (int) $reopenLog->causer_id);
        $this->assertSame($reopenReason, $reopenLog->properties['reason']);

        app(ExamMarkWriter::class)->save($this->teacherA, $this->examA, $this->studentA->id, 83);
        $this->assertSame(83, (int) Marks::where('student_id', $this->studentA->id)->value('marks'));
    }

    public function test_review_page_posts_each_action_on_its_own_and_names_the_class(): void
    {
        $this->savedMark();
        app(MarksSubmissionService::class)->submit($this->examA, $this->teacherA);

        $html = $this->actingAs($this->adminA)
            ->get(route('admin.marks.submissions.show', $this->examA))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Class: P.1', $html);
        $this->assertStringNotContainsString('Class: primary', $html);
        $this->assertStringContainsString('data-testid="marks-approve-form"', $html);
        $this->assertStringContainsString('data-testid="marks-lock-form"', $html);
        $this->assertStringContainsString('data-testid="marks-return-form"', $html);
        $this->assertStringContainsString('name="return_reason"', $html);
        $this->assertMatchesRegularExpression('/>\s*Return\s*</', $html);
        $this->assertMatchesRegularExpression('/>\s*Confirm Return\s*</', $html);
        $this->assertDoesNotMatchRegularExpression('/>\s*Reject\s*</', $html);
        $this->assertStringNotContainsString('id="bulk-approve-form"', $html);
        $this->assertFormsAreNotNested($html);

        app(MarksSubmissionService::class)->approve(
            $this->adminA,
            $this->examA,
            $this->sectionA->id,
            $this->subjectA->id,
        );

        $approved = $this->actingAs($this->adminA)
            ->get(route('admin.marks.submissions.show', $this->examA))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('data-testid="marks-lock-form"', $approved);
        $this->assertStringNotContainsString('data-testid="marks-approve-form"', $approved);
        $this->assertStringContainsString('data-testid="marks-reopen-form"', $approved);
        $this->assertFormsAreNotNested($approved);
    }

    public function test_queue_and_exam_lists_read_the_submission_row(): void
    {
        $this->savedMark();
        app(MarksSubmissionService::class)->submit($this->examA, $this->teacherA);

        $ghostSubject = Subject::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'standard_id' => $this->standardA->id,
            'section_id' => $this->sectionA->id,
            'name' => 'Ghost Science',
            'type' => 'core',
        ]);
        Exam::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'academic_term_id' => $this->examA->academic_term_id,
            'standard_id' => $this->standardA->id,
            'section_id' => $this->sectionA->id,
            'subject_id' => $ghostSubject->id,
            'teacher_id' => $this->teacherA->id,
            'exam_type_id' => 1,
            'status' => 'submitted',
        ]);

        $this->actingAs($this->adminA)
            ->get(route('admin.marks.submissions'))
            ->assertOk()
            ->assertSee('MATH A')
            ->assertDontSee('GHOST SCIENCE');

        $adminList = $this->actingAs($this->adminA)
            ->get(route('admin.exams'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="exam-submission-state"', $adminList);
        $this->assertStringContainsString('Submitted', $adminList);
        $this->assertStringContainsString('Not started', $adminList);

        $teacherList = $this->actingAs($this->teacherA)
            ->get(route('teacher.exam.marks'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="exam-submission-state"', $teacherList);
        $this->assertStringContainsString('Submitted', $teacherList);
        $this->assertStringContainsString('Not started', $teacherList);
    }

    public function test_release_reads_the_approved_state_and_keeps_the_admin_override(): void
    {
        $this->savedMark();
        StandardLink::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'standard_id' => $this->standardA->id,
            'section_id' => $this->sectionA->id,
            'class_teacher_id' => $this->teacherA->id,
            'status' => 1,
        ]);
        app(MarksSubmissionService::class)->submit($this->examA, $this->teacherA);

        $release = app(MarksReleaseService::class);
        try {
            $release->requireApproval($this->adminA, $this->examA, null, null);
            $this->fail('a pending subject must block release');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approval', $e->errors());
        }

        $override = 'Parents were promised these marks today.';
        $this->assertSame($override, $release->requireApproval($this->adminA, $this->examA, null, $override));

        app(MarksSubmissionService::class)->approve(
            $this->adminA,
            $this->examA,
            $this->sectionA->id,
            $this->subjectA->id,
        );

        $this->assertNull($release->requireApproval($this->adminA, $this->examA, null, null));
    }

    public function test_approvals_inbox_points_at_the_marks_queue(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('admin.approvals.inbox'))
            ->assertOk()
            ->assertSee(route('admin.marks.submissions'), false)
            ->assertDontSee('marks requests will appear');
    }

    private function savedMark(): void
    {
        Marks::create([
            'student_id' => $this->studentA->id,
            'subject_id' => $this->subjectA->id,
            'section_id' => $this->sectionA->id,
            'exam_id' => $this->examA->id,
            'teacher_id' => $this->teacherA->id,
            'school_id' => $this->schoolA->id,
            'marks' => 70,
            'grade' => 'C',
        ]);
    }

    private function assertFormsAreNotNested(string $html): void
    {
        preg_match_all('/<\/?form\b[^>]*>/i', $html, $tags);
        $depth = 0;
        foreach ($tags[0] as $tag) {
            if (preg_match('/^<\//', $tag) === 1) {
                $depth--;
                $this->assertGreaterThanOrEqual(0, $depth);
            } else {
                $this->assertSame(0, $depth, 'A form is nested inside another form.');
                $depth++;
            }
        }
        $this->assertSame(0, $depth);
    }
}
