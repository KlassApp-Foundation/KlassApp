<?php

namespace Tests\Unit\Services\Toshi;

use App\Services\Toshi\McpWriteGate;
use RuntimeException;
use Tests\TestCase;

class McpWriteGateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.mcp_connectors.slack.read_tools' => ['slack_list_channels', 'slack_search'],
            'toshi.mcp_connectors.slack.write_tools' => ['slack_post_message'],
            'toshi.mcp_write_gates.connectors.slack.mode' => 'classify',
            'toshi.mcp_write_gates.connectors.notion.mode' => 'allowlist',
            'toshi.mcp_write_gates.connectors.google-drive.mode' => 'deny',
        ]);
    }

    public function test_master_switch_off_allows_all(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => false]);

        $this->assertFalse(McpWriteGate::isWrite('slack', 'slack_post_message'));
        $this->assertFalse(McpWriteGate::isWrite('slack', 'slack_list_channels'));
        $this->assertFalse(McpWriteGate::isWrite('unknown', 'anything'));
    }

    public function test_master_switch_on_enables_classification(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => true]);

        $this->assertTrue(McpWriteGate::isWrite('slack', 'slack_post_message'));
        $this->assertFalse(McpWriteGate::isWrite('slack', 'slack_list_channels'));
    }

    public function test_deny_mode_treats_all_tools_as_writes(): void
    {
        config([
            'toshi.mcp_write_gates.master_switch' => true,
            'toshi.mcp_connectors.google-drive.read_tools' => ['gdrive_list'],
        ]);

        $this->assertTrue(McpWriteGate::isWrite('google-drive', 'gdrive_list'));
        $this->assertTrue(McpWriteGate::isWrite('google-drive', 'gdrive_upload'));
    }

    public function test_classify_mode_separates_reads_and_writes(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => true]);

        $this->assertFalse(McpWriteGate::isWrite('slack', 'slack_list_channels'));
        $this->assertFalse(McpWriteGate::isWrite('slack', 'slack_search'));
        $this->assertTrue(McpWriteGate::isWrite('slack', 'slack_post_message'));
        $this->assertTrue(McpWriteGate::isWrite('slack', 'unknown_tool'));
    }

    public function test_allowlist_mode_gates_only_write_tools(): void
    {
        config([
            'toshi.mcp_write_gates.master_switch' => true,
            'toshi.mcp_connectors.notion.write_tools' => ['notion_create_page'],
            'toshi.mcp_connectors.notion.read_tools' => ['notion_search'],
        ]);

        $this->assertTrue(McpWriteGate::isWrite('notion', 'notion_create_page'));
        $this->assertFalse(McpWriteGate::isWrite('notion', 'notion_search'));
        $this->assertFalse(McpWriteGate::isWrite('notion', 'notion_update_page'));
    }

    public function test_unknown_connector_fails_closed(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => true]);

        $this->assertTrue(McpWriteGate::isWrite('unknown-connector', 'any_tool'));
    }

    public function test_unknown_mode_fails_closed(): void
    {
        config([
            'toshi.mcp_write_gates.master_switch' => true,
            'toshi.mcp_write_gates.connectors.slack.mode' => 'invalid-mode',
        ]);

        $this->assertTrue(McpWriteGate::isWrite('slack', 'slack_list_channels'));
    }

    public function test_assert_executable_throws_on_write_when_not_bypassed(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => true]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('write-classified');

        McpWriteGate::assertExecutable('slack', 'slack_post_message');
    }

    public function test_assert_executable_passes_on_read(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => true]);

        McpWriteGate::assertExecutable('slack', 'slack_list_channels');

        $this->addToAssertionCount(1);
    }

    public function test_bypass_for_skips_gate(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => true]);

        $result = McpWriteGate::bypassFor(function (): int {
            McpWriteGate::assertExecutable('slack', 'slack_post_message');

            return 42;
        });

        $this->assertSame(42, $result);
    }

    public function test_bypass_for_restores_previous_state(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => true]);

        McpWriteGate::bypassFor(function (): void {
            McpWriteGate::assertExecutable('slack', 'slack_post_message');
        });

        $this->expectException(RuntimeException::class);
        McpWriteGate::assertExecutable('slack', 'slack_post_message');
    }

    public function test_bypass_for_restores_false_when_nested(): void
    {
        config(['toshi.mcp_write_gates.master_switch' => true]);

        $outer = McpWriteGate::bypassFor(function (): int {
            return McpWriteGate::bypassFor(function (): int {
                McpWriteGate::assertExecutable('slack', 'slack_post_message');

                return 1;
            });
        });

        $this->assertSame(1, $outer);

        $this->expectException(RuntimeException::class);
        McpWriteGate::assertExecutable('slack', 'slack_post_message');
    }
}
