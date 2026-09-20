<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\Tools\RouteToSlackSkillTool;
use App\Mcp\Servers\SpikeSlackMockServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Go-live fold-in verification (2026-09-20): role scoping WITHIN a connected school.
 *
 * Slack connects at the workspace level (one admin's OAuth → the whole school's
 * workspace is reachable). These tests prove "workspace reachable" ≠ "every staff
 * member can trigger Slack actions":
 *
 *  - Structural layer: ToshiSdkV2Service's scope router sends ug4/5/6/7/8/10/11 to
 *    their own operations agents whose tools() exclude RouteToSlackSkillTool;
 *    only ToshiOrchestrator (school-admin default arm) registers it.
 *  - In-tool layer: RouteToSlackSkillTool::handle() → AuthorizesToshiAction::
 *    authorizeOrMessage() → Gate toshi-school-action (ug3, or ug1 impersonating
 *    ug3) OR toshi-deputy-action (ug4). Teachers (ug5) are denied by both.
 *
 * The distinguishing factor vs the existing SlackSkillTest::route_tool_denies_roles_
 * without_school_scope: here the school's workspace IS connected (active registry
 * row exists), so a denial can only come from the role gate — not from the
 * ConnectorNotConnected path. Covers both a read intent and a write-gated
 * post-message intent, with a schooladmin positive control.
 */
class SlackRoleScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.slack_mcp.mode' => 'mock',
            // Neutralize dual-config guard + host/model mismatch from the
            // developer's local .env (same pattern as SlackMcpApprovalFlowTest).
            'toshi.llm_env' => [
                'openai_compatible_model' => 'deepseek-chat',
                'toshi_llm_model' => null,
                'openai_compatible_url' => 'https://api.deepseek.com/v1',
                'toshi_llm_base_url' => null,
            ],
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com/v1',
            'ai.providers.openai-compatible.key' => 'test-key',
            'toshi.model' => 'deepseek-chat',
            'toshi.mcp_write_gates.master_switch' => true,
            'toshi.mcp_write_gates.connectors.slack.mode' => 'classify',
            'toshi.mcp_connectors.slack.enabled' => true,
            'toshi.mcp_connectors.slack.read_tools' => ['spike-slack-auth-test', 'spike-slack-list-channels'],
            'toshi.mcp_connectors.slack.write_tools' => ['spike-slack-post-message'],
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->ensureMockSlackClient();
    }

    #[Test]
    public function teacher_of_connected_school_cannot_trigger_slack_read(): void
    {
        [$schoolId, ] = $this->createConnectedSchool();

        $teacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $schoolId]);
        $this->actingAs($teacher);

        $result = (new RouteToSlackSkillTool)->handle(
            new Request(['query' => 'list the Slack channels'])
        );

        // Denied by the role gate — the ❌ authorization message, NOT the
        // "not connected" fallback (workspace IS connected; teacher is not
        // an authorized Slack actor).
        $this->assertStringContainsString('❌', $result);
        $this->assertStringContainsString(
            'not authorized',
            strtolower($result),
            'Teacher at a connected school must hit the role gate, not a connection fallback'
        );
        $this->assertStringNotContainsString('not connected', strtolower($result));
    }

    #[Test]
    public function teacher_of_connected_school_cannot_trigger_slack_write_intent(): void
    {
        [$schoolId, ] = $this->createConnectedSchool();

        $teacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $schoolId]);
        $this->actingAs($teacher);

        $result = (new RouteToSlackSkillTool)->handle(
            new Request(['query' => 'post a message to the #general Slack channel'])
        );

        // The write path must be unreachable BEFORE any approval flow could
        // start: a teacher cannot even enter the skill, so no pause, no
        // approval request, no execution.
        $this->assertStringContainsString('❌', $result);
        $this->assertStringContainsString(
            'not authorized',
            strtolower($result),
            'Teacher must be denied at the role gate before the write/approval path'
        );

        // No Toshi MCP audit rows for this teacher's attempted action.
        $this->assertSame(
            0,
            DB::table('activity_log')->where('causer_id', $teacher->id)->count(),
            'A denied teacher must produce zero audit rows'
        );
    }

    #[Test]
    public function schooladmin_of_connected_school_passes_the_role_gate(): void
    {
        [$schoolId, ] = $this->createConnectedSchool();

        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => $schoolId]);
        $this->actingAs($admin);

        // Fake the LLM (positive controls enter the skill's prompt() loop;
        // the authorization result is the thing under test, not the LLM).
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'id' => 'chatcmpl-test',
                'object' => 'chat.completion',
                'choices' => [[
                    'index' => 0,
                    'message' => ['role' => 'assistant', 'content' => 'OK'],
                ]],
            ], 200),
        ]);

        $result = (new RouteToSlackSkillTool)->handle(
            new Request(['query' => 'list the Slack channels'])
        );

        // Positive control: role gate passes (the mock skill then runs; any
        // LLM/tool response is fine — the point is the ❌ authorization
        // denial is absent).
        $this->assertStringNotContainsString(
            'not authorized',
            strtolower($result),
            'SchoolAdmin of the connected school must pass the role gate'
        );
    }

    #[Test]
    public function deputy_admin_of_connected_school_passes_the_role_gate(): void
    {
        [$schoolId, ] = $this->createConnectedSchool();

        $deputy = User::factory()->create(['usergroup_id' => 4, 'school_id' => $schoolId]);
        $this->actingAs($deputy);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'id' => 'chatcmpl-test',
                'object' => 'chat.completion',
                'choices' => [[
                    'index' => 0,
                    'message' => ['role' => 'assistant', 'content' => 'OK'],
                ]],
            ], 200),
        ]);

        $result = (new RouteToSlackSkillTool)->handle(
            new Request(['query' => 'search Slack for exam timetable messages'])
        );

        // ug4 is dual-allowed by design (toshi-deputy-action).
        $this->assertStringNotContainsString(
            'not authorized',
            strtolower($result),
            'Deputy admin (ug4) is dual-allowed into the Slack route tool by design'
        );
    }

    #[Test]
    public function unlisted_usergroup_of_connected_school_is_denied_by_the_in_tool_gate(): void
    {
        // ug12 (Stock Keeper) falls to the scope router's default arm →
        // ToshiOrchestrator (which registers RouteToSlackSkillTool), so the
        // structural layer does NOT block it — the in-tool Gate is the only
        // barrier. This test pins that barrier.
        [$schoolId, ] = $this->createConnectedSchool();

        DB::table('usergroups')->upsert([
            ['id' => 12, 'name' => 'stock keeper', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $stockKeeper = User::factory()->create(['usergroup_id' => 12, 'school_id' => $schoolId]);
        $this->actingAs($stockKeeper);

        $result = (new RouteToSlackSkillTool)->handle(
            new Request(['query' => 'list the Slack channels'])
        );

        $this->assertStringContainsString('❌', $result);
        $this->assertStringContainsString('not authorized', strtolower($result));
    }

    /**
     * School with an ACTIVE Slack registry row — the workspace is genuinely
     * connected, so denials below can only come from the role gate.
     *
     * @return array{0: int, 1: int} [school_id, connector_row_id]
     */
    private function createConnectedSchool(): array
    {
        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'Role Scope School',
            'slug' => 'role-scope-' . uniqid(),
            'email' => 'role-scope-' . uniqid() . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $connectorId = DB::table('school_mcp_connectors')->insertGetId([
            'school_id' => $schoolId,
            'connector_type' => 'slack',
            'external_team_id' => 'T' . strtoupper(bin2hex(random_bytes(6))),
            'external_team_name' => 'Role Scope Workspace',
            'credentials' => encrypt([
                'access_token' => 'xoxb-role-scope-test',
                'refresh_token' => 'xoxr-role-scope-test',
                'token_type' => 'Bearer',
            ]),
            'token_expires_at' => now()->addHours(12),
            'auth_mode' => 'oauth_remote',
            'status' => 'active',
            'trust_level' => 'first_party_catalog',
            'write_mode' => 'deny',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$schoolId, $connectorId];
    }

    private function ensureMockSlackClient(): void
    {
        try {
            Mcp::client('slack');

            return;
        } catch (\Laravel\Mcp\Exceptions\ClientException) {
            // Register below when routes/ai.php did not run (or client missing).
        }

        Mcp::local('spike-slack-mock', SpikeSlackMockServer::class);
        Mcp::registerClient('slack', function () {
            return Client::local(PHP_BINARY, [
                base_path('artisan'),
                'mcp:start',
                'spike-slack-mock',
            ])->withTimeout(30);
        });
    }
}
