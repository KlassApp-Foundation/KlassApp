<?php

namespace Tests\Feature\Reports;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Academics\Marks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SyntheticClass;
use Tests\TestCase;

/**
 * A missing mark or an absence in a counting set keeps the arithmetic and
 * names that set on the card and on the class list.
 */
class IncompleteMarkerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);
    }

    public function test_a_counting_set_with_an_absence_is_named_on_the_card_and_the_class_list(): void
    {
        $class = SyntheticClass::build(['learners' => 10, 'counting_sets' => [1]]);

        $absent = $class->cardData('KLS9000010');
        $clean = $class->cardData('KLS9000001');

        $this->assertSame(['Mid Term'], $absent['incompleteSets']);
        $this->assertSame([], $clean['incompleteSets']);

        $text = preg_replace('/\s+/', ' ', $class->pdfText('KLS9000010'));
        $this->assertStringContainsString('Incomplete: Mid Term.', $text);

        $response = $this->actingAs($class->teacher)->get(route('teacher.reports.cards.show', $class->link));
        $response->assertOk();
        $response->assertSee('Incomplete: Mid Term.', false);
        $response->assertSee('data-kind="incomplete"', false);
    }

    public function test_a_blank_in_a_counting_set_is_named_on_the_card_and_the_results_sheet(): void
    {
        $class = SyntheticClass::build(['learners' => 1, 'counting_sets' => [0]]);
        $learner = $class->learners['KLS9000001'];
        Marks::query()
            ->where('student_id', $learner->id)
            ->where('exam_id', $class->exams[0][0]->id)
            ->delete();

        $data = $class->cardData('KLS9000001');
        $this->assertSame(['Beginning of Term'], $data['incompleteSets']);

        $text = preg_replace('/\s+/', ' ', $class->pdfText('KLS9000001'));
        $this->assertStringContainsString('Incomplete: Beginning of Term.', $text);

        $sheet = $this->actingAs($class->admin)->get(route('admin.marks.filter', [
            'class' => $class->section->id,
            'term' => $class->term->id,
            'examType' => $class->exams[0][0]->exam_type_id,
        ]));
        $sheet->assertOk();
        $sheet->assertSee('Incomplete: Beginning of Term.', false);
        $sheet->assertSee('data-kind="incomplete"', false);

        $studentPage = $this->actingAs($class->admin)->get(route('admin.marks.student.class', [
            'user' => $learner->id,
            'class' => $class->section->id,
            'exam' => $class->exams[0][0]->id,
        ]));
        $studentPage->assertOk();
        $studentPage->assertSee('Incomplete: Beginning of Term.', false);
    }
}
