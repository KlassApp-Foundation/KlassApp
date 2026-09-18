<?php

namespace Tests\Unit\Ai\Tools\Toshi;

use App\Ai\Tools\Toshi\ApprovableMcpTool;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Client\Primitives\Tool;
use Tests\TestCase;

class ApprovableMcpToolTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.mcp_write_gates.master_switch' => true,
            'toshi.mcp_connectors.slack.read_tools' => ['slack_list_channels'],
            'toshi.mcp_connectors.slack.write_tools' => ['slack_post_message'],
            'toshi.mcp_write_gates.connectors.slack.mode' => 'classify',
        ]);
    }

    private function makePrimitive(string $name, array $inputSchema = []): Tool
    {
        return new Tool(
            client: null,
            name: $name,
            title: 'Test ' . $name,
            description: 'Test description',
            inputSchema: $inputSchema,
            outputSchema: null,
            annotations: [],
            meta: null,
        );
    }

    public function test_rejects_non_mcp_primitive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ApprovableMcpTool::wrap('slack', new \stdClass);
    }

    public function test_name_delegates(): void
    {
        $primitive = $this->makePrimitive('slack-list-channels');
        $tool = ApprovableMcpTool::wrap('slack', $primitive);

        $this->assertSame('mcp_tools_slack-list-channels', $tool->name());
    }

    public function test_description_delegates(): void
    {
        $primitive = $this->makePrimitive('slack-list-channels');
        $tool = ApprovableMcpTool::wrap('slack', $primitive);

        $this->assertSame('Test description', $tool->description());
    }

    public function test_should_request_approval_returns_null_for_read_tool(): void
    {
        $primitive = $this->makePrimitive('slack_list_channels');
        $tool = ApprovableMcpTool::wrap('slack', $primitive);

        $approval = $tool->shouldRequestApproval(new Request([]));

        $this->assertNull($approval);
    }

    public function test_should_request_approval_returns_required_for_write_tool(): void
    {
        $primitive = $this->makePrimitive('slack_post_message');
        $tool = ApprovableMcpTool::wrap('slack', $primitive);

        $approval = $tool->shouldRequestApproval(new Request(['channel' => '#general', 'text' => 'Hello']));

        $this->assertInstanceOf(Approval::class, $approval);
        $this->assertNotNull($approval->reason);
    }

    public function test_write_approval_message_includes_client_and_tool(): void
    {
        $primitive = $this->makePrimitive('slack_post_message');
        $tool = ApprovableMcpTool::wrap('slack', $primitive);

        $approval = $tool->shouldRequestApproval(new Request(['channel' => '#general', 'text' => 'Hello world']));

        $this->assertStringContainsString('slack', $approval->reason);
        $this->assertStringContainsString('slack_post_message', $approval->reason);
    }

    public function test_write_approval_message_summarizes_arguments(): void
    {
        $primitive = $this->makePrimitive('slack_post_message');
        $tool = ApprovableMcpTool::wrap('slack', $primitive);

        $approval = $tool->shouldRequestApproval(new Request([
            'channel' => '#general',
            'text' => 'Hello world',
        ]));

        $this->assertStringContainsString('channel=', $approval->reason);
    }

    public function test_should_request_approval_respects_master_switch(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => false]);

        $primitive = $this->makePrimitive('slack_post_message');
        $tool = ApprovableMcpTool::wrap('slack', $primitive);

        $approval = $tool->shouldRequestApproval(new Request([]));

        $this->assertNull($approval);
    }

    public function test_raw_tool_name_strips_prefix(): void
    {
        $primitive = $this->makePrimitive('slack_post_message');
        $tool = ApprovableMcpTool::wrap('slack', $primitive);

        $reflector = new \ReflectionMethod($tool, 'rawToolName');
        $reflector->setAccessible(true);

        $this->assertSame('slack_post_message', $reflector->invoke($tool));
    }

    public function test_raw_tool_name_handles_unprefixed_name(): void
    {
        $primitive = new Tool(
            client: null,
            name: 'custom_tool',
            title: 'test',
            description: 'test',
            inputSchema: [],
            outputSchema: null,
            annotations: [],
            meta: null,
        );
        $tool = ApprovableMcpTool::wrap('slack', $primitive);

        $reflector = new \ReflectionMethod($tool, 'rawToolName');
        $reflector->setAccessible(true);

        $this->assertSame('custom_tool', $reflector->invoke($tool));
    }
}
