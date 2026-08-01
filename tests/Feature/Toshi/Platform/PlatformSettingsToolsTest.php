<?php

namespace Tests\Feature\Toshi\Platform;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Ai\Tools\Superadmin\ToggleSchoolFeatureTool;
use App\Ai\Tools\Superadmin\UpdateSystemSettingsTool;
use App\Models\ActivityLog;
use App\Models\City;
use App\Models\Country;
use App\Models\School;
use App\Models\SchoolFeatureToggle;
use App\Models\Setting;
use App\Models\User;
use App\Services\FeatureToggleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use ReflectionProperty;
use Tests\TestCase;

class PlatformSettingsToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $siteadmin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.platform_gate.enabled' => true,
            'toshi.platform_gate.user_ids' => [],
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->siteadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin-settings@test.sch.ug',
        ]);

        $country = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'iso_code' => 'UG',
            'tel_prefix' => '+256',
            'status' => 1,
        ]);

        $city = City::create([
            'country_id' => $country->id,
            'name' => 'Kampala',
            'status' => 1,
            'state_id' => 0,
        ]);

        $this->school = School::create([
            'name' => 'Toggle School',
            'email' => 'toggle.school.'.uniqid().'@example.com',
            'phone' => '0700333444',
            'address' => 'Plot 2',
            'city_id' => $city->id,
            'country_id' => $country->id,
            'pincode' => 25602,
            'status' => 1,
        ]);
    }

    public function test_toggle_feature_pending_before_approve_no_mutation(): void
    {
        $this->actingAs($this->siteadmin);

        $args = [
            'school_id' => $this->school->id,
            'feature' => 'whatsapp',
            'enabled' => true,
        ];

        PlatformOperationsAgent::fake([
            new ToolCall('call_toggle_pending', 'ToggleSchoolFeatureTool', $args),
        ]);

        $response = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Enable WhatsApp for the school.');

        $this->assertTrue($response->hasPendingApprovals());
        $this->assertSame('ToggleSchoolFeatureTool', $response->pendingApprovals->first()->tool);
        $this->assertFalse(FeatureToggleService::isEnabled($this->school->id, 'whatsapp'));
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ToggleSchoolFeatureTool')
                ->where('properties->status', 'pending_approval')
                ->exists()
        );
    }

    public function test_toggle_feature_mutates_only_after_decision_approve_resume(): void
    {
        $this->actingAs($this->siteadmin);

        $args = [
            'school_id' => $this->school->id,
            'feature' => 'whatsapp',
            'enabled' => true,
        ];

        PlatformOperationsAgent::fake([
            new ToolCall('call_toggle_approve', 'ToggleSchoolFeatureTool', $args),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Enable WhatsApp.');

        $approvalId = $paused->pendingApprovals->first()->id;

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Feature enabled.');

        (new PlatformOperationsAgent)
            ->continue($paused->conversationId, as: $this->siteadmin)
            ->prompt(Decisions::from([
                $approvalId => Decision::approve(),
            ]));

        $this->assertTrue(FeatureToggleService::isEnabled($this->school->id, 'whatsapp'));
        $this->assertDatabaseHas('school_feature_toggles', [
            'school_id' => $this->school->id,
            'feature' => 'whatsapp',
            'enabled' => 1,
        ]);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ToggleSchoolFeatureTool')
                ->where('properties->status', 'success')
                ->exists()
        );
    }

    public function test_toggle_feature_blocked_on_reject(): void
    {
        $this->actingAs($this->siteadmin);

        PlatformOperationsAgent::fake([
            new ToolCall('call_toggle_reject', 'ToggleSchoolFeatureTool', [
                'school_id' => $this->school->id,
                'feature' => 'toshi',
                'enabled' => true,
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Enable Toshi.');

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Not enabling.');

        (new PlatformOperationsAgent)
            ->continue($paused->conversationId, as: $this->siteadmin)
            ->prompt(Decisions::from([
                $paused->pendingApprovals->first()->id => Decision::reject('Keep disabled.'),
            ]));

        $this->assertFalse(FeatureToggleService::isEnabled($this->school->id, 'toshi'));
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ToggleSchoolFeatureTool')
                ->where('properties->status', 'approval_rejected')
                ->exists()
        );
    }

    public function test_toggle_unknown_feature_validation_via_handle(): void
    {
        $this->actingAs($this->siteadmin);

        $result = (string) app(ToggleSchoolFeatureTool::class)->handle(new Request([
            'school_id' => $this->school->id,
            'feature' => 'not-a-feature',
            'enabled' => true,
        ]));

        $this->assertStringStartsWith('❌', $result);
        $this->assertSame(0, SchoolFeatureToggle::count());
    }

    public function test_update_display_settings_no_approval_required(): void
    {
        $this->actingAs($this->siteadmin);

        Setting::create([
            'key' => 'sitename',
            'name' => 'sitename',
            'description' => 'sitename',
            'value' => 'School-Plus',
            'field' => 'text',
            'active' => 1,
        ]);

        PlatformOperationsAgent::fake([
            new ToolCall('call_display', 'UpdateSystemSettingsTool', [
                'sitename' => 'BatchA-Temp-Name',
            ]),
            'Settings updated.',
        ]);

        $response = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Rename the site.');

        $this->assertFalse($response->hasPendingApprovals());
        $this->assertDatabaseHas('settings', [
            'key' => 'sitename',
            'value' => 'BatchA-Temp-Name',
        ]);
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'UpdateSystemSettingsTool')
                ->where('properties->status', 'success')
                ->exists()
        );
    }

    public function test_update_access_settings_pending_then_approve_resume(): void
    {
        $this->actingAs($this->siteadmin);

        Setting::create([
            'key' => 'maintenance',
            'name' => 'maintenance',
            'description' => 'maintenance',
            'value' => '0',
            'field' => 'text',
            'active' => 1,
        ]);

        PlatformOperationsAgent::fake([
            new ToolCall('call_access', 'UpdateSystemSettingsTool', [
                'maintenance' => '1',
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Enable maintenance mode.');

        $this->assertTrue($paused->hasPendingApprovals());
        $this->assertStringContainsString('maintenance', strtolower((string) $paused->pendingApprovals->first()->reason));
        $this->assertDatabaseHas('settings', ['key' => 'maintenance', 'value' => '0']);

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Maintenance enabled.');

        (new PlatformOperationsAgent)
            ->continue($paused->conversationId, as: $this->siteadmin)
            ->prompt(Decisions::from([
                $paused->pendingApprovals->first()->id => Decision::approve(),
            ]));

        $this->assertDatabaseHas('settings', [
            'key' => 'maintenance',
            'value' => '1',
        ]);
    }

    public function test_update_access_settings_blocked_on_reject(): void
    {
        $this->actingAs($this->siteadmin);

        Setting::create([
            'key' => 'login_status',
            'name' => 'login_status',
            'description' => 'login_status',
            'value' => '1',
            'field' => 'text',
            'active' => 1,
        ]);

        PlatformOperationsAgent::fake([
            new ToolCall('call_login_reject', 'UpdateSystemSettingsTool', [
                'login_status' => '0',
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Disable login.');

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Login left enabled.');

        (new PlatformOperationsAgent)
            ->continue($paused->conversationId, as: $this->siteadmin)
            ->prompt(Decisions::from([
                $paused->pendingApprovals->first()->id => Decision::reject('Keep login open.'),
            ]));

        $this->assertDatabaseHas('settings', [
            'key' => 'login_status',
            'value' => '1',
        ]);
    }

    public function test_update_settings_empty_payload_validation(): void
    {
        $this->actingAs($this->siteadmin);

        $result = (string) app(UpdateSystemSettingsTool::class)->handle(new Request([]));

        $this->assertStringStartsWith('❌', $result);
    }

    public function test_needs_approval_false_for_display_only(): void
    {
        $tool = app(UpdateSystemSettingsTool::class);
        $approval = $tool->shouldRequestApproval(new Request([
            'sitename' => 'Cosmetic Only',
        ]));

        $this->assertNull($approval);
    }

    public function test_needs_approval_true_for_access_keys(): void
    {
        $tool = app(UpdateSystemSettingsTool::class);
        $approval = $tool->shouldRequestApproval(new Request([
            'register_status' => '0',
        ]));

        $this->assertNotNull($approval);
        $this->assertStringContainsString('register_status', (string) $approval->reason);
    }

    private function clearAgentFake(string $agentClass): void
    {
        $ai = Ai::getFacadeRoot();
        $prop = new ReflectionProperty($ai, 'fakeAgentGateways');
        $gateways = $prop->getValue($ai);
        unset($gateways[$agentClass]);
        $prop->setValue($ai, $gateways);
    }

    private function fakeOpenAiCompatibleCompletion(string $content): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'id' => 'chatcmpl-test',
                'object' => 'chat.completion',
                'choices' => [[
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => $content,
                    ],
                    'finish_reason' => 'stop',
                ]],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'total_tokens' => 15,
                ],
            ], 200),
        ]);
    }
}
