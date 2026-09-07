<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Four complete-mode regressions found after the plan→Review fix:
 * 1. Reload must keep draft step (wizard-shaped: nextIncompleteStep, not blocking skip)
 * 2. Fee level selector must offer O'Level/A'Level and persist those Standards
 * 3. Free-text must hit the active step handler before student-lookup routing
 * 4. Skipping WhatsApp must land on School Pay (not auto-skip it via advance(''))
 */
class ToshiCompleteModeFourFindingsTest extends TestCase
{
    use RefreshDatabase;

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

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        Plan::create([
            'name' => 'freemium',
            'display_name' => 'Freemium',
            'amount' => 0,
            'no_of_students' => 100,
            'no_of_users' => 5,
            'cycle' => 365,
            'is_active' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Four Findings Primary',
            'email' => 'four.findings@example.test',
            'phone' => '+256700119902',
            'slug' => 'four-findings-primary',
            'status' => 1,
            'curriculum' => 'uneb',
            'school_category' => 'primary',
            'registration_country' => 'Uganda',
            'ministry_code' => 'EMIS-FOUR',
            'uneb_center_number' => '',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Four Admin',
            'email' => 'four.admin@example.test',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Four',
            'lastname' => 'Admin',
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'type' => 'Current Academic Year',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);
    }

    /** @test */
    public function reload_keeps_complete_mode_step_instead_of_snapping_to_first_db_gap(): void
    {
        $this->actingAs($this->admin);

        $feesIdx = array_search('fees', (new AgentToshi)->steps, true);
        $this->assertNotFalse($feesIdx);

        // Simulate mid-flow: drafts not in DB, session at fees.
        session(['toshi_state' => [
            'mode' => 'complete',
            'scope' => 'school',
            'schoolId' => $this->school->id,
            'step' => $feesIdx,
            'substep' => 6,
            'messages' => [['role' => 'assistant', 'text' => 'Adding fees…']],
            'teacherList' => ['Jane Auma'],
            'studentList' => ['Alice'],
            'terms' => [['name' => 'Term I']],
            'fees' => [],
            'actionData' => ['fees' => []],
        ]]);

        $component = Livewire::test(AgentToshi::class);

        $this->assertSame('complete', $component->get('mode'));
        $this->assertSame($feesIdx, $component->get('step'));
        $this->assertSame('fees', $component->instance()->steps[$component->get('step')]);
        $this->assertNotSame(
            'teachers',
            $component->instance()->steps[$component->get('step')],
            'Must not re-jump to teachers just because drafts are not committed yet'
        );
    }

    /** @test */
    public function fee_level_olevel_persists_tuition_with_labeled_name(): void
    {
        Standard::create(['school_id' => $this->school->id, 'name' => 'o-level', 'order' => 3, 'status' => '1']);
        Standard::create(['school_id' => $this->school->id, 'name' => 'a-level', 'order' => 4, 'status' => '1']);

        $this->assertSame("O'Level", FeesCategories::tierDisplayLabel('o-level'));
        $this->assertSame("A'Level", FeesCategories::tierDisplayLabel('a-level'));

        app(OnboardingEngine::class)->saveFees($this->school, [
            ['name' => 'Tuition', 'amount' => 650000, 'level' => 'o-level'],
            ['name' => 'Tuition', 'amount' => 750000, 'level' => 'a-level'],
        ]);

        $labels = FeesCategories::with('standard')
            ->where('school_id', $this->school->id)
            ->where('name', 'Tuition')
            ->get()
            ->map(fn (FeesCategories $fee) => $fee->labeledName())
            ->sort()
            ->values()
            ->all();

        $this->assertSame(["Tuition (A'Level)", "Tuition (O'Level)"], $labels);

        $this->actingAs($this->admin);
        Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('step', array_search('fees', (new AgentToshi)->steps, true))
            ->call('showFeeFormFn')
            ->assertSee("O'Level")
            ->assertSee("A'Level");
    }

    /** @test */
    public function typed_teacher_name_stays_on_teachers_step_not_student_lookup(): void
    {
        $this->actingAs($this->admin);

        $teachersIdx = array_search('teachers', (new AgentToshi)->steps, true);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('step', $teachersIdx)
            ->set('substep', 0)
            ->set('showTeacherForm', false)
            ->set('input', 'Jane Auma')
            ->call('send');

        $this->assertSame('complete', $component->get('mode'));
        $this->assertSame($teachersIdx, $component->get('step'));
        $this->assertSame('teachers', $component->instance()->steps[$component->get('step')]);

        $messages = collect($component->get('messages'))->pluck('text')->implode("\n");
        $this->assertStringNotContainsString("couldn't find a student matching", $messages);
        $this->assertStringNotContainsString('Found **', $messages);
    }

    /** @test */
    public function skip_and_yes_on_whatsapp_are_not_hijacked_by_lookup(): void
    {
        $this->actingAs($this->admin);

        $waIdx = array_search('whatsapp_verify', (new AgentToshi)->steps, true);
        $schoolPayIdx = array_search('school_pay', (new AgentToshi)->steps, true);

        // "skip" must advance WA → School Pay, not switch to assistant lookup.
        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('schoolPhone', '+256700119902')
            ->set('step', $waIdx)
            ->set('substep', 0)
            ->set('input', 'skip')
            ->call('send');

        $this->assertSame('complete', $component->get('mode'));
        $this->assertSame($schoolPayIdx, $component->get('step'));
        $this->assertSame('school_pay', $component->instance()->steps[$component->get('step')]);

        $messages = collect($component->get('messages'))->pluck('text')->implode("\n");
        $this->assertStringContainsString('School Pay', $messages);
        $this->assertStringNotContainsString("couldn't find a student matching", $messages);
    }

    /** @test */
    public function skipping_whatsapp_lands_on_school_pay_without_auto_skipping_it(): void
    {
        $this->actingAs($this->admin);

        $waIdx = array_search('whatsapp_verify', (new AgentToshi)->steps, true);
        $schoolPayIdx = array_search('school_pay', (new AgentToshi)->steps, true);
        $planIdx = array_search('plan_selection', (new AgentToshi)->steps, true);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('schoolPhone', '+256700119902')
            ->set('step', $waIdx)
            ->set('substep', 0)
            ->call('skipStep');

        $this->assertSame($schoolPayIdx, $component->get('step'));
        $this->assertSame('school_pay', $component->instance()->steps[$component->get('step')]);
        $this->assertNotSame($planIdx, $component->get('step'));

        $messages = collect($component->get('messages'))->pluck('text')->implode("\n");
        $this->assertStringContainsString('School Pay', $messages);
        $this->assertStringContainsString('skip', strtolower($messages));
        // Must still be prompting — not already past School Pay into plan selection.
        $this->assertStringNotContainsString('plan selected', strtolower($messages));
    }
}
