<?php

namespace Tests\Feature\Toshi;

use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * T-117: the setup checklist opens the wizard step. It no longer drives a chat
 * step (the v1 walkthrough is retired), so there is no awaitingConfirm state to
 * clear — the whole panel leaves the page.
 */
class ToshiChecklistJumpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    #[Test]
    public function a_checklist_row_redirects_to_the_wizard_step(): void
    {
        $school = School::create([
            'name' => 'Jump Clear School',
            'email' => 'jump.clear@klassapp.test',
            'phone' => '0700000022',
            'status' => 1,
            'slug' => 'jump-clear-school',
            'toshi_enabled' => 0,
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
            ->set('mode', 'setup')
            ->set('schoolId', $school->id)
            ->call('jumpToChecklistStep', 'school_category')
            ->assertRedirect(route('admin.onboarding.wizard', ['step' => 'school_category']));
    }

    #[Test]
    public function an_unknown_key_does_not_redirect(): void
    {
        $school = School::create([
            'name' => 'Jump Unknown School',
            'email' => 'jump.unknown@klassapp.test',
            'phone' => '0700000023',
            'status' => 1,
            'slug' => 'jump-unknown-school',
            'toshi_enabled' => 0,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => 'jump.unknown.admin@klassapp.test',
            'status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(AgentToshi::class)
            ->call('jumpToChecklistStep', 'not_a_step')
            ->assertNoRedirect();
    }
}
