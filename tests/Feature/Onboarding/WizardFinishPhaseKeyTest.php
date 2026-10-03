<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Soft-launch A4: Confirm & finish threw pageerror
 * `TypeError: Cannot read properties of null (reading 'before')` from Livewire morph.
 * Fix: stable wizard-phase wire:key so done vs flow replace as one subtree.
 */
class WizardFinishPhaseKeyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => "Phase's School",
            'email' => 'phase@test.sch.ug',
            'phone' => '0700000077',
            'slug' => 'phase-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Phase Admin',
            'email' => 'admin@phase.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Phase',
            'lastname' => 'Admin',
        ]);

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
    }

    public function test_blade_source_defines_stable_done_and_flow_phase_keys(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/manual-onboarding-wizard.blade.php'));
        $this->assertNotFalse($blade);
        $this->assertStringContainsString(
            "wire:key=\"wizard-phase-{{ \$finished ? 'done' : 'flow' }}\"",
            $blade
        );
        $this->assertMatchesRegularExpression(
            '/data-testid="wizard-phase-\{\{\s*\$finished\s*\?\s*[\'"]done[\'"]\s*:\s*[\'"]flow[\'"]\s*\}\}"/',
            $blade
        );
    }

    public function test_confirm_review_renders_done_phase_with_balanced_divs(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);
        $component
            ->set('schoolName', 'Phase Academy')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-PHASE')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('skipOptionalStep');

        $this->goToStepKey($component, 'students');
        $component
            ->call('skipOptionalStep')
            ->call('next')
            ->call('next');

        $component
            ->set('whatsappPhone', '+256700777888')
            ->call('sendWhatsAppVerificationCode');
        $code = (string) $component->get('whatsappOtpDisplay');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $component
            ->set('whatsappOtpInput', $code)
            ->call('verifyWhatsAppCode')
            ->call('next')
            ->call('next')
            ->call('confirmReview')
            ->assertSet('finished', true)
            ->assertSee('Phase Academy is ready')
            ->assertSeeHtml('data-testid="wizard-phase-done"')
            ->assertDontSeeHtml('data-testid="wizard-phase-flow"')
            ->assertSeeHtml('data-testid="wizard-completion-suggestions"');

        $html = $component->html();
        $this->assertSame(0, $this->divDelta($html), 'finished wizard markup must be div-balanced for Livewire morph');
    }

    private function goToStepKey(object $component, string $key): void
    {
        $keys = array_column($component->instance()->steps, 'key');
        $index = array_search($key, $keys, true);
        $this->assertNotFalse($index, "step {$key} missing");
        $component->call('goToStep', $index);
        $component->call('goToStep', $index);
    }

    private function divDelta(string $html): int
    {
        $stripped = preg_replace('/wire:snapshot="[^"]*"/', '', $html) ?? $html;
        $stripped = preg_replace('/wire:effects="[^"]*"/', '', $stripped) ?? $stripped;
        preg_match_all('/<div\b/i', $stripped, $opens);
        preg_match_all('/<\/div>/i', $stripped, $closes);

        return count($opens[0]) - count($closes[0]);
    }
}
