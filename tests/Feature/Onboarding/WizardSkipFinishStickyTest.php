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
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Soft-launch A4: after skip + "is ready", reload remounted on Teachers.
 * Optional skips and finish must persist on the school row.
 */
class WizardSkipFinishStickyTest extends TestCase
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
            'name' => "Sticky's School",
            'email' => 'sticky@test.sch.ug',
            'phone' => '0700000066',
            'slug' => 'sticky-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Sticky Admin',
            'email' => 'admin@sticky.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Sticky',
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

    private function advanceThroughSubjects(object $component): void
    {
        $component
            ->set('schoolName', 'Sticky Academy')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-STICKY')
            ->call('next')
            ->call('next') // uneb
            ->call('next') // academic year seeds classes/subjects
            ->call('next') // structure
            ->call('next'); // subjects → teachers
    }

    public function test_skip_optional_steps_persist_across_remount(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceThroughSubjects($component);
        $this->assertSame(
            'teachers',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );

        // skipOptionalStep jumps to the next blocking step (terms), past students —
        // same as ManualWizardBulkTeachersStudentsTest; visit students explicitly.
        $component->call('skipOptionalStep'); // teachers
        $this->goToStepKey($component, 'students');
        $component->call('skipOptionalStep'); // students → terms

        $this->school->refresh();
        $this->assertSame(['teachers', 'students'], OnboardingStepsService::skippedSteps($this->school));
        $this->assertTrue(OnboardingStepsService::isStepComplete('teachers', $this->school));
        $this->assertTrue(OnboardingStepsService::isStepComplete('students', $this->school));

        $remount = Livewire::test(ManualOnboardingWizard::class);
        $key = $remount->instance()->steps[$remount->get('stepIndex')]['key'] ?? null;

        $this->assertNotSame('teachers', $key);
        $this->assertNotSame('students', $key);
        $this->assertContains($key, ['terms', 'fees', 'whatsapp_verify', 'plan_selection', 'review']);
    }

    private function goToStepKey(object $component, string $key): void
    {
        $keys = array_column($component->instance()->steps, 'key');
        $index = array_search($key, $keys, true);
        $this->assertNotFalse($index, "step {$key} missing");
        $component->call('goToStep', $index);
        $component->call('goToStep', $index);
        $this->assertSame(
            $key,
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
    }

    public function test_confirm_review_persists_finish_so_remount_stays_ready(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceThroughSubjects($component);

        $component->call('skipOptionalStep'); // teachers → terms
        $this->goToStepKey($component, 'students');
        $component
            ->call('skipOptionalStep') // students → terms
            ->call('next') // terms
            ->call('next'); // fees

        $component
            ->set('whatsappPhone', '+256700666777')
            ->call('sendWhatsAppVerificationCode');
        $code = (string) $component->get('whatsappOtpDisplay');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $component
            ->set('whatsappOtpInput', $code)
            ->call('verifyWhatsAppCode')
            ->assertSet('whatsappVerified', true)
            ->call('next'); // whatsapp → plan
        $component->call('next'); // plan → review
        $this->assertSame(
            'review',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );
        $component->call('confirmReview')->assertSet('finished', true);

        $this->school->refresh();
        $this->assertNotNull($this->school->onboarding_finished_at);
        $this->assertTrue(OnboardingStepsService::isOnboardingFinished($this->school));

        $remount = Livewire::test(ManualOnboardingWizard::class);
        $remount->assertSet('finished', true);
        $key = $remount->instance()->steps[$remount->get('stepIndex')]['key'] ?? null;
        $this->assertSame('review', $key);
        $this->assertNotSame('teachers', $key);
    }
}
