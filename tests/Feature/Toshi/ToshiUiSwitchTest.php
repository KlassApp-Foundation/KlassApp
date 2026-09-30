<?php

namespace Tests\Feature\Toshi;

use App\Models\School;
use App\Models\User;
use App\Services\Toshi\ToshiUiSwitch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Soft-launch UI switch: off unless an AI key is set AND the school has toshi_enabled.
 * When off, no school-admin-reachable page may render the word "Toshi".
 */
class ToshiUiSwitchTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Switch School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Switch Admin',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    public function test_switch_requires_ai_key_and_school_flag(): void
    {
        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');

        $this->assertFalse(app(ToshiUiSwitch::class)->enabled($this->admin));

        Config::set('ai.providers.openai-compatible.key', 'sk-test');
        $this->assertFalse(
            app(ToshiUiSwitch::class)->enabled($this->admin),
            'Key alone is not enough when toshi_enabled is off',
        );

        $this->school->update(['toshi_enabled' => 1]);
        $this->admin->refresh();
        $this->assertTrue(app(ToshiUiSwitch::class)->enabled($this->admin->fresh()));

        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');
        $this->assertFalse(app(ToshiUiSwitch::class)->enabled($this->admin->fresh()));
    }

    public function test_school_admin_pages_contain_no_toshi_when_switch_off(): void
    {
        // Red without the UI gates: dashboard / wizard / integrations all render "Toshi".
        Config::set('ai.providers.openai-compatible.key', '');
        Config::set('toshi.api_key', '');
        $this->school->update(['toshi_enabled' => 0]);

        $paths = [
            '/admin/dashboard',
            '/admin/onboarding/wizard',
            '/admin/settings',
            '/admin/settings/integrations',
            '/admin/students',
        ];

        foreach ($paths as $path) {
            $response = $this->actingAs($this->admin)->get($path);
            if ($response->isRedirect()) {
                // Incomplete-setup middleware may bounce to the wizard — follow once.
                $response = $this->actingAs($this->admin)->get($response->headers->get('Location'));
            }
            $this->assertTrue(
                $response->isSuccessful(),
                "Expected {$path} to succeed, got {$response->getStatusCode()}"
            );
            $this->assertStringNotContainsString(
                'Toshi',
                $response->getContent(),
                "School admin page {$path} must not contain \"Toshi\" when the UI switch is off",
            );
        }

        $activity = $this->actingAs($this->admin)->get('/admin/toshi-activity');
        if ($activity->isRedirect()) {
            // Onboarding middleware may bounce incomplete schools before the controller.
            $followed = $this->actingAs($this->admin)->get($activity->headers->get('Location'));
            $this->assertStringNotContainsString('Toshi', $followed->getContent());
        } else {
            $activity->assertNotFound();
        }
    }

    public function test_set_up_with_toshi_returns_when_switch_on(): void
    {
        Config::set('ai.providers.openai-compatible.key', 'sk-test');
        $this->school->update(['toshi_enabled' => 1]);

        $response = $this->actingAs($this->admin->fresh())->get('/admin/dashboard');
        $response->assertOk();
        $response->assertSee('Set up with Toshi', false);
        $response->assertSee('data-testid="toshi-toggle"', false);
    }
}
