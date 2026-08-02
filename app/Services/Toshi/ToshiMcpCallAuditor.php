<?php

namespace App\Services\Toshi;

use App\Models\School;
use App\Models\User;
use App\Services\ToshiAuditService;
use Laravel\Mcp\Client\Schema\ToolResult;

/**
 * Lowest-layer audit for MCP client callTool (named clients via AuditingMcpClientManager).
 *
 * Agent-mediated McpTool invocations hit this path through Tool::call → Client::callTool;
 * LogToolInvoked skips McpTool to avoid double rows. Direct Client::web()/local() still
 * bypasses named-client wrapping — prefer Mcp::client() or ToshiMcpClient.
 */
class ToshiMcpCallAuditor
{
    private static ?User $actingUserOverride = null;

    private static bool $auditing = false;

    public static function actingAs(?User $user): void
    {
        self::$actingUserOverride = $user;
    }

    public static function clearActingAs(): void
    {
        self::$actingUserOverride = null;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public static function audit(string $toolName, array $arguments, ToolResult $result): void
    {
        if (self::$auditing) {
            return;
        }

        $user = self::$actingUserOverride
            ?? (auth()->user() instanceof User ? auth()->user() : null)
            ?? (request()->user() instanceof User ? request()->user() : null);

        if (! $user instanceof User) {
            return;
        }

        self::$auditing = true;

        try {
            $school = $user->school_id
                ? School::find($user->school_id)
                : null;

            $resultText = method_exists($result, 'text')
                ? (string) $result->text()
                : (string) $result;

            if (($result->isError ?? false) === true) {
                $resultText = str_starts_with($resultText, '❌')
                    ? $resultText
                    : '❌ '.$resultText;
            }

            ToshiAuditService::logExecution(
                user: $user,
                school: $school,
                toolName: 'mcp_tools_'.$toolName,
                arguments: $arguments,
                result: $resultText,
                approver: null,
                actingUser: $user,
            );
        } finally {
            self::$auditing = false;
        }
    }
}
