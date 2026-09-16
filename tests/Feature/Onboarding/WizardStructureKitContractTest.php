<?php

namespace Tests\Feature\Onboarding;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class WizardStructureKitContractTest extends TestCase
{
    public function test_structure_blade_matches_kit_chrome_and_copy(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/partials/manual-wizard-step-fields.blade.php'));

        $this->assertStringContainsString('manual-wizard-structure-card', $blade);
        $this->assertStringContainsString('manual-wizard-stream-chip', $blade);
        $this->assertStringContainsString('No streams yet — undivided base class.', $blade);
        $this->assertStringContainsString('No class teacher yet', $blade);
        $this->assertStringContainsString('placeholder="e.g. A, East, Science"', $blade);
        $this->assertStringContainsString('— Create a new teacher —', $blade);
        $this->assertStringContainsString('placeholder="Phone (optional)"', $blade);
        $this->assertStringContainsString('wire:click="addStructureStream(', $blade);
        $this->assertStringContainsString('wire:click="inviteStructureClassTeacher(', $blade);
        $this->assertStringContainsString('<x-button', $blade);
        $this->assertStringContainsString('data-testid="wizard-structure-add-stream-', $blade);
        $this->assertStringContainsString('data-testid="wizard-structure-invite-ct-', $blade);
        // Prefer kit CSS classes over ad-hoc gray utility cards on this step.
        $this->assertDoesNotMatchRegularExpression(
            '/stepKey === \'standards\'[\s\S]*?class="border border-gray-200 rounded-lg/',
            $blade
        );
    }

    public function test_structure_css_defines_card_and_chip_rules(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertStringContainsString('.manual-wizard-structure-card', $css);
        $this->assertStringContainsString('.manual-wizard-stream-chip', $css);
        $this->assertStringContainsString('.manual-wizard-structure-ct-empty', $css);
        $this->assertStringContainsString('.manual-wizard-structure-card-body', $css);
    }

    public function test_structure_action_buttons_forward_wire_click_via_x_button(): void
    {
        $html = Blade::render(
            '<x-button type="button" variant="outline" size="sm" wire:click="addStructureStream(12)" data-testid="wizard-structure-add-stream-12">Add</x-button>'
        );

        $this->assertStringContainsString('wire:click="addStructureStream(12)"', $html);
        $this->assertStringContainsString('data-testid="wizard-structure-add-stream-12"', $html);
        $this->assertStringContainsString('ds-btn-outline', $html);
        $this->assertStringContainsString('Add', $html);
    }
}
