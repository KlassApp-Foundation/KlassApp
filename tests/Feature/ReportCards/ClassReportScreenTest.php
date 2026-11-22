<?php

namespace Tests\Feature\ReportCards;

use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\ReportGeneration;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ReleasedExam;
use Tests\Support\StudentProfileFixture;
use Tests\TestCase;

/** The admin report cards class screen: released marks only, one row per student, a PDF per row. */
class ClassReportScreenTest extends TestCase
{
    use RefreshDatabase;
    use StudentProfileFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStudentProfileFixture();
        $this->draftMark('English', 74);
        $this->draftMark('Mathematics', 62);
    }

    private function screen(string $query = '')
    {
        return $this->actingAs($this->admin)->get('/admin/reports/cards'.$query);
    }

    public function test_nothing_unreleased_shows_on_the_screen(): void
    {
        $response = $this->screen()->assertOk();

        $response->assertSee('data-testid="rc-unreleased"', false)
            ->assertSee('Report cards use released marks only')
            ->assertDontSee('data-testid="rc-row"', false)
            ->assertDontSee('Amara Okoro');
    }

    public function test_a_released_sitting_lists_each_student_with_position_and_a_pdf(): void
    {
        $this->release('English');

        $response = $this->screen()->assertOk();

        $response->assertSee('data-testid="rc-row"', false)
            ->assertSee('Amara Okoro')
            ->assertSee('/admin/reports/cards/'.$this->link->id.'/student/'.$this->student->id.'/download?term='.$this->term->id, false)
            ->assertSee('Download report card for Amara Okoro');
    }

    public function test_an_absence_shows_as_abs_on_report_cards(): void
    {
        $math = Marks::query()->where('exam_id', $this->exams['Mathematics']->id)->first();
        $math->update(['marks' => null, 'grade' => 'AB', 'absent' => true]);
        $this->release('English');
        $this->release('Mathematics');

        $html = $this->screen()->assertOk()->getContent();

        $this->assertStringContainsString('>–<', $html);
        $this->assertStringContainsString('>74<', $html);
        $this->assertStringNotContainsString('>AB<', $html);
    }

    public function test_a_release_for_another_class_does_not_open_this_class(): void
    {
        ReleasedExam::release($this->exams['English'], 'class', $this->link->id + 999);

        $this->screen()->assertSee('data-testid="rc-unreleased"', false)->assertDontSee('Amara Okoro');
    }

    public function test_another_schools_class_is_never_shown(): void
    {
        $this->release('English');
        $other = \App\Models\Section::where('school_id', $this->otherSchool->id)->value('id');

        $html = $this->screen('?class='.$other)->assertOk()->getContent();

        $this->assertStringNotContainsString('Eve Other', $html);
        $this->assertStringContainsString('Grade 5', $html);
    }

    public function test_a_first_class_with_no_exam_still_shows_the_picker_and_opens_on_a_current_term_exam(): void
    {
        $nursery = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'nursery',
            'order' => 0,
            'status' => 1,
        ]);
        $baby = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Baby Class',
            'status' => 1,
        ]);
        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $nursery->id,
            'section_id' => $baby->id,
            'status' => 1,
        ]);
        $middle = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Middle Class',
            'status' => 1,
        ]);
        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $nursery->id,
            'section_id' => $middle->id,
            'status' => 1,
        ]);
        $lastTerm = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'status' => 'last',
            'starts_on' => '2026-01-06',
            'ends_on' => '2026-04-30',
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $nursery->id,
            'section_id' => $middle->id,
            'name' => 'Reading',
            'code' => 'RD',
            'type' => 'core',
            'status' => 1,
        ]);
        Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $nursery->id,
            'section_id' => $middle->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $lastTerm->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => 2,
            'status' => 'undone',
        ]));

        $opened = $this->screen()->assertOk()->getContent();

        $this->assertStringContainsString('id="rc-class"', $opened);
        $this->assertStringContainsString('data-open-filters', $opened);
        $this->assertStringContainsString('Baby Class', $opened);
        $this->assertStringNotContainsString('Baby Class has no exams yet', $opened);
        $this->assertMatchesRegularExpression(
            '/<option value="'.$this->section->id.'"[^>]*selected[^>]*>\\s*Grade 5\\s*<\\/option>/',
            $opened
        );

        $empty = $this->screen('?class='.$baby->id)->assertOk()->getContent();

        $this->assertStringContainsString('Baby Class has no exams yet', $empty);
        $this->assertStringContainsString('id="rc-class"', $empty);
        $this->assertStringContainsString('data-open-filters', $empty);
        $this->assertStringContainsString('id="rc-filter-sheet"', $empty);
        $this->assertStringContainsString('>Grade 5</option>', $empty);
        $this->assertMatchesRegularExpression(
            '/<option value="'.$baby->id.'"[^>]*selected[^>]*>\\s*Baby Class\\s*<\\/option>/',
            $empty
        );
    }

    public function test_phone_filters_sheet_carries_class_exam_stream_and_sort(): void
    {
        $this->release('English');

        $html = $this->screen()->getContent();

        $this->assertStringContainsString('id="rc-filter-sheet"', $html);
        $this->assertStringContainsString('name="sort"', $html);
        $this->assertStringContainsString('data-open-filters', $html);
    }

    public function test_class_pdf_states_build_preparing_failed_and_ready(): void
    {
        $this->release('English');
        $this->screen()->assertSee('data-testid="build-all"', false);

        $generation = ReportGeneration::create([
            'school_id' => $this->school->id, 'standard_link_id' => $this->link->id, 'academic_term_id' => $this->term->id,
            'class_name' => 'Grade 5', 'mode' => 'merged', 'status' => 'pending', 'requested_by' => $this->admin->id,
        ]);
        $this->screen()->assertSee('Preparing 1 report cards');

        $generation->update(['status' => 'failed']);
        $this->screen()->assertSee("The class PDF didn't finish", false);

        $generation->update(['status' => 'completed', 'file_path' => 'reports/x.pdf', 'file_name' => 'x.pdf']);
        $this->screen()->assertSee('data-testid="download-all"', false);
    }

    public function test_the_earlier_page_is_kept_at_history(): void
    {
        $this->actingAs($this->admin)->get('/admin/reports/cards/history')->assertOk()
            ->assertSee('Report Card Template')
            ->assertSee('New look')
            ->assertSee('Printer-friendly')
            ->assertSee('data-testid="open-printer-friendly"', false);
    }
}
