<?php

namespace App\Services\Toshi\Concerns;

use App\Services\Toshi\McpWriteGate;
use App\Services\Toshi\ToshiMcpCallAuditor;
use Laravel\Mcp\Client\Schema\ToolResult;

/**
 * Overrides Laravel MCP Client::callTool so named-client invocations are audited.
 *
 * Defense-in-depth: write-classified tools fail closed if called outside an
 * approved agent turn (McpWriteGate::bypassFor set by ApprovableMcpTool::handle).
 */
trait AuditsMcpToolCalls
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function callTool(string $name, array $arguments = []): ToolResult
    {
        if ($this->name !== null) {
            McpWriteGate::assertExecutable($this->name, $name);
        }

        $result = parent::callTool($name, $arguments);

        ToshiMcpCallAuditor::audit($name, $arguments, $result);

        return $result;
    }
}
