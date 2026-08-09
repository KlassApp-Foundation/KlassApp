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

class ManualUiWave3WizardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private Country $uganda;

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

        $this->uganda = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => "Wave3's School",
            'email' => 'wave3@test.sch.ug',
            'phone' => '0700000033',
            'slug' => 'wave3-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Wave3 Admin',
            'email' => 'admin@wave3.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Wave3',
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

    public function test_wizard_route_renders_live_shell(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/onboarding/wizard');

        $response->assertOk();
        $response->assertSee('School setup', false);
        $response->assertSee('data-testid="wizard-nav"', false);
        $response->assertSee('data-testid="wizard-progress"', false);
        $response->assertSee('data-testid="wizard-prev"', false);
        $response->assertSee('data-testid="wizard-next"', false);
    }

    public function test_prev_next_and_progress_indicator_state(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManualOnboardingWizard::class)
            ->assertSet('stepIndex', 0)
            ->assertSee('School name')
            ->assertSeeHtml('data-testid="wizard-progress"')
            ->assertSeeHtml('is-current')
            ->call('next')
            ->assertSet('errorMessage', 'Enter your real school name.')
            ->assertSet('stepIndex', 0)
            ->set('schoolName', 'Wave3 Primary')
            ->call('next')
            ->assertSet('stepIndex', 1)
            ->assertSee('Board / Curriculum')
            ->call('previous')
            ->assertSet('stepIndex', 0)
            ->assertSee('School name')
            ->call('goToStep', 1)
            ->assertSet('stepIndex', 1);
    }

    public function test_whatsapp_next_lands_on_visible_plan_step_not_completion(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);

        $component
            ->set('schoolName', 'Plan Step Academy')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('ministryCode', 'EMIS-PLAN')
            ->call('next')
            ->call('next') // uneb skip
            ->call('next') // academic year
            ->set('className', 'P1')
            ->call('next')
            ->set('subjectName', 'Math')
            ->call('next')
            ->set('teacherName', 'Grace')
            ->set('teacherEmail', 'grace@wave3.sch.ug')
            ->call('next')
            ->call('next') // terms
            ->call('next') // fees
            ->assertSee('WhatsApp verification')
            ->set('whatsappPhone', '+256700111222')
            ->call('next');

        $instance = $component->instance();
        $this->assertSame(12, $instance->stepIndex);
        $this->assertSame('plan_selection', $instance->steps[12]['key']);
        // Livewire test HTML can lag one tick after stepIndex-only updates; re-enter step.
        $component->call('goToStep', 12);
        $component
            ->assertSee('Plan selection')
            ->assertSeeHtml('data-testid="wizard-plan-cards"')
            ->assertSee('Freemium')
            ->assertSee('Recommended to start')
            ->assertSet('finished', false);
        $this->assertNull(
            \App\Models\CurrentPlan::where('school_id', $this->school->id)->first(),
            'Plan must not be silently assigned before the plan step is confirmed'
        );

        $component->call('next')->assertSet('finished', false);

        $instance = $component->instance();
        $this->assertSame('review', $instance->steps[$instance->stepIndex]['key'] ?? null);
        $this->assertNotNull(
            \App\Models\CurrentPlan::where('school_id', $this->school->id)->first()
        );

        $component->call('goToStep', $instance->stepIndex);
        $component
            ->assertSee('Review & confirm')
            ->assertSeeHtml('data-testid="wizard-review"')
            ->assertSee('Plan Step Academy')
            ->assertSee('Freemium');

        $component->call('next')->assertSet('finished', true);
    }

    public function test_completion_screen_is_personalized_from_setup(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);

        $component
            ->set('schoolName', 'Personalized Academy')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('ministryCode', 'EMIS-333')
            ->call('next')
            ->set('unebCenterNumber', 'U333')
            ->call('next')
            ->call('next') // academic year with defaults
            ->set('className', 'P1')
            ->call('next')
            ->set('subjectName', 'Science')
            ->call('next')
            ->set('teacherName', 'Amina Teacher')
            ->set('teacherEmail', 'amina@wave3.sch.ug')
            ->call('next')
            ->call('next') // term defaults
            ->call('next') // fee defaults
            ->set('whatsappPhone', '+256700333444')
            ->call('next');

        $this->assertSame('plan_selection', $component->instance()->steps[$component->instance()->stepIndex]['key'] ?? null);
        $component->call('goToStep', $component->instance()->stepIndex);
        $component->assertSeeHtml('data-testid="wizard-plan-cards"');
        $component->call('next'); // confirm Freemium → review

        $reviewIndex = $component->instance()->stepIndex;
        $this->assertSame('review', $component->instance()->steps[$reviewIndex]['key'] ?? null);
        // Livewire test HTML can lag one tick after stepIndex-only updates; re-enter step.
        $component->call('goToStep', $reviewIndex);

        $component
            ->assertSet('finished', false)
            ->assertSeeHtml('data-testid="wizard-review"')
            ->call('confirmReview')
            ->assertSet('finished', true)
            ->assertSee('Personalized Academy is ready')
            ->assertSeeHtml('data-testid="wizard-completion-suggestions"')
            ->assertSee('Invite more teachers')
            ->assertDontSee('Setup complete! You are all done.');
    }

    public function test_review_preview_shows_entered_data_and_edit_return_preserves_rest(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);

        $component
            ->set('schoolName', 'Preview Academy')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('ministryCode', 'EMIS-PREVIEW')
            ->call('next')
            ->set('unebCenterNumber', 'U999')
            ->call('next')
            ->call('next') // academic year
            ->set('className', 'P2')
            ->call('next')
            ->set('subjectName', 'English')
            ->call('next')
            ->set('teacherName', 'Helen Teacher')
            ->set('teacherEmail', 'helen@wave3.sch.ug')
            ->call('next')
            ->call('next') // terms
            ->call('next') // fees
            ->set('whatsappPhone', '+256700555666')
            ->call('next'); // plan

        $component->call('goToStep', $component->instance()->stepIndex);
        $component->call('next'); // plan → review

        $reviewIndex = $component->instance()->stepIndex;
        $this->assertSame('review', $component->instance()->steps[$reviewIndex]['key'] ?? null);
        $component->call('goToStep', $reviewIndex);

        $component
            ->assertSet('finished', false)
            ->assertSeeHtml('data-testid="wizard-review"')
            ->assertSee('Preview Academy')
            ->assertSee('UNEB')
            ->assertSee('Uganda')
            ->assertSee('EMIS-PREVIEW')
            ->assertSee('U999')
            ->assertSee('P2')
            ->assertSee('ENGLISH') // Subject model accessor uppercases name
            ->assertSee('Helen Teacher')
            ->assertSee('+256700555666')
            ->assertSee('Freemium');

        $planId = \App\Models\CurrentPlan::where('school_id', $this->school->id)->value('plan_id');
        $this->assertNotNull($planId);

        $component
            ->call('editSection', 'school_name')
            ->assertSee('School name');
        $this->assertNotNull($component->get('returnToStepIndex'));
        $component
            ->set('schoolName', 'Preview Academy Renamed')
            ->call('next');

        $component->call('goToStep', $component->instance()->stepIndex);

        $component
            ->assertSeeHtml('data-testid="wizard-review"')
            ->assertSee('Preview Academy Renamed')
            ->assertSee('P2')
            ->assertSee('ENGLISH')
            ->assertSee('Helen Teacher')
            ->assertSee('+256700555666')
            ->assertSee('Freemium');
        $this->assertNull($component->get('returnToStepIndex'));

        $this->assertSame(
            'Preview Academy Renamed',
            $this->school->fresh()->name
        );
        $this->assertSame(
            (int) $planId,
            (int) \App\Models\CurrentPlan::where('school_id', $this->school->id)->value('plan_id')
        );
        $this->assertTrue(
            \App\Models\Subject::where('school_id', $this->school->id)->where('name', 'English')->exists()
            || \App\Models\Subject::where('school_id', $this->school->id)->where('name', 'ENGLISH')->exists()
        );

        $component->call('confirmReview')->assertSet('finished', true);
        $this->assertNotNull(
            \App\Models\CurrentPlan::where('school_id', $this->school->id)->first()
        );
    }

    public function test_previous_from_classes_returns_to_academic_year(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);

        $component
            ->set('schoolName', 'Prev Nav Academy')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('ministryCode', 'EMIS-PREV')
            ->call('next')
            ->call('next') // uneb
            ->call('next'); // academic year → lands on classes

        $component
            ->assertSee('Classes')
            ->call('previous')
            ->assertSee('Academic year')
            ->assertDontSeeHtml('>Classes</h2>');
    }

    public function test_wizard_page_hides_toshi_via_body_class_hook(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/onboarding/wizard');

        $response->assertOk();
        $response->assertSee('toshi-manual-wizard', false);
        $response->assertSee('data-testid="manual-wizard-page"', false);
    }
}
