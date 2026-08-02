<?php

namespace App\Services\Toshi\Concerns;

use App\Services\Toshi\ToshiMcpCallAuditor;
use Laravel\Mcp\Client\Schema\ToolResult;

/**
 * Overrides Laravel MCP Client::callTool so named-client invocations are audited.
 */
trait AuditsMcpToolCalls
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function callTool(string $name, array $arguments = []): ToolResult
    {
        $result = parent::callTool($name, $arguments);

        ToshiMcpCallAuditor::audit($name, $arguments, $result);

        return $result;
    }
}
