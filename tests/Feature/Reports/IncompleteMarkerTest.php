<?php

namespace Tests\Feature\Reports;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
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
}
