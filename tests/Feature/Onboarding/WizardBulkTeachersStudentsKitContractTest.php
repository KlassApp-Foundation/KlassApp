<?php

namespace Tests\Feature\Onboarding;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Piece 3 PR4 — teachers/students bulk steps must match kit surface + real production behavior
 * (paste, email/phone, Download template, Upload, Skip with confirm-when-drafts).
 */
class WizardBulkTeachersStudentsKitContractTest extends TestCase
{
    private function fieldsBlade(): string
    {
        return (string) file_get_contents(
            resource_path('views/livewire/partials/manual-wizard-step-fields.blade.php')
        );
    }

    public function test_teachers_step_has_kit_modes_and_fields(): void
    {
        $blade = $this->fieldsBlade();

        $this->assertStringContainsString('data-testid="wizard-teachers-bulk"', $blade);
        $this->assertStringContainsString('data-testid="wizard-teacher-template"', $blade);
        $this->assertStringContainsString('Download template', $blade);
        $this->assertStringContainsString('data-testid="wizard-teacher-upload"', $blade);
        $this->assertStringContainsString('Upload file', $blade);
        $this->assertStringContainsString('data-testid="wizard-teacher-paste"', $blade);
        $this->assertStringContainsString('Paste names (one per line)', $blade);
        $this->assertStringContainsString('wire:click="applyTeacherPaste"', $blade);
        $this->assertStringContainsString('data-testid="wizard-teacher-paste-btn"', $blade);
        $this->assertStringContainsString('Add from paste', $blade);
        $this->assertStringContainsString('data-testid="wizard-teacher-email"', $blade);
        $this->assertStringContainsString('data-testid="wizard-teacher-phone"', $blade);
        $this->assertStringContainsString('data-testid="wizard-teachers-skip"', $blade);
        $this->assertStringContainsString('Skip for now', $blade);
        $this->assertStringContainsString('wire:click="skipOptionalStep"', $blade);
        $this->assertStringContainsString(
            'wire:confirm="You have teachers in the list that will not be saved. Skip anyway?"',
            $blade
        );
        $this->assertStringContainsString('templates/teacher-upload-template.xlsx', $blade);
    }

    public function test_students_step_has_kit_modes_and_dynamic_template_route(): void
    {
        $blade = $this->fieldsBlade();

        $this->assertStringContainsString('data-testid="wizard-students-bulk"', $blade);
        $this->assertStringContainsString('data-testid="wizard-student-template"', $blade);
        $this->assertStringContainsString("route('admin.students.upload-template')", $blade);
        $this->assertStringContainsString('data-testid="wizard-student-upload"', $blade);
        $this->assertStringContainsString('data-testid="wizard-student-paste"', $blade);
        $this->assertStringContainsString('wire:click="applyStudentPaste"', $blade);
        $this->assertStringContainsString('data-testid="wizard-students-skip"', $blade);
        $this->assertStringContainsString(
            'wire:confirm="You have students in the list that will not be saved. Skip anyway?"',
            $blade
        );
        // Must not regress to a static public template for students.
        $this->assertStringNotContainsString('student-upload-template.xlsx', $blade);
    }

    public function test_bulk_primary_actions_use_x_button(): void
    {
        $blade = $this->fieldsBlade();

        $this->assertMatchesRegularExpression(
            '/<x-button[^>]+wire:click="applyTeacherPaste"/',
            $blade
        );
        $this->assertMatchesRegularExpression(
            '/<x-button[^>]+wire:click="addTeacherDraft"/',
            $blade
        );
        $this->assertStringContainsString('data-testid="wizard-teachers-skip"', $blade);
        $this->assertMatchesRegularExpression(
            '/wire:click="skipOptionalStep"\s+wire:confirm="You have teachers/',
            $blade
        );
        $this->assertMatchesRegularExpression(
            '/<x-button[^>]+wire:click="applyStudentPaste"/',
            $blade
        );
        $this->assertMatchesRegularExpression(
            '/<x-button[^>]+wire:click="addStudentDraft"/',
            $blade
        );
        $this->assertStringContainsString('data-testid="wizard-students-skip"', $blade);
        $this->assertMatchesRegularExpression(
            '/wire:click="skipOptionalStep"\s+wire:confirm="You have students/',
            $blade
        );
        // Template download stays a plain <a> (dynamic href / download attr).
        $this->assertDoesNotMatchRegularExpression(
            '/<x-button[^>]+data-testid="wizard-student-template"/',
            $blade
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<x-button[^>]+data-testid="wizard-teacher-template"/',
            $blade
        );
    }

    public function test_standards_step_does_not_reuse_bulk_classes(): void
    {
        $blade = $this->fieldsBlade();
        $standardsChunk = $this->extractStepChunk($blade, 'standards', 'teachers');

        $this->assertStringContainsString('manual-wizard-structure', $standardsChunk);
        $this->assertStringNotContainsString('manual-wizard-bulk', $standardsChunk);
        $this->assertStringNotContainsString('wizard-teachers-bulk', $standardsChunk);
    }

    public function test_css_bulk_kit_helpers_present(): void
    {
        $css = (string) file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertStringContainsString('.manual-wizard-bulk-toolbar', $css);
        $this->assertStringContainsString('.manual-wizard-bulk-pair', $css);
        $this->assertStringContainsString('.manual-wizard-bulk-help', $css);
        $this->assertStringContainsString('.manual-wizard-bulk-footnote', $css);
        $this->assertStringContainsString('.manual-wizard-bulk-file', $css);
        $this->assertStringContainsString('.manual-wizard-bulk-upload-icon', $css);
        $this->assertMatchesRegularExpression(
            '/\.manual-wizard-bulk-pair\s*\{[^}]*grid-template-columns:\s*1fr/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*768px\)\s*\{\s*\.manual-wizard-bulk-pair\s*\{[^}]*grid-template-columns:\s*repeat\(2/s',
            $css
        );
    }

    public function test_x_button_forwards_wire_confirm(): void
    {
        $html = Blade::render(
            '<x-button type="button" variant="ghost" size="sm" wire:click="skipOptionalStep" wire:confirm="Skip anyway?" data-testid="wizard-teachers-skip">Skip for now</x-button>'
        );

        $this->assertStringContainsString('wire:click="skipOptionalStep"', $html);
        $this->assertStringContainsString('wire:confirm="Skip anyway?"', $html);
        $this->assertStringContainsString('ds-btn-ghost', $html);
        $this->assertStringContainsString('data-testid="wizard-teachers-skip"', $html);
    }

    private function extractStepChunk(string $blade, string $fromKey, string $untilKey): string
    {
        $start = strpos($blade, "@elseif(\$stepKey === '{$fromKey}')");
        $this->assertNotFalse($start, "missing step {$fromKey}");
        $end = strpos($blade, "@elseif(\$stepKey === '{$untilKey}')", $start + 1);
        $this->assertNotFalse($end, "missing following step {$untilKey}");

        return substr($blade, $start, $end - $start);
    }
}
