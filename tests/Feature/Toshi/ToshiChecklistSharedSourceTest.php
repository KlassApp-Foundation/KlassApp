<?php

namespace Tests\Feature\Toshi;

use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Toshi checklist + progress (dock AND maximized sidebar) read the same
 * OnboardingStepsService source as the dashboard setup bar and the sidebar
 * chip: same count, same neutral labels (K25), rows opening the same step.
 */
class ToshiChecklistSharedSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    /** @return array{0: School, 1: User} */
    private function makeAdmin(): array
    {
        $school = School::create([
            'name' => 'Checklist School',
            'email' => 'checklist@klassapp.test',
            'phone' => '0700000033',
            'status' => 1,
            'slug' => 'checklist-school-'.uniqid(),
            'toshi_mode' => 'onboarding',
            'toshi_enabled' => 0,
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'school_category' => 'primary_nursery',
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => uniqid().'@klassapp.test',
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Checklist',
            'lastname' => 'Admin',
        ]);

        return [$school, $admin];
    }

    public function test_modal_checklist_reads_the_shared_source_with_neutral_labels(): void
    {
        [$school, $admin] = $this->makeAdmin();
        $progress = OnboardingStepsService::progress($school, $admin->id);

        $component = Livewire::actingAs($admin)
            ->test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $school->id);

        $component->assertSee('toshi-setup-list-modal')
            ->assertSee("Progress — {$progress['done']}/{$progress['total']}");

        // Neutral names from the one source; not the country/curriculum jargon.
        $component->assertSee('Registration code')
            ->assertDontSee('EMIS / Ministry code');

        // One row per shared step, opening the same step (jumpToChecklistStep).
        foreach (OnboardingStepsService::steps($school, $admin->id) as $step) {
            $component->assertSee('toshi-setup-row-modal-'.$step['key']);
        }

        $this->assertSame(count(OnboardingStepsService::steps($school, $admin->id)), $progress['total']);
    }

    public function test_dock_and_modal_show_the_same_count_and_labels(): void
    {
        [$school, $admin] = $this->makeAdmin();
        $progress = OnboardingStepsService::progress($school, $admin->id);

        $component = Livewire::actingAs($admin)
            ->test(AgentToshi::class)
            ->set('mode', 'complete')
            ->set('schoolId', $school->id);

        // Both variants carry the same source count.
        $component->assertSee('toshi-setup-list')
            ->assertSee("Progress — {$progress['done']}/{$progress['total']}");

        $html = $component->html();
        $this->assertStringNotContainsString('EMIS / Ministry code', $html);
        $this->assertStringNotContainsString('UNEB centre number', $html);
    }

    public function test_preview_composer_is_visibly_disabled_and_announced(): void
    {
        [$school, $admin] = $this->makeAdmin();
        $school->forceFill(['toshi_mode' => 'preview'])->save();

        Livewire::actingAs($admin)
            ->test(AgentToshi::class)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('messaging disabled in preview');
    }
}
