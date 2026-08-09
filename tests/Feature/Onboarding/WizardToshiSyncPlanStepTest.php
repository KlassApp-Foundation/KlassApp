<?php

namespace Tests\Feature\Onboarding;

use App\Helpers\OnboardingHelper;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Deploy-blocker fixes: hide Toshi on manual wizard, reconcile Completing Setup
 * after onboarding completes, keep plan selection as a visible last step.
 */
class WizardToshiSyncPlanStepTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private Plan $freemium;

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
            'name' => "Sync's School",
            'email' => 'sync@test.sch.ug',
            'phone' => '0700000044',
            'slug' => 'sync-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Sync Admin',
            'email' => 'admin@sync.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Sync',
            'lastname' => 'Admin',
        ]);

        $this->freemium = Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);

        Plan::create([
            'name' => 'Growth',
            'display_name' => 'Growth',
            'cycle' => 30,
            'no_of_students' => 500,
            'no_of_users' => 50,
            'amount' => 35,
            'order' => 2,
            'is_active' => 1,
        ]);
    }

    public function test_entering_wizard_injects_toshi_hide_hook(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin/onboarding/wizard')
            ->assertOk()
            ->assertSee('toshi-manual-wizard', false)
            ->assertSee('data-testid="manual-wizard-page"', false);
    }

    public function test_toshi_exits_completing_setup_when_onboarding_is_complete(): void
    {
        $this->seedFullyOnboardedSchool();
        $this->actingAs($this->admin);

        // Stale session: still "Completing Setup" with early step index (the walkthrough bug).
        session(['toshi_state' => [
            'mode' => 'complete',
            'scope' => 'school',
            'schoolId' => $this->school->id,
            'step' => 0,
            'substep' => 0,
            'messages' => [
                ['role' => 'bot', 'text' => "What's the real name of your school?"],
            ],
        ]]);

        $this->assertEmpty(OnboardingHelper::getMissingSteps($this->school->id, $this->admin->id));

        Livewire::test(AgentToshi::class)
            ->assertSet('mode', 'assistant')
            ->assertSet('step', 99)
            ->assertDontSee('Completing Setup')
            ->assertSee('Toshi Agent');
    }

    public function test_manual_wizard_finish_dispatches_toshi_sync(): void
    {
        $this->actingAs($this->admin);

        // Partial: leave only plan incomplete so one Next finishes.
        $this->seedContentThroughWhatsApp();

        session(['toshi_state' => [
            'mode' => 'complete',
            'scope' => 'school',
            'schoolId' => $this->school->id,
            'step' => 0,
            'substep' => 0,
            'messages' => [['role' => 'bot', 'text' => 'stale']],
        ]]);

        Livewire::test(ManualOnboardingWizard::class)
            ->assertSee('Plan selection')
            ->call('next')
            ->assertSet('finished', true)
            ->assertDispatched('manual-onboarding-finished');

        Livewire::test(AgentToshi::class)
            ->call('syncOnboardingAfterManualWizard')
            ->assertSet('mode', 'assistant')
            ->assertSet('step', 99);
    }

    public function test_plan_selection_is_visible_with_freemium_default(): void
    {
        $this->actingAs($this->admin);
        $this->seedContentThroughWhatsApp();

        Livewire::test(ManualOnboardingWizard::class)
            ->assertSee('Plan selection')
            ->assertSeeHtml('data-testid="wizard-plan-cards"')
            ->assertSet('selectedPlanId', $this->freemium->id)
            ->assertSee('Recommended to start')
            ->assertSet('finished', false);

        $this->assertNull(CurrentPlan::where('school_id', $this->school->id)->first());
    }

    private function seedContentThroughWhatsApp(): void
    {
        $this->school->update([
            'name' => 'Sync Primary',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'ministry_code' => 'EMIS-SYNC',
            'uneb_center_number' => '',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $phase = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => '1',
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'status' => '1',
        ]);
        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $phase->id,
            'section_id' => $section->id,
            'status' => '1',
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $phase->id,
            'section_id' => $section->id,
            'name' => 'Math',
        ]);

        $teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Grace',
            'email' => 'grace@sync.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $link->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'starts_on' => '2026-01-15',
            'ends_on' => '2026-04-15',
            'status' => 'current',
        ]);
        FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $phase->id,
            'name' => 'Tuition',
            'amount' => 100000,
        ]);
        WhatsAppUser::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'phone' => '+256700111222',
            'opted_in' => true,
            'verified_at' => now(),
        ]);
    }

    private function seedFullyOnboardedSchool(): void
    {
        $this->seedContentThroughWhatsApp();
        CurrentPlan::create([
            'school_id' => $this->school->id,
            'plan_id' => $this->freemium->id,
            'status' => 'running',
        ]);
    }
}
