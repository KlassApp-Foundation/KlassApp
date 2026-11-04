<?php

namespace Tests\Feature\ReportCards;

use App\Models\ReportGeneration;
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
        $this->actingAs($this->admin)->get('/admin/reports/cards/history')->assertOk()->assertSee('Report Card Template');
    }
}
