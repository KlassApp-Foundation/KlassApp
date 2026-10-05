<?php

namespace Tests\Feature\MarksImport;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\MarksImportFixture;
use Tests\TestCase;

/** Entry points and screens of the marks import: where the button is, and what the pages must contain. */
class MarksImportViewsTest extends TestCase
{
    use MarksImportFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBeTeacher::class, MustBeSchoolAdmin::class, MustBePrivilege::class]);
        $this->buildMarksImportFixture();
    }

    public function test_teacher_enter_marks_page_has_the_import_button(): void
    {
        $this->actingAs($this->owner)->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->assertSee('data-testid="marks-import-button"', false)
            ->assertSee(route('teacher.exam.marks.import.page', $this->exam), false)
            ->assertSee('Import from spreadsheet');
    }

    public function test_teacher_exam_list_has_the_import_button(): void
    {
        $this->actingAs($this->owner)->get(route('teacher.exam.marks'))
            ->assertOk()
            ->assertSee(route('teacher.exam.marks.import.page', $this->exam), false);
    }

    public function test_admin_exam_list_has_the_import_button(): void
    {
        $this->actingAs($this->admin)->get(route('admin.exams'))
            ->assertOk()
            ->assertSee(route('admin.exams.marks.import.page', $this->exam), false)
            ->assertSee('Import from spreadsheet');
    }

    public function test_upload_page_is_accessible_and_explains_the_rules(): void
    {
        $html = $this->actingAs($this->owner)->get(route('teacher.exam.marks.import.page', $this->exam))->assertOk()->getContent();

        $this->assertStringContainsString('<label for="marks-file"', $html);
        $this->assertStringContainsString('id="marks-file"', $html);
        $this->assertStringContainsString('aria-describedby="marks-file-help"', $html);
        $this->assertStringContainsString('KLS number', $html);
        $this->assertStringContainsString('Download template (.xlsx)', $html);
        $this->assertStringContainsString('Uploading the same file again changes nothing', $html);
        $this->assertStringContainsStringIgnoringCase('Mathematics', $html);
        $this->assertStringContainsStringIgnoringCase('Grade 4', $html);
    }

    public function test_preview_and_result_pages_use_announced_alerts_and_stacking_tables(): void
    {
        $file = UploadedFile::fake()->createWithContent('m.csv', "KLS number,student_name,Mark (out of 100)\nKLS0000001,,80\nKLS0000099,,10\n");
        $html = $this->actingAs($this->owner)->post(route('teacher.exam.marks.import.preview', $this->exam), ['file' => $file])->assertOk()->getContent();

        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('data-testid="marks-import-warnings"', $html, 'csv without the Exam info sheet warns instead of blocking');
        $this->assertStringContainsString('data-label="Marks"', $html, 'cells carry data-label so tables restack on phones');
        $this->assertStringContainsString('ds-table-card-mobile', $html);
        $this->assertStringContainsString('css/marks-import.css', $html, 'the header row of the restacked table is hidden by this stylesheet');
        $this->assertMatchesRegularExpression('/<th scope="col">/', $html);
        $this->assertStringContainsString('data-testid="marks-import-confirm"', $html);

        preg_match('/name="token" value="([A-Za-z0-9]{40})"/', $html, $m);
        $result = $this->actingAs($this->owner)->followingRedirects()
            ->post(route('teacher.exam.marks.import.confirm', $this->exam), ['token' => $m[1]])->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="result-saved">1<', $result);
        $this->assertStringContainsString('data-testid="result-skipped">1<', $result);
        $this->assertStringContainsString('Student not found', $result);
    }

    public function test_import_views_use_global_wording_and_no_retired_icon_grey(): void
    {
        $dir = resource_path('views/marks-import');
        foreach (glob($dir.'/*.blade.php') as $file) {
            $text = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/UNEB|Uganda|UGX|\bP\.\d\b|Primary (One|Seven)|O-level|A-level/i', $text, basename($file).' must use global wording');
            $this->assertStringNotContainsString('#94A3B8', $text, basename($file));
        }
    }

    public function test_teacher_enter_page_download_template_link_is_44px(): void
    {
        $html = $this->actingAs($this->owner)->get(route('teacher.exam.marks.enter', $this->exam))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<a[^>]+min-height:44px[^>]*>[^<]*Download template/s',
            $html,
            'the Download template link must be a 44px target',
        );
    }


    public function test_entry_page_shows_a_saved_marks_note_only_when_marks_exist(): void
    {
        $this->actingAs($this->owner)->get(route('teacher.exam.marks.enter', $this->exam))
            ->assertOk()
            ->assertDontSee('already saved');

        $this->saveMark($this->students[1], 80);
        $this->saveMark($this->students[2], 65);

        $html = $this->actingAs($this->owner)->get(route('teacher.exam.marks.enter', $this->exam))->assertOk()->getContent();

        $this->assertStringContainsString('data-testid="saved-marks-note"', $html);
        $this->assertStringContainsString('2</strong> marks already saved', $html);
        $this->assertStringContainsString(route('teacher.exam.marks.view', $this->exam), $html);

        // The form must not prefill saved values (deliberately unchanged behaviour).
        $this->assertStringNotContainsString('value="80"', $html);
        $this->assertStringNotContainsString('value="65"', $html);
    }

}
