<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\Onboarding;

use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Userprofile;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * OnboardingStepsService::progress() — the single count used by the dashboard
 * banner and chip — plus the ?step= deep-link on the wizard and the
 * user_preferences store behind the banner dismissal.
 */
class OnboardingProgressAndDeepLinkTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchoolAndAdmin(): array
    {
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Progress School', 'slug' => 'progress-'.uniqid(), 'email' => uniqid().'@p.test',
            'phone' => '07'.random_int(70000000, 78999999), 'status' => 1,
        ]);
        $admin = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 3, 'status' => 'active', 'email_verified' => 1]);
        Userprofile::create(['user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3, 'firstname' => 'P', 'lastname' => 'A', 'status' => 'active']);

        return [$school, $admin];
    }

    public function test_progress_totals_are_internally_consistent(): void
    {
        [$school, $admin] = $this->makeSchoolAndAdmin();

        $progress = OnboardingStepsService::progress($school, $admin->id);
        $this->assertGreaterThan(0, $progress['total']);
        $this->assertSame($progress['total'], $progress['done'] + count($progress['incomplete']));
        $this->assertSame($progress['incomplete'][0] ?? null, $progress['next']['key'] ?? null);
        $this->assertArrayHasKey($progress['next']['key'] ?? 'x', $progress['labels']);
        $this->assertSame((int) round($progress['done'] / $progress['total'] * 100), $progress['percent']);

        // Completing a step moves the counters.
        $school->forceFill(['student_size' => 'Up to 500'])->save();
        $after = OnboardingStepsService::progress($school->fresh(), $admin->id);
        $this->assertSame($progress['done'] + 1, $after['done']);
    }

    public function test_wizard_honours_the_step_query_parameter(): void
    {
        [$school, $admin] = $this->makeSchoolAndAdmin();
        $this->actingAs($admin);

        Livewire::withQueryParams(['step' => 'subjects'])->test(ManualOnboardingWizard::class)
            ->assertSet('stepIndex', function ($index) {
                return $this->currentKey($index) === 'subjects';
            });

        Livewire::withQueryParams(['step' => 'does-not-exist'])->test(ManualOnboardingWizard::class)
            ->assertSet('stepIndex', function ($index) {
                return $this->currentKey($index) !== 'does-not-exist';
            });
    }

    private function currentKey(int $index): ?string
    {
        $steps = OnboardingStepsService::steps(School::first(), auth()->id());

        return $steps[$index]['key'] ?? null;
    }

    public function test_user_preference_store_is_per_user_and_updates_in_place(): void
    {
        [$school, $admin] = $this->makeSchoolAndAdmin();

        UserPreference::set($admin, 'k', 'v1');
        UserPreference::set($admin, 'k', 'v2');

        $this->assertSame('v2', UserPreference::get($admin, 'k'));
        $this->assertSame(1, UserPreference::query()->where('user_id', $admin->id)->where('key', 'k')->count());

        UserPreference::forget($admin, 'k');
        $this->assertNull(UserPreference::get($admin, 'k'));
    }
}
