<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class WizardReviewPanelsKitContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_blade_uses_panel_cards_with_edit_actions(): void
    {
        $blade = (string) file_get_contents(
            resource_path('views/livewire/partials/manual-wizard-step-fields.blade.php')
        );

        $this->assertStringContainsString('manual-wizard-review-panels', $blade);
        $this->assertStringContainsString('manual-wizard-review-panel', $blade);
        $this->assertStringContainsString('data-testid="wizard-review"', $blade);
        $this->assertStringContainsString("wire:click=\"editSection('{{ \$row['key'] }}')\"", $blade);
        $this->assertStringContainsString('data-testid="wizard-edit-', $blade);
        // Flat single-card row list must not be the review layout anymore.
        $this->assertStringNotContainsString('manual-wizard-review-row', $blade);
        $this->assertStringNotContainsString('manual-wizard-review-header', $blade);
    }

    public function test_css_review_panels_are_responsive_grid(): void
    {
        $css = (string) file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertStringContainsString('.manual-wizard-review-panels', $css);
        $this->assertStringContainsString('.manual-wizard-review-panel', $css);
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*768px\)\s*\{\s*\.manual-wizard-review-panels\s*\{[^}]*grid-template-columns:\s*repeat\(2/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1280px\)\s*\{\s*\.manual-wizard-review-panels\s*\{[^}]*grid-template-columns:\s*repeat\(3/s',
            $css
        );
    }

    public function test_review_subjects_unique_names_despite_per_class_rows(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        Country::create(['name' => 'Uganda', 'short_name' => 'UG', 'status' => 1, 'order' => 1]);
        Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);

        $school = School::create([
            'name' => 'Review Subjects School',
            'email' => 'review-subjects@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'review-subjects-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);
        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Review Admin',
            'email' => 'admin@review-subjects.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Review',
            'lastname' => 'Admin',
        ]);

        $this->actingAs($admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $component
            ->set('schoolName', 'Review Subjects Academy')
            ->call('next')
            ->set('studentSize', '100-300 students')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-REV')
            ->call('next')
            ->call('next') // uneb
            ->call('next'); // academic year → seeds P.1–P.7 subjects

        $school->refresh();
        $rowCount = Subject::where('school_id', $school->id)->count();
        $uniqueNames = Subject::where('school_id', $school->id)->pluck('name')->unique()->sort()->values();

        // Seeder creates one subject row per class/section — not junk duplicates.
        $this->assertGreaterThan($uniqueNames->count(), $rowCount, 'expected per-class subject rows');
        $this->assertGreaterThanOrEqual(4, $uniqueNames->count());
        $this->assertGreaterThanOrEqual(7, (int) floor($rowCount / max(1, $uniqueNames->count())));

        // Walk to review (skip optional / defaults).
        $component
            ->call('next') // standards
            ->call('next') // subjects
            ->call('skipOptionalStep') // teachers
            ->call('goToStep', $this->stepIndexFor($component, 'students'))
            ->call('skipOptionalStep')
            ->call('next') // terms
            ->call('next') // fees
            ->set('whatsappPhone', '+256700111222')
            ->call('sendWhatsAppVerificationCode');
        $otp = (string) $component->get('whatsappOtpDisplay');
        $component->set('whatsappOtpInput', $otp)->call('verifyWhatsAppCode')->call('next');
        $component->call('next'); // plan → review

        $this->assertSame('review', $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null);
        $component->call('goToStep', $component->get('stepIndex'));

        $subjectsRow = collect($component->get('reviewSummary'))->firstWhere('key', 'subjects');
        $this->assertNotNull($subjectsRow);
        $value = (string) ($subjectsRow['value'] ?? '');

        foreach ($uniqueNames as $name) {
            $this->assertSame(1, substr_count($value, (string) $name), "subject {$name} should appear once in review: {$value}");
        }
        $this->assertStringContainsString('unique across', $value);

        $component
            ->assertSeeHtml('manual-wizard-review-panels')
            ->assertSeeHtml('data-testid="wizard-review-subjects"')
            ->assertSeeHtml('data-testid="wizard-edit-subjects"')
            ->call('editSection', 'subjects');

        $this->assertSame(
            'subjects',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
    }

    private function stepIndexFor(object $component, string $key): int
    {
        $keys = array_column($component->instance()->steps, 'key');
        $index = array_search($key, $keys, true);
        $this->assertNotFalse($index);

        return (int) $index;
    }
}
