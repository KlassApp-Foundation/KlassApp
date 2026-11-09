<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The step card, the side panel and the URL must describe the same step, and the
 * page chrome (school name, academic-year selector) must refresh after the save
 * that changes it. Both were seen on the Riverside walkthrough.
 */
class WizardStepCountersAndChromeTest extends TestCase
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

        Country::create(['name' => 'Uganda', 'short_name' => 'UG', 'status' => 1, 'order' => 1]);

        $this->school = School::create([
            'name' => "Counter's School",
            'email' => 'counters@test.sch.ug',
            'phone' => '0700000044',
            'slug' => 'counters-school',
            'status' => 1,
            'curriculum' => null,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Counters Admin',
            'email' => 'counters-admin@test.sch.ug',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Counters',
            'lastname' => 'Admin',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_step_card_and_side_panel_report_the_same_total(): void
    {
        $html = Livewire::test(ManualOnboardingWizard::class)->html();

        $this->assertSame(1, preg_match('/Step (\d+) of (\d+)/', $html, $card), 'step card not found');
        $this->assertSame(1, preg_match('/Setup (\d+) of (\d+)/', $html, $panel), 'side panel not found');

        $this->assertSame($card[2], $panel[2], 'card and side panel disagree on the number of steps');
        $this->assertSame('1', $card[1]);
    }

    public function test_side_panel_now_matches_the_step_on_screen(): void
    {
        $html = Livewire::test(ManualOnboardingWizard::class)->html();

        $this->assertSame(1, preg_match('/data-testid="wizard-step-title">\s*([^<]+)/', $html, $title));
        $this->assertSame(1, preg_match('/Now:\s*([^<]+)/', $html, $now));

        $this->assertSame(trim($title[1]), trim($now[1]));
    }

    public function test_url_follows_the_current_step(): void
    {
        Livewire::test(ManualOnboardingWizard::class)
            ->assertSet('stepKey', 'school_name')
            ->assertDispatched('wizard-url', step: 'school_name');
    }

    public function test_saving_the_name_asks_for_a_full_reload(): void
    {
        $component = Livewire::test(ManualOnboardingWizard::class)
            ->set('schoolName', 'Riverside Academy')
            ->call('next');

        $this->assertSame('Riverside Academy', $this->school->fresh()->name);
        $this->assertStringContainsString(
            'window.location.reload',
            json_encode($component->effects ?? []),
            'the header only re-reads the name on a full page load',
        );
    }

    public function test_saved_school_name_reaches_the_page_header(): void
    {
        Livewire::test(ManualOnboardingWizard::class)
            ->set('schoolName', 'Riverside Academy')
            ->call('next');

        $this->assertSame('Riverside Academy', $this->school->fresh()->name);

        $this->get(route('admin.onboarding.wizard', ['step' => 'student_size']))
            ->assertOk()
            ->assertSee('Riverside Academy');
    }

    public function test_academic_year_save_reloads_and_the_year_is_listed(): void
    {
        $component = Livewire::withQueryParams(['step' => 'academic_year'])
            ->test(ManualOnboardingWizard::class)
            ->assertSet('stepKey', 'academic_year')
            ->set('academicYearStart', '2026-01-01')
            ->set('academicYearEnd', '2026-12-31')
            ->set('academicYearDescription', '2026')
            ->call('next');

        $this->assertStringContainsString(
            'window.location.reload',
            json_encode($component->effects ?? []),
            'the year selector only re-reads its list on a full page load',
        );

        $year = AcademicYear::where('school_id', $this->school->id)->first();
        $this->assertNotNull($year, 'academic year was not saved');

        $listing = $this->get('/admin/list/academicyear');
        $listing->assertOk();
        $this->assertStringContainsString((string) $year->name, $listing->getContent());
    }
}
