<?php

namespace Tests\Feature\Toshi;

use App\Livewire\AgentToshi;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Piece 2 PR2 — clay polish for suggestion chips, plan option cards,
 * tool-confirm + execution-plan cards. Pulse freeze canary re-checked.
 */
class ToshiPiece2CardsChipsContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_published_toshi_ui_css_keeps_pulse_ledger_blur_canary(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('FROZEN — Pulse design system', $css);
        $this->assertMatchesRegularExpression(
            '/\.ds-table-ledger thead\s*\{[^}]*backdrop-filter:\s*blur\(12px\)/s',
            $css
        );
        $this->assertStringContainsString('.ds-kpi-card', $css);
        $this->assertStringContainsString('background: rgba(34,197,94,0.08)', $css);
    }

    public function test_published_toshi_ui_css_includes_piece2_card_chip_tokens(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('PIECE 2 — Toshi panel chrome (header / composer / chips / cards)', $css);
        $this->assertStringContainsString('--toshi-clay: #c96442', $css);
        $this->assertStringContainsString('.toshi-option-card-badge', $css);
        $this->assertStringContainsString('.toshi-confirm-btn-yes', $css);
        $this->assertStringContainsString('.toshi-plan-btn-execute', $css);
        $this->assertStringContainsString('.toshi-chip-suggestion', $css);
    }

    public function test_source_and_published_toshi_ui_css_match(): void
    {
        $source = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $published = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertSame($source, $published, 'Run: php artisan vendor:publish --tag=toshi-ui-css --force');
    }

    public function test_plan_option_cards_use_clay_badge_not_green_inline_hover(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/agent-toshi.blade.php'));
        $this->assertSame(2, substr_count($blade, 'toshi-option-card-badge'));
        $this->assertDoesNotMatchRegularExpression(
            '/data-testid="toshi-plan-\{\{\s*\$plan->id\s*\}\}"[\s\S]{0,200}onmouseover=/',
            $blade
        );
        $this->assertDoesNotMatchRegularExpression(
            '/class="toshi-option-card"[\s\S]{0,400}background:\s*#22C55E/',
            $blade
        );
    }

    public function test_tool_confirm_and_plan_card_components_expose_testids(): void
    {
        $confirm = file_get_contents(base_path('packages/toshi-ui/resources/views/components/tool-confirm-card.blade.php'));
        $plan = file_get_contents(base_path('packages/toshi-ui/resources/views/components/plan-card.blade.php'));
        $this->assertStringContainsString('data-testid="toshi-tool-confirm-card"', $confirm);
        $this->assertStringContainsString('data-testid="toshi-tool-confirm-yes"', $confirm);
        $this->assertStringContainsString('data-testid="toshi-execution-plan-card"', $plan);
        $this->assertStringContainsString('data-testid="toshi-plan-execute"', $plan);
    }

    public function test_plan_selection_step_renders_option_cards_with_badge(): void
    {
        Plan::query()->create([
            'name' => 'freemium',
            'display_name' => 'Freemium',
            'amount' => 0,
            'cycle' => 30,
            'is_active' => 1,
            'order' => 1,
            'is_custom_pricing' => 0,
            'no_of_users' => 5,
            'no_of_students' => 100,
        ]);

        $school = School::create([
            'name' => 'Piece2 Cards School',
            'email' => 'piece2-cards@klassapp.test',
            'phone' => '0700000088',
            'status' => 1,
            'slug' => 'piece2-cards-school',
            'toshi_enabled' => 1,
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => 'piece2.cards.admin@klassapp.test',
            'status' => 'active',
        ]);

        $html = Livewire::actingAs($admin)
            ->test(AgentToshi::class)
            ->set('visible', true)
            ->set('selectedPlanId', null)
            ->set('actionStep', 'onboarding_plan_selection')
            ->html();

        $this->assertStringContainsString('data-testid="toshi-plan-cards"', $html);
        $this->assertStringContainsString('toshi-option-card-badge', $html);
        $this->assertStringContainsString('toshi-option-card', $html);
    }
}
