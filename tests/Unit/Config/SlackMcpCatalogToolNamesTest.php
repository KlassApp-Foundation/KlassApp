<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards against the §6c failure class: catalog read_tools/write_tools drifted
 * from the live Slack MCP server's actual tool names, so mode=classify treated
 * reads as writes (stranded pending_approval, no free reads).
 *
 * Snapshot: tests/fixtures/slack-mcp-live-tool-names.json (recorded from staging
 * live Mcp::client('slack')->tools() on 2026-10-02). Update the snapshot when
 * Slack renames tools — do not silence the test.
 */
class SlackMcpCatalogToolNamesTest extends TestCase
{
    #[Test]
    public function slack_catalog_tool_names_match_recorded_live_snapshot(): void
    {
        $snapshotPath = base_path('tests/fixtures/slack-mcp-live-tool-names.json');
        $this->assertFileExists($snapshotPath);

        $snapshot = json_decode((string) file_get_contents($snapshotPath), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($snapshot['read_tools'] ?? null);
        $this->assertIsArray($snapshot['write_tools'] ?? null);

        $catalog = config('toshi.mcp_connectors.slack');
        $this->assertIsArray($catalog);

        $read = $catalog['read_tools'] ?? [];
        $write = $catalog['write_tools'] ?? [];

        $this->assertSame(
            $this->sortedUnique($snapshot['read_tools']),
            $this->sortedUnique($read),
            'config/toshi.php mcp_connectors.slack.read_tools must match the live Slack MCP snapshot'
        );
        $this->assertSame(
            $this->sortedUnique($snapshot['write_tools']),
            $this->sortedUnique($write),
            'config/toshi.php mcp_connectors.slack.write_tools must match the live Slack MCP snapshot'
        );

        $overlap = array_intersect($read, $write);
        $this->assertSame([], array_values($overlap), 'A tool cannot be both read and write in the catalog');
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    private function sortedUnique(array $names): array
    {
        $names = array_values(array_unique(array_map('strval', $names)));
        sort($names);

        return $names;
    }
}
