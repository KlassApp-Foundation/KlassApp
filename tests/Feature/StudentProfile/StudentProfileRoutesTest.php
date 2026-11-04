<?php

namespace Tests\Feature\StudentProfile;

use App\Models\Academics\Marks;
use App\Models\Attendance;
use App\Models\FeePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\StudentProfileFixture;
use Tests\TestCase;

/** The profile's three forms, submitted the way the page submits them. */
class StudentProfileRoutesTest extends TestCase
{
    use RefreshDatabase;
    use StudentProfileFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStudentProfileFixture();
    }

    private function url(string $path = ''): string
    {
        return '/admin/students/'.$this->student->id.$path;
    }

    public function test_the_page_offers_the_three_actions_and_the_dialogs(): void
    {
        $html = $this->actingAs($this->admin)->get($this->url())->assertOk()->getContent();

        foreach (['action-payment', 'action-marks', 'action-attendance', 'dlg-pay', 'dlg-marks', 'dlg-attendance'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString('name="marks['.$this->exams['English']->id.']"', $html);
    }

    public function test_posting_a_payment_saves_it_and_returns_to_the_fees_tab(): void
    {
        $this->actingAs($this->admin)->post($this->url('/payments'), [
            'amount' => '50,000', 'payment_method' => 'cash', 'paid_on' => now()->toDateString(), 'request_id' => 'r1',
        ])->assertRedirect($this->url().'?tab=fees');

        $this->assertSame(1, FeePayment::where('user_id', $this->student->id)->count());
    }

    public function test_a_bad_payment_returns_to_the_page_with_the_form_reopened(): void
    {
        $response = $this->actingAs($this->admin)->from($this->url())->post($this->url('/payments'), [
            'amount' => '-5', 'payment_method' => 'cash', 'paid_on' => now()->toDateString(),
        ]);
        $response->assertRedirect($this->url())->assertSessionHasErrors('amount');

        $page = $this->actingAs($this->admin)->get($this->url())->getContent();
        $this->assertStringContainsString("open(\"pay\")", str_replace("'", '"', $page));
        $this->assertSame(0, FeePayment::where('user_id', $this->student->id)->count());
    }

    public function test_posting_marks_saves_drafts_only(): void
    {
        $exam = $this->exams['English'];
        $this->actingAs($this->admin)->post($this->url('/marks'), [
            'sitting' => $exam->exam_type_id.':'.$exam->academic_term_id,
            'marks' => [$exam->id => '64'],
        ])->assertRedirect($this->url().'?tab=performance');

        $this->assertSame('64.00', number_format((float) Marks::where('student_id', $this->student->id)->value('marks'), 2, '.', ''));
        $html = $this->actingAs($this->admin)->get($this->url().'?tab=performance')->getContent();
        $this->assertStringNotContainsString('data-testid="released-row"', $html);
        $this->assertStringNotContainsString('data-testid="progress-average"', $html);
    }

    public function test_posting_attendance_saves_the_day_with_its_comment(): void
    {
        $this->actingAs($this->admin)->post($this->url('/attendance'), [
            'date' => now()->toDateString(), 'status' => 'absent', 'comment' => 'Fever',
        ])->assertRedirect($this->url().'?tab=attendance');

        $row = Attendance::where('user_id', $this->student->id)->first();
        $this->assertSame(0, (int) $row->status);
        $this->assertSame('Fever', $row->remarks);

        $this->actingAs($this->admin)->get($this->url().'?tab=attendance')->assertSee('Fever');
    }
}
