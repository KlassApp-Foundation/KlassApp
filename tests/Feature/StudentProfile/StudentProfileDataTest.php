<?php

namespace Tests\Feature\StudentProfile;

use App\Models\Academics\Marks;
use App\Models\Userprofile;
use App\Services\StudentProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\StudentProfileFixture;
use Tests\TestCase;

/** What the admin student profile shows: released performance only, an approved-only progress report, joined date. */
class StudentProfileDataTest extends TestCase
{
    use RefreshDatabase;
    use StudentProfileFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStudentProfileFixture();
    }

    private function page(): array
    {
        return app(StudentProfileService::class)->page($this->school->id, $this->student);
    }

    public function test_unreleased_marks_never_show_in_performance_or_progress(): void
    {
        $this->draftMark('English', 91);
        $this->submission('English', 'draft');
        $this->draftMark('Mathematics', 77);
        $this->submission('Mathematics', 'submitted', 'pending');

        $page = $this->page();

        $this->assertSame([], $page['performance']['released']);
        $this->assertNull($page['performance']['latest']);
        $this->assertNull($page['performance']['progress']);
    }

    public function test_released_marks_show_with_a_download_for_the_term_report(): void
    {
        $this->draftMark('English', 80);
        $this->draftMark('Mathematics', 60);
        $this->release('English');
        $this->release('Mathematics');

        $perf = $this->page()['performance'];

        $this->assertCount(1, $perf['released']);
        $this->assertSame('70', (string) $perf['released'][0]['average']);
        $this->assertStringContainsString('/admin/reports/cards/'.$this->link->id.'/student/'.$this->student->id.'/download', $perf['released'][0]['download_url']);
    }

    public function test_a_release_for_another_class_does_not_show_this_students_marks(): void
    {
        $this->draftMark('English', 80);
        \Tests\Support\ReleasedExam::release($this->exams['English'], 'class', $this->link->id + 999);

        $this->assertSame([], $this->page()['performance']['released']);
    }

    public function test_progress_report_lists_approved_unreleased_subjects_only(): void
    {
        $this->draftMark('English', 88);
        $this->submission('English', 'locked', 'approved');
        $this->draftMark('Mathematics', 12);
        $this->submission('Mathematics', 'submitted', 'pending');

        $progress = $this->page()['performance']['progress'];

        $this->assertNotNull($progress);
        $this->assertSame(1, $progress['subjects']);
        $this->assertSame('88', (string) $progress['average']);
    }

    public function test_progress_report_download_is_a_pdf_and_404s_when_nothing_is_approved(): void
    {
        $this->actingAs($this->admin)->get('/admin/students/'.$this->student->id.'/progress-report')->assertNotFound();

        $this->draftMark('English', 88);
        $this->submission('English', 'locked', 'approved');

        $response = $this->actingAs($this->admin)->get('/admin/students/'.$this->student->id.'/progress-report');
        $response->assertOk();
        $this->assertStringContainsString('pdf', (string) $response->headers->get('content-type'));
    }

    public function test_profile_page_never_prints_unreleased_marks(): void
    {
        Marks::query()->delete();
        $this->draftMark('English', 93);
        $this->submission('English', 'submitted', 'pending');

        $html = $this->actingAs($this->admin)->get('/admin/students/'.$this->student->id)->assertOk()->getContent();

        $this->assertStringNotContainsString('93', strip_tags(explode('Performance', $html, 2)[1] ?? ''));
    }

    public function test_joined_date_defaults_to_the_onboarding_date(): void
    {
        $fresh = Userprofile::query()->where('user_id', $this->student->id)->first();
        $this->assertSame(now()->toDateString(), substr((string) $fresh->getRawOriginal('joining_date'), 0, 10));
    }

    public function test_joined_date_falls_back_to_the_account_created_date_for_older_rows(): void
    {
        Userprofile::query()->where('user_id', $this->student->id)->update(['joining_date' => null]);
        $this->student->forceFill(['created_at' => '2025-02-10 08:00:00'])->saveQuietly();

        $this->assertStringStartsWith('Feb 2025', $this->page()['joined']);
    }

    public function test_the_profile_of_another_school_is_not_found_and_actions_are_refused(): void
    {
        $id = $this->otherStudent->id;
        $this->actingAs($this->admin)->get('/admin/students/'.$id)->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/students/'.$id.'/progress-report')->assertNotFound();
        $this->actingAs($this->admin)->post('/admin/students/'.$id.'/payments', ['amount' => 1000, 'payment_method' => 'cash', 'paid_on' => now()->toDateString()])->assertNotFound();
        $this->actingAs($this->admin)->post('/admin/students/'.$id.'/attendance', ['date' => now()->toDateString(), 'status' => 'absent'])->assertNotFound();
    }
}
