<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
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
 * Academic-year step uses native date inputs in DS date-picker chrome.
 * Field names + validation (end after start) must stay intact.
 */
class WizardAcademicYearDatePickerTest extends TestCase
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
            'name' => "DatePicker's School",
            'email' => 'datepicker@test.sch.ug',
            'phone' => '0700000055',
            'slug' => 'datepicker-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'DatePicker Admin',
            'email' => 'admin@datepicker.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Date',
            'lastname' => 'Picker',
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

    public function test_academic_year_step_renders_ds_date_pickers(): void
    {
        $this->actingAs($this->admin);

        $component = $this->advanceToAcademicYearStep();
        $idx = $component->instance()->stepIndex;
        $this->assertSame('academic_year', $component->instance()->steps[$idx]['key'] ?? null);

        // Livewire test HTML can lag one tick after stepIndex-only updates; re-enter step.
        $component->call('goToStep', $idx);

        $component
            ->assertSee('Academic year')
            ->assertSeeHtml('data-testid="wizard-ay-date-range"')
            ->assertSeeHtml('id="wizard-ay-start"')
            ->assertSeeHtml('id="wizard-ay-end"')
            ->assertSeeHtml('type="date"')
            ->assertSeeHtml('ds-date-picker')
            ->assertSeeHtml('wire:model="academicYearStart"')
            ->assertSeeHtml('wire:model="academicYearEnd"');
    }

    public function test_date_selection_persists_academic_years_fields(): void
    {
        $this->actingAs($this->admin);

        $component = $this->advanceToAcademicYearStep();

        $component
            ->set('academicYearDescription', '2026 Academic Year')
            ->set('academicYearStart', '2026-02-01')
            ->set('academicYearEnd', '2026-11-30')
            ->call('next')
            ->assertSet('errorMessage', '');

        $year = AcademicYear::where('school_id', $this->school->id)->first();
        $this->assertNotNull($year);
        $this->assertSame('2026 Academic Year', $year->description);
        $this->assertSame('2026-02-01', $year->start_date->toDateString());
        $this->assertSame('2026-11-30', $year->end_date->toDateString());
        $this->assertSame('2026', $year->name);
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        $this->actingAs($this->admin);

        $component = $this->advanceToAcademicYearStep();

        $component
            ->set('academicYearStart', '2026-06-01')
            ->set('academicYearEnd', '2026-01-15')
            ->call('next');

        $this->assertNotSame('', (string) $component->get('errorMessage'));
        $this->assertNull(AcademicYear::where('school_id', $this->school->id)->first());

        // Same day is also not "after"
        $component
            ->set('academicYearStart', '2026-06-01')
            ->set('academicYearEnd', '2026-06-01')
            ->call('next');

        $this->assertNotSame('', (string) $component->get('errorMessage'));
        $this->assertNull(AcademicYear::where('school_id', $this->school->id)->first());
    }

    private function advanceToAcademicYearStep()
    {
        return Livewire::test(ManualOnboardingWizard::class)
            ->set('schoolName', 'DatePicker Primary')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('ministryCode', 'EMIS-DP-1')
            ->call('next')
            ->set('unebCenterNumber', '')
            ->call('next')
            ->assertSee('Academic year');
    }
}
