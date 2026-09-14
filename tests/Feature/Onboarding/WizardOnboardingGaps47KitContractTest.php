<?php

namespace Tests\Feature\Onboarding;

use Tests\TestCase;

/**
 * Piece 3 gaps 4–7 — blade contract for new wizard fields/behaviours.
 */
class WizardOnboardingGaps47KitContractTest extends TestCase
{
    public function test_wizard_blade_includes_gender_teacher_assignment_terms_and_fees_fields(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/partials/manual-wizard-step-fields.blade.php'));
        $this->assertNotFalse($blade);

        $this->assertStringContainsString('data-testid="wizard-student-gender"', $blade);
        $this->assertStringContainsString('data-testid="wizard-student-dob"', $blade);
        $this->assertStringContainsString('data-testid="wizard-teacher-classes"', $blade);
        $this->assertStringContainsString('data-testid="wizard-teacher-subjects"', $blade);
        $this->assertStringContainsString('wire:model="teacherSelectedClasses"', $blade);
        $this->assertStringContainsString('wire:model="teacherSelectedSubjects"', $blade);

        $this->assertStringContainsString('data-testid="wizard-terms-bulk"', $blade);
        $this->assertStringContainsString('data-testid="wizard-term-list"', $blade);
        $this->assertStringContainsString('data-testid="wizard-term-add"', $blade);
        $this->assertStringContainsString('markTermCurrent', $blade);

        $this->assertStringContainsString('data-testid="wizard-fees-bulk"', $blade);
        $this->assertStringContainsString('data-testid="wizard-fee-scope"', $blade);
        $this->assertStringContainsString('data-testid="wizard-fee-yearly"', $blade);
        $this->assertStringContainsString('data-testid="wizard-fee-term"', $blade);
        $this->assertStringContainsString('data-testid="wizard-fee-class"', $blade);
        $this->assertStringContainsString('data-testid="wizard-fee-add"', $blade);
    }

    public function test_student_template_service_headers_include_new_columns(): void
    {
        $src = file_get_contents(app_path('Services/StudentUploadTemplateService.php'));
        $this->assertNotFalse($src);
        $this->assertStringContainsString("'Gender'", $src);
        $this->assertStringContainsString("'School Student ID'", $src);
        $this->assertStringContainsString("'UNEB Reg No.'", $src);
        $this->assertStringContainsString("'Date of Birth'", $src);
        $this->assertStringContainsString('isCandidateClass', $src);
    }
}
