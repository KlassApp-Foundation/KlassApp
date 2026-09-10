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
use App\Services\SchoolCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class WizardSchoolCategoryNextTest extends TestCase
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
            'name' => 'Category Block School',
            'email' => 'category-block@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'category-block-school',
            'status' => 1,
            'curriculum' => null,
            'school_category' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Category Admin',
            'email' => 'admin@category-block.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Category',
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

    public function test_select_school_category_sets_property_like_select_plan(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManualOnboardingWizard::class)
            ->call('selectSchoolCategory', 'primary')
            ->assertSet('schoolCategory', 'primary')
            ->assertSet('errorMessage', '');
    }

    public function test_select_school_category_rejects_unknown_key(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManualOnboardingWizard::class)
            ->call('selectSchoolCategory', 'not-a-real-category')
            ->assertSet('schoolCategory', '')
            ->assertSet('errorMessage', 'Please choose a school category.');
    }

    public function test_next_on_category_without_selection_stays_and_errors(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);

        $component
            ->set('schoolName', 'Category Block School')
            ->call('next')
            ->set('studentSize', 'Under 100 students')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next');

        $this->assertSame(
            'school_category',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );

        $component->call('next');

        $this->assertSame(
            'school_category',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null,
            'Next must not advance when schoolCategory is empty'
        );
        $this->assertSame('', $component->get('schoolCategory'));
        $this->school->refresh();
        $this->assertNull($this->school->school_category);
    }

    public function test_select_school_category_then_next_advances_to_emis(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);

        $component
            ->set('schoolName', 'Category Block School')
            ->call('next')
            ->set('studentSize', 'Under 100 students')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next');

        $this->assertSame(
            'school_category',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );

        // Reproduce the card click path (not ->set()), matching wire:click="selectSchoolCategory(...)"
        $component
            ->call('selectSchoolCategory', 'primary')
            ->assertSet('schoolCategory', 'primary')
            ->call('next');

        $this->assertSame(
            'emis',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null,
            'After selecting Primary, Next must advance to EMIS (step after school_category)'
        );

        $this->school->refresh();
        $this->assertSame('primary', $this->school->school_category);
        $this->assertArrayHasKey('primary', SchoolCategorySeeder::CATEGORIES);
    }
}
