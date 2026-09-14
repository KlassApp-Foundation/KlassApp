<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingStepsService;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ToshiFreeTextConfirmParityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Free Text Confirm School',
            'email' => 'freetext-confirm@test.sch.ug',
            'phone' => '0700000077',
            'slug' => 'freetext-confirm-school',
            'status' => 1,
            'curriculum' => 'uneb',
            'school_category' => 'primary',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'FT Admin',
            'email' => 'admin@freetext-confirm.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'FT',
            'lastname' => 'Admin',
        ]);
    }

    public function test_typed_yes_confirms_pending_tool_gate(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'assistant')
            ->set('step', 99)
            ->set('actionStep', null)
            ->set('awaitingConfirm', true)
            ->set('pendingToolConfirm', [
                'tool' => 'toolDoesNotExistForParityTest',
                'args' => [],
                'preview' => 'Parity test confirm',
            ]);

        $this->assertTrue((bool) $component->get('awaitingConfirm'));
        $this->assertSame('assistant', $component->get('mode'));
        $this->assertNotNull($component->get('pendingToolConfirm'));

        $component->set('input', 'yes')->call('send');

        $this->assertFalse((bool) $component->get('awaitingConfirm'));
        $this->assertNull($component->get('pendingToolConfirm'));
    }

    public function test_typed_yeah_also_confirms_pending_tool_gate(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AgentToshi::class)
            ->set('mode', 'assistant')
            ->set('step', 99)
            ->set('actionStep', null)
            ->set('awaitingConfirm', true)
            ->set('pendingToolConfirm', [
                'tool' => 'toolDoesNotExistForParityTest',
                'args' => [],
                'preview' => 'Parity test confirm',
            ])
            ->set('input', 'yeah')
            ->call('send')
            ->assertSet('awaitingConfirm', false)
            ->assertSet('pendingToolConfirm', null);
    }

    public function test_review_typed_yes_triggers_commit_path(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $this->school->id)
            ->set('reviewData', [
                'schoolName' => $this->school->name,
                'mode' => 'complete',
            ]);

        $steps = $component->get('steps');
        $reviewIdx = array_search('review', $steps, true);
        $this->assertNotFalse($reviewIdx);
        $component->set('step', $reviewIdx);

        $beforeMessages = count($component->get('messages'));
        $component->set('input', 'yes')->call('send');

        $this->assertGreaterThan($beforeMessages, count($component->get('messages')));
    }

    public function test_toshi_label_for_standards_is_classes_not_structure(): void
    {
        $this->assertSame(
            'Classes',
            OnboardingStepsService::labelForContext('standards', 'Structure & Class Teachers', 'toshi')
        );
        $this->assertSame(
            'Structure & Class Teachers',
            OnboardingStepsService::labelForContext('standards', 'Structure & Class Teachers', 'wizard')
        );
    }
}
