<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingNameListExtractor;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Confirmed wizard + Toshi parity bugs before production cutover:
 * plan empty-state contradiction, specific finish errors, students Continue gate,
 * gender + school_student_id through commitAll, Toshi plan empty messaging.
 */
class WizardToshiParityBugsTest extends TestCase
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
            'name' => 'Parity Bugs School',
            'email' => 'parity-bugs@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'parity-bugs-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'ministry_code' => 'EMIS-PARITY',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Parity Admin',
            'email' => 'admin@parity-bugs.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Parity',
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
    }

    public function test_wizard_plan_empty_state_does_not_also_demand_selection(): void
    {
        Plan::query()->update(['is_active' => 0]);
        $this->seedBlockingStepsExceptPlan();
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->goToStepKey($component, 'plan_selection');

        $component
            ->assertSeeHtml('data-testid="wizard-plan-empty"')
            ->assertSee('No plans are available yet. Contact support.')
            ->assertDontSee('Select a plan to continue.')
            ->call('next')
            ->assertSet('errorMessage', 'No plans are available yet. Contact support.')
            ->assertDontSee('Select a plan to continue.');
    }

    public function test_wizard_finish_lists_exact_incomplete_steps(): void
    {
        $this->actingAs($this->admin);

        // Fresh school: many blocking steps incomplete; land on review and confirm.
        $component = Livewire::test(ManualOnboardingWizard::class);
        $reviewIndex = null;
        foreach ($component->instance()->steps as $i => $step) {
            if (($step['key'] ?? '') === 'review') {
                $reviewIndex = $i;
                break;
            }
        }
        $this->assertNotNull($reviewIndex);

        $blocking = OnboardingStepsService::blockingIncompleteSteps($this->school->fresh(), $this->admin->id);
        $this->assertNotEmpty($blocking);
        $expectedLabels = collect($blocking)->pluck('label')->implode(', ');

        $component
            ->call('goToStep', $reviewIndex)
            ->call('confirmReview')
            ->assertSet('finished', false)
            ->assertSet(
                'errorMessage',
                "Finish these incomplete steps before creating your school: {$expectedLabels}."
            )
            ->assertDontSee('Finish the remaining setup steps before creating your school.');
    }

    public function test_wizard_students_continue_with_zero_requires_explicit_skip(): void
    {
        $this->seedThroughTeachers();
        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->goToStepKey($component, 'students');

        $component
            ->assertSeeHtml('data-testid="wizard-students-bulk"')
            ->call('next')
            ->assertSet(
                'errorMessage',
                'Add at least one student, or click “Skip for now” if you’ll enrol later.'
            );

        $this->assertSame('students', $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null);

        $component
            ->call('skipOptionalStep')
            ->assertSet('errorMessage', '');

        $this->assertNotSame('students', $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null);
    }

    public function test_toshi_commit_all_passes_gender_and_school_student_id(): void
    {
        $this->seedClassStructure('P.7');
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('schoolName', $this->school->name);
        $component->set('standards', [['name' => 'P.7']]);
        $component->set('subjects', []);
        $component->set('teacherList', []);
        $component->set('teacherLinks', []);
        $component->set('terms', []);
        $component->set('studentList', ['Ada Lovelace']);
        $component->set('actionData', [
            'students' => [[
                'name' => 'Ada Lovelace',
                'class' => 'P.7',
                'gender' => 'female',
                'school_student_id' => 'ADM-P7-001',
                'board_registration_number' => 'U1234/567',
            ]],
        ]);
        $component->call('commit');

        $this->assertTrue((bool) ($component->get('reviewData')['committed'] ?? false), 'Commit must succeed');

        $student = User::query()
            ->where('school_id', $this->school->id)
            ->where('usergroup_id', 6)
            ->where('name', 'Ada Lovelace')
            ->first();
        $this->assertNotNull($student);

        $profile = Userprofile::where('user_id', $student->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame('female', $profile->gender);

        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic);
        $this->assertSame('ADM-P7-001', $academic->school_student_id);
        $this->assertSame('U1234/567', $academic->board_registration_number);
    }

    public function test_toshi_commit_all_keeps_lin_and_school_student_id_distinct(): void
    {
        $this->seedClassStructure('P1');
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('schoolName', $this->school->name);
        $component->set('standards', [['name' => 'P1']]);
        $component->set('subjects', []);
        $component->set('teacherList', []);
        $component->set('teacherLinks', []);
        $component->set('terms', []);
        $component->set('studentList', ['Both Ids Student']);
        $component->set('actionData', [
            'students' => [[
                'name' => 'Both Ids Student',
                'class' => 'P1',
                'gender' => 'female',
                'school_student_id' => 'SCH-INTERNAL-1',
                'lin' => 'UG123456789012',
            ]],
        ]);
        $component->call('commit');

        $this->assertTrue((bool) ($component->get('reviewData')['committed'] ?? false));

        $student = User::query()
            ->where('school_id', $this->school->id)
            ->where('usergroup_id', 6)
            ->where('name', 'Both Ids Student')
            ->first();
        $this->assertNotNull($student);

        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic);
        $this->assertSame('SCH-INTERNAL-1', $academic->school_student_id);
        $this->assertSame('UG123456789012', $academic->lin);
        $this->assertNotSame($academic->school_student_id, $academic->lin);

        $profile = Userprofile::where('user_id', $student->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame('UG123456789012', $profile->LIN ?? $profile->getAttribute('LIN'));
    }

    public function test_toshi_maps_lin_upload_key_to_lin_column_not_school_student_id(): void
    {
        $this->seedClassStructure('P1');
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('schoolName', $this->school->name);
        $component->set('standards', [['name' => 'P1']]);
        $component->set('subjects', []);
        $component->set('teacherList', []);
        $component->set('teacherLinks', []);
        $component->set('terms', []);
        $component->set('studentList', ['Lin Student']);
        $component->set('actionData', [
            'students' => [[
                'name' => 'Lin Student',
                'class' => 'P1',
                'gender' => 'male',
                'lin' => 'LIN-999',
            ]],
        ]);
        $component->call('commit');

        $this->assertTrue((bool) ($component->get('reviewData')['committed'] ?? false));

        $student = User::query()
            ->where('school_id', $this->school->id)
            ->where('usergroup_id', 6)
            ->where('name', 'Lin Student')
            ->first();
        $this->assertNotNull($student);
        $this->assertSame('male', Userprofile::where('user_id', $student->id)->value('gender'));
        $academic = StudentAcademic::where('user_id', $student->id)->first();
        $this->assertNotNull($academic);
        $this->assertSame('LIN-999', $academic->lin);
        $this->assertNull($academic->school_student_id);
    }

    public function test_name_list_extractor_maps_lin_and_school_student_id_separately(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'lin-upload-');
        $csv = $tmp.'.csv';
        rename($tmp, $csv);
        file_put_contents(
            $csv,
            "Name,Class,Gender,School Student ID,LIN\nBoth Kid,P1,female,ADM-42,EMIS-42\n"
        );

        try {
            $rows = app(OnboardingNameListExtractor::class)->extractNamesFromFile($csv, 'csv');
            $this->assertCount(1, $rows);
            $this->assertSame('Both Kid', $rows[0]['name']);
            $this->assertSame('ADM-42', $rows[0]['school_student_id']);
            $this->assertSame('EMIS-42', $rows[0]['lin']);
            $this->assertSame('female', strtolower((string) $rows[0]['gender']));
        } finally {
            @unlink($csv);
        }
    }

    public function test_toshi_blade_has_plan_empty_and_student_id_fields(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/agent-toshi.blade.php'));
        $this->assertStringContainsString('data-testid="toshi-plan-empty"', $blade);
        $this->assertStringContainsString('No plans are available yet. Contact support.', $blade);
        $this->assertStringContainsString('data-testid="toshi-student-gender"', $blade);
        $this->assertStringContainsString('data-testid="toshi-student-school-id"', $blade);
        $this->assertStringContainsString('data-testid="toshi-student-lin"', $blade);
        $this->assertStringContainsString('data-testid="toshi-student-board-reg"', $blade);
        $this->assertStringNotContainsString(
            "OnboardingEngine::saveStudents doesn't use them",
            file_get_contents(app_path('Livewire/AgentToshi.php'))
        );
    }

    public function test_toshi_prompt_plan_selection_empty_message(): void
    {
        Plan::query()->update(['is_active' => 0]);
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $instance = $component->instance();
        $ref = new \ReflectionMethod(AgentToshi::class, 'promptPlanSelection');
        $ref->setAccessible(true);
        $ref->invoke($instance);

        $messages = collect($component->get('messages') ?? []);
        $this->assertTrue(
            $messages->contains(fn ($m) => str_contains((string) ($m['text'] ?? ''), 'No plans are available yet. Contact support.')),
            'Toshi must explain empty plan catalog'
        );
    }

    private function seedClassStructure(string $sectionName): void
    {
        $year = AcademicYear::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => date('Y')],
            [
                'description' => 'Current Academic Year',
                'type' => 'Current Academic Year',
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'status' => 1,
            ]
        );

        $standard = Standard::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'primary'],
            ['order' => 1, 'status' => '1']
        );
        $section = Section::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => $sectionName],
            ['status' => '1']
        );
        StandardLink::firstOrCreate([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
        ]);
    }

    /**
     * Seed everything except CurrentPlan so the wizard lands on plan_selection.
     */
    private function seedBlockingStepsExceptPlan(): void
    {
        $this->school->update([
            'name' => 'Parity Ready School',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'ministry_code' => 'EMIS-READY',
            'uneb_center_number' => '',
            'student_size' => '100-300 students',
            'school_category' => 'primary',
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
        $subject = \App\Models\Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $phase->id,
            'section_id' => $section->id,
            'name' => 'Math',
        ]);

        $teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Grace Teacher',
            'email' => 'grace@parity-bugs.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        \App\Models\Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $link->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        \App\Models\AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'starts_on' => '2026-01-15',
            'ends_on' => '2026-04-15',
            'status' => 'current',
        ]);
        \App\Models\FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $phase->id,
            'name' => 'Tuition',
            'amount' => 100000,
        ]);
        \App\Models\WhatsAppUser::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'phone' => '+256700111222',
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $this->assertNull(CurrentPlan::where('school_id', $this->school->id)->first());
        $next = OnboardingStepsService::nextBlockingIncompleteStep($this->school->fresh(), $this->admin->id);
        $this->assertSame('plan_selection', $next['key'] ?? null);
    }

    private function seedThroughTeachers(): void
    {
        $this->seedBlockingStepsExceptPlan();
        CurrentPlan::create([
            'school_id' => $this->school->id,
            'plan_id' => $this->freemium->id,
            'status' => 'running',
        ]);
    }

    private function goToStepKey($component, string $key): void
    {
        foreach ($component->instance()->steps as $i => $step) {
            if (($step['key'] ?? '') === $key) {
                $component->call('goToStep', $i);

                return;
            }
        }
        $this->fail("Step {$key} not found");
    }
}
