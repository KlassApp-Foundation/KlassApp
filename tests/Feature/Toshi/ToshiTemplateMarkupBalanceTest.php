<?php

namespace Tests\Feature\Toshi;

use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Toshi panel template must emit tag-balanced markup in every mode.
 *
 * Preview mode used to leave `.toshi-messages-area` (opened just above the
 * `@if($mode === 'preview')` block) unclosed, because its closing `</div>` only
 * existed inside the `@else` branch. `.toshi-root` was therefore never closed,
 * so everything after it in <body> — the dock wrapper and `@livewireScripts`
 * carrying `data-update-uri` — was parsed as children of the component root.
 * The first `POST /livewire/update` morphed the root and removed them, and every
 * later Livewire send threw "Cannot read properties of undefined (reading 'uri')".
 */
class ToshiTemplateMarkupBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    private function panelFor(string $mode): string
    {
        $slug = 'markup-balance-' . $mode;

        $school = School::create([
            'name' => 'Markup Balance ' . ucfirst($mode),
            'email' => $slug . '@klassapp.test',
            'phone' => '0700000011',
            'status' => 1,
            'slug' => $slug,
            'toshi_enabled' => 0,
            'toshi_mode' => $mode,
        ]);

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => $slug . '-admin@klassapp.test',
            'status' => 'active',
        ]);

        $html = Livewire::actingAs($admin)
            ->test(AgentToshi::class)
            ->set('visible', true)
            ->html();

        if ($mode === 'preview') {
            $this->assertStringContainsString(
                'toshi-root--preview',
                $html,
                'guard: the panel did not render in preview mode, so this balance check would be vacuous'
            );
        } else {
            $this->assertStringNotContainsString('toshi-root--preview', $html);
        }

        return $html;
    }

    /**
     * Count <div> opens and </div> closes in the rendered component markup,
     * ignoring Livewire attribute payloads (wire:snapshot embeds state as JSON).
     */
    private function divDelta(string $html): int
    {
        $stripped = preg_replace('/\swire:[a-zA-Z-]+="[^"]*"/', ' ', $html);

        return preg_match_all('/<div\b/', $stripped) - preg_match_all('#</div>#', $stripped);
    }

    public function test_preview_panel_markup_is_div_balanced(): void
    {
        $this->assertSame(
            0,
            $this->divDelta($this->panelFor('preview')),
            'Preview-mode panel markup must be tag-balanced: an unclosed <div> swallows the rest of <body> and breaks every later Livewire request.'
        );
    }

    public function test_onboarding_panel_markup_is_div_balanced(): void
    {
        $this->assertSame(
            0,
            $this->divDelta($this->panelFor('onboarding')),
            'Onboarding-mode panel markup must be tag-balanced.'
        );
    }
}
