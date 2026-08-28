<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regression: confirmOnboarding must persist students/terms/fees collected via
 * the real form flow (saveStudent, saveFee, handleTerms), not only when tests
 * bypass collection with set('actionData', …) before commit.
 */
class ToshiConfirmOnboardingCollectedDataTest extends TestCase
{
    use RefreshDatabase;

    private const TERMS_STEP = 11;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('plans')->insert([
            ['id' => 1, 'cycle' => 30, 'name' => 'Freemium', 'display_name' => 'Freemium', 'order' => 1, 'is_active' => 1, 'amount' => 0, 'no_of_students' => 0, 'no_of_users' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Collected Data Commit School',
            'email' => 'collected-commit@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'collected-data-commit-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'school_category' => 'primary',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'School Admin',
            'email' => 'admin@collected-commit.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'School Admin',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'description' => 'Current Academic Year',
            'type' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        foreach (['P1', 'P7'] as $className) {
            $section = Section::create([
                'school_id' => $this->school->id,
                'name' => $className,
                'status' => 1,
            ]);

            StandardLink::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'status' => 1,
            ]);
        }
    }

    private function findStudentByDisplayName(string $displayName): ?User
    {
        $profile = Userprofile::where('school_id', $this->school->id)
            ->where('usergroup_id', 6)
            ->whereRaw('UPPER(firstname) = ?', [strtoupper($displayName)])
            ->first();

        return $profile ? User::find($profile->user_id) : null;
    }

    /**
     * Collect two students through the same Livewire methods the UI uses.
     * Never sets actionData or studentList directly.
     */
    private function collectTwoStudentsViaForm($component): void
    {
        $component->call('showStudentFormFn');

        $component->set('studentFormName', 'Grace Nakato');
        $component->set('studentFormClass', 'P1');
        $component->call('saveStudent');

        $component->set('studentFormName', 'Samuel Okello');
        $component->set('studentFormClass', 'P7');
        $component->call('saveStudent');

        $component->call('doneStudents');
    }

    private function initializeDefaultTerms($component): void
    {
        $component->set('step', self::TERMS_STEP);
        $component->set('substep', 0);

        $method = new ReflectionMethod(AgentToshi::class, 'callStepHandler');
        $method->setAccessible(true);
        $method->invoke($component->instance(), '');

        $component->call('confirmYes');
    }

    /** @test */
    public function complete_mode_confirm_onboarding_persists_students_collected_via_save_student_flow(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('schoolName', $this->school->name);

        $this->collectTwoStudentsViaForm($component);

        $this->assertCount(2, $component->get('actionData')['students'] ?? []);

        $component->call('confirmOnboarding');

        $component->assertSet('reviewData.committed', true);

        $this->assertNotNull($this->findStudentByDisplayName('Grace Nakato'));
        $this->assertNotNull($this->findStudentByDisplayName('Samuel Okello'));
        $this->assertEquals(2, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
    }

    /** @test */
    public function complete_mode_confirm_onboarding_restores_students_from_session_when_wire_payload_cleared(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('schoolName', $this->school->name);

        $this->collectTwoStudentsViaForm($component);

        // Simulate nested actionData lost on the wire before commit (prod E2E symptom).
        $component->set('actionData', []);
        $component->set('studentList', []);

        $component->call('confirmOnboarding');

        $component->assertSet('reviewData.committed', true);
        $this->assertEquals(2, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
    }

    /** @test */
    public function complete_mode_confirm_onboarding_persists_terms_after_confirm_yes_flow(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('schoolName', $this->school->name);

        $this->initializeDefaultTerms($component);

        $this->assertNotEmpty($component->get('terms'));

        // Simulate terms dropped from wire payload before commit.
        $component->set('terms', []);

        $component->call('confirmOnboarding');

        $component->assertSet('reviewData.committed', true);
        $this->assertEquals(3, AcademicTerm::where('school_id', $this->school->id)->count());
        $this->assertNotNull(AcademicTerm::where('school_id', $this->school->id)->where('name', 'Term I')->first());
    }

    /** @test */
    public function complete_mode_confirm_onboarding_persists_fees_from_action_data_without_done_fees(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('schoolName', $this->school->name);

        $component->call('showFeeFormFn');
        $component->set('feeFormName', 'Tuition');
        $component->set('feeFormAmount', '500000');
        $component->set('feeFormClass', 'P1');
        $component->call('saveFee');

        // Intentionally skip doneFees() — fees must still commit from actionData.
        $this->assertNotEmpty($component->get('actionData')['fees'] ?? []);
        $this->assertEmpty($component->get('fees'));

        $component->call('confirmOnboarding');

        $component->assertSet('reviewData.committed', true);

        $fee = FeesCategories::where('school_id', $this->school->id)
            ->where('name', 'Tuition')
            ->first();

        $this->assertNotNull($fee, 'Tuition fee must be written from actionData on commit');
        $this->assertEquals(500000.0, (float) $fee->amount);
    }

    /** @test */
    public function complete_mode_full_collection_flow_persists_students_terms_and_fees_on_commit(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('schoolName', $this->school->name);

        $this->collectTwoStudentsViaForm($component);
        $this->initializeDefaultTerms($component);

        $component->call('showFeeFormFn');
        $component->set('feeFormName', 'Tuition');
        $component->set('feeFormAmount', '500000');
        $component->set('feeFormClass', 'P1');
        $component->call('saveFee');
        $component->call('doneFees');

        // Clear all canonical + nested state to force session/resolve path.
        $component->set('actionData', []);
        $component->set('studentList', []);
        $component->set('terms', []);
        $component->set('fees', []);

        $component->call('confirmOnboarding');

        $component->assertSet('reviewData.committed', true);
        $this->assertEquals(2, User::where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $this->assertEquals(3, AcademicTerm::where('school_id', $this->school->id)->count());
        $this->assertGreaterThanOrEqual(1, FeesCategories::where('school_id', $this->school->id)->where('name', 'Tuition')->count());
    }

    /** @test */
    public function create_mode_confirm_onboarding_persists_students_collected_via_save_student_flow(): void
    {
        $superadmin = User::create([
            'school_id' => null,
            'usergroup_id' => 1,
            'name' => 'Super Admin',
            'email' => 'super@collected-commit.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $this->actingAs($superadmin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'create');
        $component->set('schoolName', 'Create Flow Collected School');
        $component->set('schoolEmail', 'create-flow@collected.sch.ug');
        $component->set('schoolPhone', '0700111333');
        $component->set('adminName', 'Create Admin');
        $component->set('adminEmail', 'create-admin@collected.sch.ug');
        $component->set('adminPassword', 'password123');
        $component->set('schoolType', 'primary');
        $component->set('curriculum', 'uneb');
        $component->set('selectedPlanId', 1);
        $component->set('standards', [['name' => 'P1']]);

        $component->call('showStudentFormFn');
        $component->set('studentFormName', 'Alice Create Flow');
        $component->set('studentFormClass', 'P1');
        $component->call('saveStudent');
        $component->call('doneStudents');

        $component->set('actionData', []);
        $component->set('studentList', []);

        $component->call('confirmOnboarding');

        $component->assertSet('reviewData.committed', true);

        $schoolId = (int) $component->get('schoolId');
        $this->assertGreaterThan(0, $schoolId);

        $profile = Userprofile::where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->whereRaw('UPPER(firstname) = ?', ['ALICE CREATE FLOW'])
            ->first();

        $this->assertNotNull($profile, 'Create-mode student collected via saveStudent must exist after commit');
    }
}
