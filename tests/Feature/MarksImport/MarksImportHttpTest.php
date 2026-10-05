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
        return UploadedFile::fake()->createWithContent('marks.csv', implode("\n", array_merge(['KLS number,student_name,Mark (out of 100)'], $lines))."\n");
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

        $this->assertStringContainsString('Not a student in this class', $html);   // other class
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
        $this->actingAs($this->stranger)->get(route('teacher.exam.marks.template', $this->exam))->assertForbidden();
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

    /** @return list<list<string>> rows of a CSV template download (headings first) */
    private function csvRows(\Illuminate\Testing\TestResponse $response): array
    {
        $base = $response->baseResponse;
        $content = $base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            ? file_get_contents($base->getFile()->getPathname())
            : $response->getContent();

        return array_map('str_getcsv', array_values(array_filter(preg_split('/\r\n|\n/', trim($content)))));
    }

    public function test_the_986_teacher_template_feeds_the_import_unchanged(): void
    {
        // Download the template that already exists on main, fill the mark column, upload it.
        $download = $this->actingAs($this->owner)->get(route('teacher.exam.marks.template', [$this->exam, 'format' => 'csv']));
        $download->assertOk();
        $rows = $this->csvRows($download);
        $this->assertSame(['KLS number', 'student_name', 'Mark (out of 100)'], $rows[0]);

        $lines = [];
        foreach (array_slice($rows, 1) as $i => $r) {
            $lines[] = $r[0].','.$r[1].','.(60 + $i);
        }
        [$token, $html] = $this->preview('teacher.exam.marks.import', $this->owner, $lines);
        $this->assertStringContainsString('data-testid="count-new">3<', $html);
        $this->assertStringContainsString('data-testid="marks-import-warnings"', $html, 'csv templates cannot carry the Exam info sheet; the preview says so');

        $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token])->assertRedirect();
        $this->assertSame(3, Marks::count());
    }

    public function test_upload_page_links_to_the_986_template_for_teachers_and_the_admin_template_for_admins(): void
    {
        $this->actingAs($this->owner)->get(route('teacher.exam.marks.import.page', $this->exam))
            ->assertOk()->assertSee(route('teacher.exam.marks.template', $this->exam), false);
        $this->actingAs($this->admin)->get(route('admin.exams.marks.import.page', $this->exam))
            ->assertOk()->assertSee(route('admin.exams.marks.import.template', $this->exam), false);
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('teacher.exam.marks.import.template'), 'no second teacher template route');
    }

    public function test_admin_template_has_the_same_headings_and_roster_as_the_teacher_template(): void
    {
        $teacher = $this->csvRows($this->actingAs($this->owner)->get(route('teacher.exam.marks.template', [$this->exam, 'format' => 'csv'])));
        $adminResponse = $this->actingAs($this->admin)->get(route('admin.exams.marks.import.template', [$this->exam, 'format' => 'csv']));
        $adminResponse->assertOk();
        $this->assertStringContainsString('marks-template-exam-'.$this->exam->id.'.csv', $adminResponse->headers->get('content-disposition'));

        $admin = $this->csvRows($adminResponse);
        $this->assertSame($teacher[0], $admin[0]);
        $sort = function (array $rows) {
            $rows = array_slice($rows, 1);
            usort($rows, fn ($a, $b) => strcmp($a[0], $b[0]));

            return $rows;
        };
        $this->assertSame($sort($teacher), $sort($admin), 'one roster rule, whichever template route is used');
    }

    private function spreadsheet(\Illuminate\Testing\TestResponse $response): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $base = $response->baseResponse;
        $bytes = $base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            ? file_get_contents($base->getFile()->getPathname())
            : $response->streamedContent();
        $tmp = tempnam(sys_get_temp_dir(), 'marks-template-').'.xlsx';
        file_put_contents($tmp, $bytes);

        try {
            return \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_teacher_and_admin_xlsx_templates_share_the_two_sheet_shape(): void
    {
        $teacher = $this->spreadsheet($this->actingAs($this->owner)->get(route('teacher.exam.marks.template', $this->exam)));
        $admin = $this->spreadsheet($this->actingAs($this->admin)->get(route('admin.exams.marks.import.template', $this->exam)));

        foreach (['teacher' => $teacher, 'admin' => $admin] as $who => $ss) {
            $this->assertSame(['Marks', 'Exam info'], $ss->getSheetNames(), $who.' template sheet names');
            $marks = $ss->getSheetByName('Marks')->toArray(null, true, true, false);
            $this->assertSame(['KLS number', 'student_name', 'Mark (out of 100)'], $marks[0], $who.' headings');

            $info = [];
            foreach ($ss->getSheetByName('Exam info')->toArray() as $row) {
                if (($row[0] ?? '') !== '') { $info[$row[0]] = $row[1] ?? null; }
            }
            $this->assertSame('Grade 4', $info['Class and stream'], $who);
            $this->assertSame('MATHEMATICS', $info['Subject'], $who);
            $this->assertSame('100', (string) $info['Maximum marks'], $who);
            $this->assertSame('marks-import-v1', $info['Template version'], $who);
            $this->assertMatchesRegularExpression('/^'.$this->exam->id.'-[0-9a-f]{10}$/', (string) $info['Template key'], $who);
        }
    }


    public function test_teacher_result_page_links_to_the_marks_view(): void
    {
        [$token] = $this->preview('teacher.exam.marks.import', $this->owner, ['KLS0000001,,80']);
        $redirect = $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $token]);
        $redirect->assertRedirect();

        $result = $this->actingAs($this->owner)->get($redirect->headers->get('Location'));
        $result->assertOk()
            ->assertSee('data-testid="result-view-marks"', false)
            ->assertSee(route('teacher.exam.marks.view', $this->exam));
    }

    public function test_admin_result_page_links_to_the_marks_area(): void
    {
        [$token] = $this->preview('admin.exams.marks.import', $this->admin, ['KLS0000002,,70']);
        $redirect = $this->actingAs($this->admin)->post(route('admin.exams.marks.import.confirm', $this->exam), ['token' => $token]);
        $redirect->assertRedirect();

        $result = $this->actingAs($this->admin)->get($redirect->headers->get('Location'));
        $result->assertOk()
            ->assertSee('data-testid="result-view-marks"', false)
            ->assertSee(route('admin.marks.filter', ['class' => $this->exam->section_id, 'examType' => $this->exam->exam_type_id, 'term' => $this->exam->academic_term_id]));
    }

}
