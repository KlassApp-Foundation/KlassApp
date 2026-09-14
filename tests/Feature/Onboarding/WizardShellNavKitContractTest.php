<?php

namespace Tests\Feature\Onboarding;

use App\Services\OnboardingStepsService;
use App\Services\SchoolCategorySeeder;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class WizardShellNavKitContractTest extends TestCase
{
    public function test_shell_blade_matches_kit_nav_chrome(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/manual-onboarding-wizard.blade.php'));

        $this->assertStringContainsString('manual-wizard-brand', $blade);
        $this->assertStringContainsString('Setting up without Toshi', $blade);
        $this->assertStringContainsString('Continue →', $blade);
        $this->assertStringContainsString('Confirm & finish', $blade);
        $this->assertStringContainsString('← Previous', $blade);
        $this->assertStringContainsString('wire:click="next"', $blade);
        $this->assertStringContainsString('wire:click="previous"', $blade);
        $this->assertStringContainsString('data-testid="wizard-nav"', $blade);
        $this->assertStringContainsString('data-testid="wizard-progress"', $blade);
        // Do not reintroduce Toshi CTA coordination on the manual path.
        $this->assertStringNotContainsString('data-toshi-open', $blade);
        $this->assertStringNotContainsString('Let Toshi do it', $blade);
    }

    public function test_wizard_page_keeps_toshi_manual_wizard_coordination_attr(): void
    {
        $blade = file_get_contents(resource_path('views/admin/onboarding/wizard.blade.php'));

        $this->assertStringContainsString('data-toshi-manual-wizard="1"', $blade);
        $this->assertStringContainsString('toshi-manual-wizard', $blade);
    }

    public function test_student_size_options_match_onboarding_steps_service_exactly(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/partials/manual-wizard-step-fields.blade.php'));

        $this->assertStringContainsString('OnboardingStepsService::STUDENT_SIZE_OPTIONS', $blade);
        $this->assertStringContainsString('selectStudentSize', $blade);
        $this->assertStringContainsString('manual-wizard-plan-grid--sizes', $blade);
        $this->assertStringContainsString('data-testid="wizard-student-size"', $blade);
        // Size is a card radiogroup, not a <select>.
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*(id|data-testid)=["\']wizard-student-size/', $blade);

        foreach (OnboardingStepsService::STUDENT_SIZE_OPTIONS as $option) {
            $this->assertContains($option, [
                'Under 100 students',
                '100-300 students',
                '300-500 students',
                '500+ students',
            ]);
        }

        $this->assertSame([
            'Under 100 students',
            '100-300 students',
            '300-500 students',
            '500+ students',
        ], OnboardingStepsService::STUDENT_SIZE_OPTIONS);

        $this->assertStringNotContainsString('1-100', $blade);
        $this->assertStringNotContainsString('1000+', $blade);
    }

    public function test_school_category_labels_match_seeder_exactly(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/partials/manual-wizard-step-fields.blade.php'));

        $this->assertStringContainsString('SchoolCategorySeeder::CATEGORIES', $blade);
        $this->assertStringContainsString('selectSchoolCategory', $blade);
        $this->assertStringContainsString('data-testid="wizard-category-options"', $blade);
        $this->assertStringContainsString('data-testid="wizard-plan-cards"', $blade);
        $this->assertStringContainsString('selectPlan', $blade);

        $this->assertSame([
            'nursery' => 'Nursery only',
            'primary' => 'Primary',
            'primary_nursery' => 'Primary + Nursery',
            'o_level' => 'O-Level',
            'o_a_level' => 'O-Level + A-Level',
        ], SchoolCategorySeeder::CATEGORIES);

        // Kit placeholders must not appear in production Blade.
        $this->assertStringNotContainsString('Nursery / Kindergarten', $blade);
        $this->assertStringNotContainsString('Primary (P1-P7)', $blade);
        $this->assertStringNotContainsString('primary_secondary', $blade);

        $this->assertCount(16, OnboardingStepsService::ALL_STEPS);
    }

    public function test_css_size_grid_is_two_columns_at_sm(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertStringContainsString('.manual-wizard-plan-grid--sizes', $css);
        $this->assertMatchesRegularExpression(
            '/\.manual-wizard-plan-grid--sizes\s*\{[^}]*grid-template-columns:\s*repeat\(2,\s*1fr\)/s',
            $css
        );
    }

    public function test_x_button_forwards_wire_click_attributes(): void
    {
        $html = Blade::render('<x-button variant="primary" size="sm" wire:click="next" data-testid="wizard-next">Continue →</x-button>');

        $this->assertStringContainsString('wire:click="next"', $html);
        $this->assertStringContainsString('data-testid="wizard-next"', $html);
        $this->assertStringContainsString('ds-btn-primary', $html);
        $this->assertStringContainsString('Continue →', $html);
    }

    public function test_css_has_brand_strip_and_seventeen_dot_progress_width(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertStringContainsString('.manual-wizard-brand', $css);
        $this->assertStringContainsString('.manual-wizard-brand-name', $css);
        $this->assertMatchesRegularExpression('/\.manual-wizard-progress\s*\{[^}]*max-width:\s*420px/s', $css);
    }
}
