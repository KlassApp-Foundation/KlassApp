<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
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

        DB::table('plans')->insert([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
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
            ->call('next')
            ->call('next'); // plan

        $component
            ->assertSet('finished', true)
            ->assertSee('Personalized Academy is ready')
            ->assertSeeHtml('data-testid="wizard-completion-suggestions"')
            ->assertSee('Invite more teachers')
            ->assertDontSee('Setup complete! You are all done.');
    }
}
