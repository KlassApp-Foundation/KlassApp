<?php

namespace Tests\Feature\Toshi;

use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sticky awaitingConfirm after checklist/resume jumps left the composer locked
 * on button-driven steps (category / plan). Jump helpers must clear the gate.
 */
class ToshiJumpClearsAwaitingConfirmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_checklist_jump_clears_awaiting_confirm(): void
    {
        $school = School::create([
            'name' => 'Jump Clear School',
            'email' => 'jump.clear@klassapp.test',
            'phone' => '0700000022',
            'status' => 1,
            'slug' => 'jump-clear-school',
            'toshi_enabled' => 1,
            'curriculum' => 'uneb',
            'school_category' => 'primary',
            'registration_country' => 'Uganda',
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => 'jump.clear.admin@klassapp.test',
            'status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $school->id)
            ->set('awaitingConfirm', true)
            ->call('jumpToChecklistStep', 'school_category')
            ->assertSet('awaitingConfirm', false)
            ->assertSet('actionStep', 'onboarding_school_category');
    }
}
