<?php

namespace Tests\Feature\Onboarding;

use App\Enums\ToshiMode;
use App\Livewire\ManualOnboardingWizard;
use App\Models\EarlyAccessRequest;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Onboarding\Steps\StepRegistry;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use App\Services\SchoolInviteService;
use App\Services\Toshi\ToshiOnboardingPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Real schools (is_test = 0) use the Wizard until Toshi onboarding matches it.
 * Test schools keep the chat path. TOSHI_ONBOARDING_V2 is not part of this gate.
 */
class ToshiPathHiddenForRealSchoolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_a_real_school_sees_toshi_disabled_and_is_sent_to_the_wizard(): void
    {
        $onboardingV2 = config('toshi.onboarding_v2');
        ['school' => $school, 'user' => $user] = $this->schoolAdmin(isTest: false);

        $choice = $this->actingAs($user)->get(route('onboarding.choice'));

        $choice->assertOk();
        $choice->assertSee('Set up by chatting with Toshi', false);
        $choice->assertSee('data-testid="onboarding-choice-toshi-soon"', false);
        $choice->assertSee('Coming soon', false);
        $choice->assertSee('disabled', false);
        $choice->assertDontSee('name="path" value="toshi"', false);
        $choice->assertSee('data-testid="onboarding-choice-wizard"', false);

        $destination = OnboardingEngine::setupDestination($school, $user->id);

        $this->actingAs($user)
            ->post(route('onboarding.choice.store'), ['path' => 'toshi'])
            ->assertRedirect($destination)
            ->assertSessionHas('successmessage', ToshiOnboardingPath::WIZARD_FLASH);

        $school->refresh();
        $this->assertSame(ToshiMode::Preview, $school->toshi_mode);
        $this->assertSame(0, (int) $school->toshi_enabled);

        $this->actingAs($user)
            ->get(route('onboarding.choice', ['path' => 'toshi']))
            ->assertRedirect($destination)
            ->assertSessionHas('successmessage', ToshiOnboardingPath::WIZARD_FLASH);

        $this->actingAs($user)
            ->get('/admin/dashboard?toshi_onboarding=1')
            ->assertRedirect($destination);

        $this->actingAs($user)
            ->get('/welcome')
            ->assertRedirect($destination)
            ->assertSessionHas('successmessage', ToshiOnboardingPath::WIZARD_FLASH);

        $this->assertSame($onboardingV2, config('toshi.onboarding_v2'));
    }

    public function test_invite_claim_page_shows_toshi_as_coming_soon(): void
    {
        $issuer = User::create([
            'name' => 'Platform Admin',
            'email' => 'platform-path@klassapp.test',
            'password' => bcrypt('not-a-public-password'),
            'usergroup_id' => 1,
            'status' => 'active',
        ]);
        $request = EarlyAccessRequest::create([
            'school_name' => 'Greenfield Primary',
            'contact_name' => 'Grace Nakato',
            'contact_role' => 'Head Teacher',
            'email' => 'adopter-path@school.test',
            'phone' => '0701234567',
            'country' => 'Uganda',
            'approx_students' => 420,
            'consented_at' => now(),
        ]);
        ['token' => $token] = SchoolInviteService::issue($request, $issuer);

        $this->get(route('school-invite.show', $token))
            ->assertOk()
            ->assertSee('data-testid="invite-onboarding-paths"', false)
            ->assertSee('data-testid="onboarding-choice-toshi"', false)
            ->assertSee('data-testid="onboarding-choice-toshi-soon"', false)
            ->assertSee('Set up by chatting with Toshi', false)
            ->assertSee('Coming soon', false)
            ->assertSee('disabled', false);
    }

    public function test_a_test_school_can_still_choose_toshi(): void
    {
        ['school' => $school, 'user' => $user] = $this->schoolAdmin(isTest: true);

        $choice = $this->actingAs($user)->get(route('onboarding.choice'));

        $choice->assertOk();
        $choice->assertSee('name="path" value="toshi"', false);
        $choice->assertSee('Set up by chatting with Toshi', false);
        $choice->assertDontSee('data-testid="onboarding-choice-toshi-soon"', false);

        $this->actingAs($user)
            ->post(route('onboarding.choice.store'), ['path' => 'toshi'])
            ->assertRedirect('/admin/dashboard')
            ->assertSessionHas('open_toshi_onboarding', true);

        $school->refresh();
        $this->assertSame(ToshiMode::Onboarding, $school->toshi_mode);
        $this->assertSame(0, (int) $school->toshi_enabled);

        $this->actingAs($user)
            ->get(route('onboarding.choice', ['path' => 'toshi']))
            ->assertRedirect('/admin/dashboard')
            ->assertSessionHas('open_toshi_onboarding', true);
    }

    public function test_a_real_school_midway_through_toshi_keeps_completed_steps_in_the_wizard(): void
    {
        ['school' => $school, 'user' => $user] = $this->schoolAdmin(isTest: false);
        $school->forceFill([
            'name' => 'Greenfield Primary',
            'student_size' => 'Up to 500',
            'toshi_mode' => ToshiMode::Onboarding,
            'toshi_enabled' => 0,
        ])->save();

        $this->assertTrue(OnboardingStepsService::isStepComplete('school_name', $school->fresh(), $user->id));
        $this->assertTrue(OnboardingStepsService::isStepComplete('student_size', $school->fresh(), $user->id));

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertRedirect(OnboardingEngine::setupDestination($school->fresh(), $user->id))
            ->assertSessionHas('successmessage', ToshiOnboardingPath::WIZARD_FLASH);

        $this->actingAs($user)
            ->get(route('admin.onboarding.wizard'))
            ->assertOk()
            ->assertSee('data-testid="wizard-step-title"', false)
            ->assertSee('Country', false)
            ->assertSee(ToshiOnboardingPath::WIZARD_FLASH, false);

        $school->refresh();
        $this->assertSame('Greenfield Primary', $school->name);
        $this->assertSame('Up to 500', $school->student_size);
        $this->assertSame(ToshiMode::Preview, $school->toshi_mode);
        $this->assertTrue(OnboardingStepsService::isStepComplete('school_name', $school, $user->id));
        $this->assertTrue(OnboardingStepsService::isStepComplete('student_size', $school, $user->id));
        $this->assertFalse(OnboardingStepsService::isStepComplete('country', $school, $user->id));

        foreach (app(StepRegistry::class)->forSchool($school) as $step) {
            if (! in_array($step->key(), ['school_name', 'student_size'], true)) {
                continue;
            }

            $this->assertTrue(
                $step->isComplete($school, $user->id),
                $step->key().' stays complete for the Wizard because it uses the same step record as Toshi',
            );
        }

        $wizard = Livewire::actingAs($user)->test(ManualOnboardingWizard::class);
        $steps = collect($wizard->get('steps'));
        $this->assertTrue((bool) $steps->firstWhere('key', 'school_name')['is_complete']);
        $this->assertTrue((bool) $steps->firstWhere('key', 'student_size')['is_complete']);
        $this->assertSame('country', $steps[$wizard->get('stepIndex')]['key']);
    }

    /**
     * @return array{school: School, user: User}
     */
    private function schoolAdmin(bool $isTest): array
    {
        $school = School::create([
            'name' => "Path School",
            'email' => ($isTest ? 'test' : 'real').'-path@school.test',
            'phone' => $isTest ? '0700000101' : '0700000102',
            'slug' => $isTest ? 'path-test-school' : 'path-real-school',
            'status' => 1,
            'toshi_mode' => ToshiMode::Preview,
            'toshi_enabled' => 0,
        ]);
        $school->forceFill(['is_test' => $isTest ? 1 : 0])->save();

        $user = User::create([
            'name' => 'Path Admin',
            'email' => ($isTest ? 'test' : 'real').'-admin@school.test',
            'password' => bcrypt('not-a-public-password'),
            'usergroup_id' => 3,
            'school_id' => $school->id,
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'usergroup_id' => 3,
            'firstname' => 'Path',
            'lastname' => 'Admin',
        ]);

        return ['school' => $school->fresh(), 'user' => $user];
    }
}
