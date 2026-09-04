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

class WizardPreviousFromTeachersTest extends TestCase
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
            'name' => "Nav's School",
            'email' => 'nav@test.sch.ug',
            'phone' => '0700000055',
            'slug' => 'nav-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Nav Admin',
            'email' => 'admin@nav.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Nav',
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

    private function advanceToTeachers(object $component): void
    {
        $component
            ->set('schoolName', 'Nav Academy')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-NAV')
            ->call('next')
            ->call('next') // uneb
            ->call('next'); // academic year seeds classes/subjects → teachers
    }

    public function test_previous_from_teachers_goes_to_subjects_not_students(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $this->assertSame(
            'teachers',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );

        $component->call('previous');

        $afterKey = $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null;
        $this->assertSame('subjects', $afterKey);
        $this->assertNotSame('students', $afterKey);
    }

    public function test_remount_lands_on_incomplete_optional_teachers_not_skipping_ahead(): void
    {
        $this->actingAs($this->admin);

        // Complete blocking steps through subjects via wizard, leave teachers empty.
        $setup = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($setup);
        $this->assertSame(
            'teachers',
            $setup->instance()->steps[$setup->get('stepIndex')]['key'] ?? null
        );

        // Fresh mount (reload) must resume at first incomplete step including optional ones.
        $remount = Livewire::test(ManualOnboardingWizard::class);
        $key = $remount->instance()->steps[$remount->get('stepIndex')]['key'] ?? null;

        $this->assertSame('teachers', $key);
        $this->assertNotSame('terms', $key);
        $this->assertNotSame('students', $key);
    }
}
