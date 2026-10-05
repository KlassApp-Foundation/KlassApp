<?php

namespace Tests\Feature\MarksImport;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\ActivityLog;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\MarksImportFixture;
use Tests\TestCase;

/** Routes and controllers for the marks import, for the teacher and the admin. */
class MarksImportHttpTest extends TestCase
{
    use MarksImportFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBeTeacher::class, MustBeSchoolAdmin::class, MustBePrivilege::class]);
        $this->buildMarksImportFixture();
    }

    private function csv(array $lines): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('marks.csv', implode("\n", array_merge(['Admission No,Student,Marks'], $lines))."\n");
    }

    /** @return array{0:string,1:string} the confirm token and the preview html */
    private function preview(string $prefix, User $actor, array $lines, $exam = null): array
    {
        $exam ??= $this->exam;
        $response = $this->actingAs($actor)->post(route($prefix.'.preview', $exam), ['file' => $this->csv($lines)]);
        $response->assertOk();
        preg_match('/name="token" value="([A-Za-z0-9]{40})"/', $response->getContent(), $m);

        return [$m[1] ?? '', $response->getContent()];
    }

    public function test_teacher_flow_preview_then_confirm_then_result(): void
    {
        $lines = ['KLS0000001,Amina Nakato,80', 'KLS0000002,Brian Okello,60'];

        [$token, $html] = $this->preview('teacher.exam.marks.import', $this->owner, $lines);
        $this->assertSame(0, Marks::count(), 'the preview saves nothing');
        $this->assertStringContainsString('Nothing has been saved yet', $html);
        $this->assertSame(40, strlen($token));

        $confirm = $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token]);
        $confirm->assertRedirect(route('teacher.exam.marks.import.result', [$this->exam, 'token' => $token]));
        $this->assertSame(2, Marks::count());

        $this->actingAs($this->owner)->get($confirm->headers->get('Location'))
            ->assertOk()->assertSee('data-testid="result-saved">2<', false);

        $this->assertSame(1, ActivityLog::where('description', 'marks.imported')->where('causer_id', $this->owner->id)->count());
    }

    public function test_confirming_twice_creates_no_duplicates(): void
    {
        [$token] = $this->preview('teacher.exam.marks.import', $this->owner, ['KLS0000001,,80']);

        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token])->assertRedirect();
        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token])->assertRedirect();

        $this->assertSame(1, Marks::count());
    }

    public function test_preview_reports_skipped_rows_with_reasons(): void
    {
        [, $html] = $this->preview('teacher.exam.marks.import', $this->owner, [
            'KLS0000001,,80', 'KLS0000004,,70', 'KLS0000099,,70', 'KLS0000002,,101', 'KLS0000003,,',
        ]);

        $this->assertStringContainsString('Not an active student in this class', $html);   // other class
        $this->assertStringContainsString('Student not found', $html);                     // unknown
        $this->assertStringContainsString('Mark is outside 0 to 100', $html);              // out of range
        $this->assertStringContainsString('No mark entered', $html);                       // blank
        $this->assertStringContainsString('data-testid="count-skipped">4<', $html);
        $this->assertStringContainsString('<td data-label="Marks">101</td>', $html, 'a rejected mark is shown as typed so the teacher can fix it');
    }

    public function test_overwrite_is_off_by_default_and_must_be_ticked(): void
    {
        $this->saveMark($this->students[1], 50);
        [$token, $html] = $this->preview('teacher.exam.marks.import', $this->owner, ['KLS0000001,,90']);
        $this->assertStringContainsString('name="overwrite"', $html);

        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token])->assertRedirect();
        $this->assertEquals(50, Marks::first()->marks);

        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token, 'overwrite' => 1])->assertRedirect();
        $this->assertEquals(90, Marks::first()->marks);
    }

    public function test_teacher_cannot_import_for_another_teachers_exam(): void
    {
        $this->actingAs($this->stranger)->get(route('teacher.exam.marks.import.page', $this->exam))->assertForbidden();
        $this->actingAs($this->stranger)->get(route('teacher.exam.marks.import.template', $this->exam))->assertForbidden();
        $this->actingAs($this->stranger)->post(route('teacher.exam.marks.import.preview', $this->exam), ['file' => $this->csv(['KLS0000001,,80'])])->assertForbidden();
        $this->actingAs($this->stranger)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => str_repeat('a', 40)])->assertForbidden();
        $this->assertSame(0, Marks::count());
    }

    public function test_a_preview_token_cannot_be_used_by_another_user_or_exam(): void
    {
        [$token] = $this->preview('teacher.exam.marks.import', $this->owner, ['KLS0000001,,80']);

        $this->actingAs($this->admin)->post(route('admin.exams.marks.import.confirm', $this->exam), ['token' => $token])
            ->assertRedirect(route('admin.exams.marks.import.page', $this->exam));
        $this->assertSame(0, Marks::count());
    }

    public function test_admin_can_import_for_any_class_in_their_school_but_not_another_school(): void
    {
        [$token] = $this->preview('admin.exams.marks.import', $this->admin, ['KLS0000003,,75']);
        $this->actingAs($this->admin)->post(route('admin.exams.marks.import.confirm', $this->exam), ['token' => $token])->assertRedirect();
        $this->assertSame(1, Marks::count());

        $foreign = User::factory()->create(['usergroup_id' => 3, 'school_id' => $this->otherSchoolStudent->school_id]);
        $this->actingAs($foreign)->get(route('admin.exams.marks.import.page', $this->exam))->assertForbidden();
    }

    public function test_locked_marks_cannot_be_imported(): void
    {
        // A preview taken before the lock must not be saveable after it.
        [$token] = $this->preview('teacher.exam.marks.import', $this->owner, ['KLS0000001,,80']);
        $lock = ExamMarksSubmission::create([
            'school_id' => $this->school->id, 'exam_id' => $this->exam->id, 'class_id' => $this->section->id,
            'subject_id' => $this->subject->id, 'teacher_id' => $this->owner->id, 'status' => 'locked',
        ]);

        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token])
            ->assertSessionHasErrors('import');
        $this->assertSame(0, Marks::count());

        // And a fresh preview of a locked exam shows the blocker and offers no confirm form.
        [, $html] = $this->preview('teacher.exam.marks.import', $this->owner, ['KLS0000001,,80']);
        $this->assertStringContainsString('data-testid="marks-import-blocked"', $html);
        $this->assertStringNotContainsString('data-testid="marks-import-confirm"', $html);
        $this->assertNotNull($lock);
    }

    public function test_submitted_exam_asks_for_a_reason(): void
    {
        $this->exam->update(['status' => 'submitted']);
        [$token, $html] = $this->preview('teacher.exam.marks.import', $this->owner, ['KLS0000001,,80']);
        $this->assertStringContainsString('name="correction_reason"', $html);

        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token])
            ->assertSessionHasErrors('correction_reason');
        $this->assertSame(0, Marks::count());

        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token, 'correction_reason' => 'Re-marked after moderation'])
            ->assertRedirect();
        $this->assertSame(1, Marks::count());
    }

    public function test_plan_limits_do_not_block_the_http_flow(): void
    {
        $plan = Plan::create(['cycle' => 30, 'name' => 'tiny', 'display_name' => 'Tiny', 'no_of_students' => 1, 'no_of_users' => 1, 'is_active' => 1, 'order' => 1, 'amount' => 0]);
        CurrentPlan::create(['school_id' => $this->school->id, 'plan_id' => $plan->id, 'status' => 'running']);

        [$token] = $this->preview('teacher.exam.marks.import', $this->owner, ['KLS0000001,,80', 'KLS0000002,,60', 'KLS0000003,,70']);
        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token])->assertRedirect();

        $this->assertSame(3, Marks::count());
    }

    public function test_bad_uploads_are_rejected_with_a_message(): void
    {
        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.preview', $this->exam), [])->assertSessionHasErrors('file');
        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.preview', $this->exam), ['file' => UploadedFile::fake()->create('marks.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('file');
        $this->assertSame(0, Marks::count());
    }

    public function test_template_downloads_for_the_chosen_exam(): void
    {
        $xlsx = $this->actingAs($this->owner)->get(route('teacher.exam.marks.import.template', $this->exam));
        $xlsx->assertOk();
        $this->assertStringContainsString('.xlsx', $xlsx->headers->get('content-disposition'));

        $csv = $this->actingAs($this->admin)->get(route('admin.exams.marks.import.template', [$this->exam, 'format' => 'csv']));
        $csv->assertOk();
        $this->assertStringContainsString('.csv', $csv->headers->get('content-disposition'));
    }
}
