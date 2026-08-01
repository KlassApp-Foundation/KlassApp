<?php

namespace Tests\Feature\Toshi\Platform;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Ai\Tools\Superadmin\ImpersonateSchoolAdminTool;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Impersonation platform tools — dual-identity audit + reject zero-trace.
 *
 * Spot-check vs Batch E: original audit verified start via GET
 * /schooladmin/{id}/impersonate → session + UI "Stop Impersonating", and stop
 * via /teacher/impersonate/stop clearing session key `impersonate`. These tests
 * assert the same session key / isImpersonating() state through the service
 * path used by the tool (and wired into ImpersonateController).
 */
class PlatformImpersonationToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $siteadmin;

    private User $schoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.platform_gate.enabled' => true,
            'toshi.platform_gate.user_ids' => [],
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->siteadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin-impersonate@test.sch.ug',
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $this->siteadmin->id,
            'usergroup_id' => 1,
            'firstname' => 'Site',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        $this->schoolAdmin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => null,
            'email' => 'schooladmin-impersonate-target@test.sch.ug',
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $this->schoolAdmin->id,
            'usergroup_id' => 3,
            'firstname' => 'School',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);
    }

    public function test_impersonate_pending_names_target_account_no_session(): void
    {
        Auth::login($this->siteadmin);

        PlatformOperationsAgent::fake([
            new ToolCall('call_impersonate_pending', 'ImpersonateSchoolAdminTool', [
                'user_id' => $this->schoolAdmin->id,
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Impersonate the school admin.');

        $this->assertTrue($paused->hasPendingApprovals());
        $pending = $paused->pendingApprovals->first();
        $this->assertSame('ImpersonateSchoolAdminTool', $pending->tool);
        $this->assertStringContainsString($this->schoolAdmin->email, (string) $pending->reason);
        $this->assertStringContainsString((string) $this->schoolAdmin->id, (string) $pending->reason);

        $this->assertFalse($this->siteadmin->isImpersonating());
        $this->assertFalse(session()->has('impersonate'));
        $this->assertSame($this->siteadmin->id, Auth::id());

        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ImpersonateSchoolAdminTool')
                ->where('properties->status', 'pending_approval')
                ->where('properties->acting_user_id', $this->siteadmin->id)
                ->whereNull('properties->approver_id')
                ->exists()
        );
    }

    public function test_impersonate_http_approve_starts_session_and_dual_identity_audit(): void
    {
        Auth::login($this->siteadmin);

        $paused = $this->pauseImpersonate();
        $approvalId = $paused->pendingApprovals->first()->id;

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Impersonation started.');

        $response = $this->postJson(route('superadmin.toshi.ops.store', $paused->conversationId), [
            'decisions' => [
                $approvalId => ['action' => 'approve'],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'complete');

        // Batch E verification method: session key `impersonate` set to target id.
        $this->assertTrue($this->siteadmin->fresh()->isImpersonating());
        $this->assertSame($this->schoolAdmin->id, (int) session('impersonate'));
        // Auth guard identity remains the real (login) superadmin — same as Batch E.
        $this->assertSame($this->siteadmin->id, Auth::id());

        $resolved = ActivityLog::where('log_name', 'toshi')
            ->where('properties->tool', 'ImpersonateSchoolAdminTool')
            ->where('properties->status', 'approval_resolved')
            ->latest('id')
            ->first();

        $this->assertNotNull($resolved);
        $props = $resolved->properties;
        $this->assertArrayHasKey('acting_user_id', $props);
        $this->assertArrayHasKey('approver_id', $props);
        $this->assertSame($this->siteadmin->id, $props['acting_user_id']);
        $this->assertSame($this->siteadmin->id, $props['approver_id']);
        // Distinguishable fields (self-approve: same value, separate keys).
        $this->assertTrue(array_key_exists('acting_user_id', $props) && array_key_exists('approver_id', $props));

        $executed = ActivityLog::where('log_name', 'toshi')
            ->where('properties->tool', 'ImpersonateSchoolAdminTool')
            ->where('properties->status', 'success')
            ->latest('id')
            ->first();

        $this->assertNotNull($executed);
        $this->assertSame($this->siteadmin->id, $executed->properties['acting_user_id']);
        $this->assertSame($this->siteadmin->id, $executed->properties['approver_id']);
    }

    public function test_impersonate_http_reject_leaves_zero_trace(): void
    {
        Auth::login($this->siteadmin);

        $paused = $this->pauseImpersonate();
        $approvalId = $paused->pendingApprovals->first()->id;

        $this->clearAgentFake(PlatformOperationsAgent::class);
        $this->fakeOpenAiCompatibleCompletion('Impersonation declined.');

        $response = $this->postJson(route('superadmin.toshi.ops.store', $paused->conversationId), [
            'decisions' => [
                $approvalId => [
                    'action' => 'reject',
                    'result' => 'Do not impersonate.',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'complete');

        // Zero-trace: no session started, no auth guard change.
        $this->assertFalse($this->siteadmin->fresh()->isImpersonating());
        $this->assertFalse(session()->has('impersonate'));
        $this->assertNull(session('impersonate'));
        $this->assertSame($this->siteadmin->id, Auth::id());

        $this->assertFalse(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ImpersonateSchoolAdminTool')
                ->where('properties->status', 'success')
                ->exists()
        );
        $this->assertTrue(
            ActivityLog::where('log_name', 'toshi')
                ->where('properties->tool', 'ImpersonateSchoolAdminTool')
                ->where('properties->status', 'approval_rejected')
                ->where('properties->acting_user_id', $this->siteadmin->id)
                ->where('properties->approver_id', $this->siteadmin->id)
                ->exists()
        );
    }

    public function test_impersonate_non_school_admin_validation_via_handle(): void
    {
        Auth::login($this->siteadmin);

        $result = (string) app(ImpersonateSchoolAdminTool::class)->handle(new Request([
            'user_id' => $this->siteadmin->id,
        ]));

        $this->assertStringStartsWith('❌', $result);
        $this->assertFalse(session()->has('impersonate'));
    }

    private function pauseImpersonate()
    {
        PlatformOperationsAgent::fake([
            new ToolCall('call_impersonate_'.md5((string) $this->schoolAdmin->id), 'ImpersonateSchoolAdminTool', [
                'user_id' => $this->schoolAdmin->id,
            ]),
        ]);

        $paused = (new PlatformOperationsAgent)
            ->forUser($this->siteadmin)
            ->prompt('Impersonate school admin.');

        $this->assertTrue($paused->hasPendingApprovals());

        return $paused;
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
