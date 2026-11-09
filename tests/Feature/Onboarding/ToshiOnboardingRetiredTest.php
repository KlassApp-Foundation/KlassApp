<?php

namespace Tests\Feature\Onboarding;

use App\Enums\ToshiMode;
use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * T-117: the wizard is the only setup surface (docs/onboarding/decisions-2026-11.md, D2).
 * Toshi's scripted onboarding is retired everywhere, including test schools.
 */
class ToshiOnboardingRetiredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    #[Test]
    public function every_school_goes_to_the_wizard_from_the_setup_entry(): void
    {
        foreach ([['real', 0], ['test', 1]] as [$label, $isTest]) {
            [$school, $user] = $this->schoolAdmin($label.' school', $isTest);

            $response = $this->actingAs($user)->get(route('onboarding.choice'));

            $response->assertRedirect();
            $this->assertStringContainsString(
                '/admin/onboarding/wizard',
                $response->headers->get('Location'),
                $label.' school must land in the wizard'
            );

            $this->actingAs($user)
                ->post(route('onboarding.choice.store'), ['path' => 'toshi'])
                ->assertRedirect();
        }
    }

    #[Test]
    public function a_school_parked_in_the_retired_chat_mode_is_moved_to_preview(): void
    {
        [$school, $user] = $this->schoolAdmin('Parked school', false);
        $school->forceFill(['toshi_mode' => 'onboarding', 'toshi_enabled' => 0])->save();

        $this->actingAs($user)->get(route('onboarding.choice'))->assertRedirect();

        $school->refresh();
        $this->assertSame(ToshiMode::Preview, $school->toshi_mode);
        $this->assertSame(0, (int) $school->toshi_enabled);
    }

    #[Test]
    public function the_migration_moves_only_the_retired_mode(): void
    {
        [$parked] = $this->schoolAdmin('Moved school', false);
        $parked->forceFill(['toshi_mode' => 'onboarding', 'toshi_enabled' => 1])->save();

        [$assistant] = $this->schoolAdmin('Assistant school', false);
        $assistant->forceFill(['toshi_mode' => 'assistant', 'toshi_enabled' => 1])->save();

        [$preview] = $this->schoolAdmin('Preview school', false);
        $preview->forceFill(['toshi_mode' => 'preview', 'toshi_enabled' => 0])->save();

        $migration = require database_path('migrations/2026_11_09_120000_move_schools_off_toshi_onboarding.php');
        $migration->up();

        $this->assertSame('preview', $parked->fresh()->toshi_mode->value);
        $this->assertSame(0, (int) $parked->fresh()->toshi_enabled);
        $this->assertSame('assistant', $assistant->fresh()->toshi_mode->value);
        $this->assertSame(0, (int) $preview->fresh()->toshi_enabled);
    }

    #[Test]
    public function no_onboarding_step_list_contains_exams_or_school_pay(): void
    {
        $this->assertArrayNotHasKey('exams', OnboardingStepsService::ALL_STEPS);
        $this->assertArrayNotHasKey('school_pay', OnboardingStepsService::ALL_STEPS);

        $steps = (new \ReflectionClass(AgentToshi::class))->getDefaultProperties()['steps'];

        $this->assertNotContains('exams', $steps);
        $this->assertNotContains('school_pay', $steps);

        foreach (['handleExams', 'handleSchoolPay', 'commitAll'] as $retired) {
            $this->assertFalse(
                method_exists(AgentToshi::class, $retired),
                $retired.' must not exist on the retired v1 component'
            );
        }
    }

    /** @return array{0: School, 1: User} */
    private function schoolAdmin(string $name, bool $isTest): array
    {
        $school = School::create([
            'name' => $name,
            'email' => str_replace(' ', '.', strtolower($name)).'@test.sch.ug',
            'phone' => '07'.substr(md5($name), 0, 8),
            'slug' => str_replace(' ', '-', strtolower($name)),
            'status' => 1,
            'curriculum' => 'uneb',
            'is_test' => $isTest ? 1 : 0,
            'toshi_mode' => ToshiMode::Preview,
            'toshi_enabled' => 0,
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Admin',
            'email' => 'admin.'.str_replace(' ', '.', strtolower($name)).'@test.sch.ug',
            'password' => bcrypt('secret-password'),
            'status' => 'active',
        ]);

        return [$school, $user];
    }
}
