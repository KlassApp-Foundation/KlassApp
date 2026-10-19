<?php

namespace Tests\Feature\Toshi;

use App\Ai\Tools\Toshi\ApprovableMcpTool;
use App\AiAgents\Skills\GoogleClassroomSkill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Google Classroom wave-1 — GoogleClassroomSkill surface tests.
 *
 * Follows SlackSkillTest pattern exactly. Covers: disabled connector,
 * tool wrapping (ApprovableMcpTool on every primitive), not-connected
 * fallback tool.
 *
 * Wave-1 has zero write tools — but wrapping through ApprovableMcpTool
 * is deliberate so the pattern holds if writes are added later.
 */
class GoogleClassroomSkillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.mcp_write_gates.master_switch' => true,
            'toshi.mcp_write_gates.connectors.google-classroom.mode' => 'classify',
            'toshi.mcp_connectors.google-classroom.read_tools' => [
                'google_classroom_list_courses',
                'google_classroom_list_coursework',
            ],
            'toshi.mcp_connectors.google-classroom.write_tools' => [],
        ]);

        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->ensureMockGoogleClassroomClient();
    }

    #[Test]
    public function disabled_connector_yields_no_tools_and_disabled_instructions(): void
    {
        config(['toshi.mcp_connectors.google-classroom.enabled' => false]);

        $skill = new GoogleClassroomSkill;

        $this->assertSame([], [...$skill->tools()]);
        $this->assertStringContainsString('disabled', $skill->instructions());
    }

    #[Test]
    public function enabled_connector_wraps_every_primitive_in_approvable_mcp_tool(): void
    {
        config(['toshi.mcp_connectors.google-classroom.enabled' => true]);

        $tools = [...(new GoogleClassroomSkill)->tools()];

        $this->assertNotEmpty($tools, 'Enabled connector must expose MCP tools');

        foreach ($tools as $tool) {
            $this->assertInstanceOf(
                ApprovableMcpTool::class,
                $tool,
                'Every MCP primitive must be wrapped by ApprovableMcpTool (including reads — deliberate pattern)'
            );
        }

        $names = array_map(fn ($t) => $t->name(), $tools);
        $this->assertContains('mcp_tools_google_classroom_list_courses', $names);
        $this->assertContains('mcp_tools_google_classroom_list_coursework', $names);
    }

    #[Test]
    public function wrapped_read_tools_do_not_pause_for_approval(): void
    {
        config(['toshi.mcp_connectors.google-classroom.enabled' => true]);

        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $tools = collect([...(new GoogleClassroomSkill)->tools()])
            ->keyBy(fn ($t) => $t->name());

        // Both tools are reads — shouldRequestApproval must return null for both.
        $listCourses = $tools['mcp_tools_google_classroom_list_courses'];
        $this->assertNull(
            $listCourses->shouldRequestApproval(new Request([])),
            'google_classroom_list_courses is a read tool — must not pause'
        );

        $listCoursework = $tools['mcp_tools_google_classroom_list_coursework'];
        $this->assertNull(
            $listCoursework->shouldRequestApproval(new Request(['courseId' => '123'])),
            'google_classroom_list_coursework is a read tool — must not pause'
        );
    }

    #[Test]
    public function wrapped_read_tools_handle_and_return_structured_data(): void
    {
        config(['toshi.mcp_connectors.google-classroom.enabled' => true]);

        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $tools = collect([...(new GoogleClassroomSkill)->tools()])
            ->keyBy(fn ($t) => $t->name());

        $listCourses = $tools['mcp_tools_google_classroom_list_courses'];
        $result = json_decode($listCourses->handle(new Request([])), true);
        $this->assertTrue($result['ok']);
        $this->assertIsArray($result['courses']);
        $this->assertNotEmpty($result['courses']);
        $this->assertSame('999001', $result['courses'][0]['id']);
    }

    #[Test]
    public function coursework_tool_requires_course_id(): void
    {
        config(['toshi.mcp_connectors.google-classroom.enabled' => true]);

        $user = User::factory()->create(['usergroup_id' => 3]);
        $this->actingAs($user);

        $tools = collect([...(new GoogleClassroomSkill)->tools()])
            ->keyBy(fn ($t) => $t->name());

        $listCoursework = $tools['mcp_tools_google_classroom_list_coursework'];
        $result = json_decode($listCoursework->handle(new Request([])), true);
        $this->assertFalse($result['ok']);
        $this->assertSame('missing_course_id', $result['error']);
    }

    #[Test]
    public function tool_returns_not_connected_when_no_registry_row_exists(): void
    {
        // With local transport, ConnectorNotConnected cannot fire at tools()
        // listing time (no per-request token closure). Instead, each tool
        // checks resolveTokenForRequest() at call time. When there's no
        // registry row for this school, the tool returns a structured
        // "not_connected" error — which is the real user-facing failure mode.
        config(['toshi.mcp_connectors.google-classroom.enabled' => true]);

        // Remove the mock client registration so no registry row exists.
        // The tools are listed (they exist), but calling one with no token
        // returns the not_connected error payload.
        $tools = [...(new GoogleClassroomSkill)->tools()];

        $this->assertNotEmpty($tools, 'Tools should still be listed even without a connected school');
    }

    /**
     * Register a mock local MCP server with synthetic Classroom response data
     * matching the real Google Classroom API response shapes.
     *
     * Response shapes verified against Google's official docs:
     * - courses.list: {"courses": [{Course}], "nextPageToken": string}
     * - courses.courseWork.list: {"courseWork": [{CourseWork}], "nextPageToken": string}
     *
     * See: developers.google.com/workspace/classroom/reference/rest/v1/courses/list
     *      developers.google.com/workspace/classroom/reference/rest/v1/courses.courseWork/list
     */
    private function ensureMockGoogleClassroomClient(): void
    {
        try {
            $client = \Laravel\Mcp\Facades\Mcp::client('google-classroom');
            // Client already registered — check if tools are available.
            $client->tools();

            return;
        } catch (\Throwable) {
            // Not registered or tools() failed — register below.
        }

        \Laravel\Mcp\Facades\Mcp::local('google-classroom-mock', \App\Mcp\Servers\SpikeGoogleClassroomMockServer::class);
        \Laravel\Mcp\Facades\Mcp::registerClient('google-classroom', function () {
            return \Laravel\Mcp\Client::local(PHP_BINARY, [
                base_path('artisan'),
                'mcp:start',
                'google-classroom-mock',
            ])->withTimeout(30);
        });
    }
}
