<?php

namespace App\Listeners\Toshi\Concerns;

use App\Models\User;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\Tool;

/**
 * Resolves distinguishable acting-user vs approver identities for Toshi audit logs.
 *
 * - acting user: conversation participant from forUser() / continue(..., as:)
 * - approver: authenticated user who submitted the approval decision (when applicable)
 * - tool name: prefer Tool::name() so MCP McpTool logs mcp_tools_* not "McpTool"
 */
trait ResolvesToshiAuditIdentity
{
    protected function conversationUserFromEvent(?object $conversationUser, ?Agent $agent = null): ?User
    {
        if ($conversationUser instanceof User) {
            return $conversationUser;
        }

        if ($agent instanceof Conversational && method_exists($agent, 'conversationParticipant')) {
            $participant = $agent->conversationParticipant();
            if ($participant instanceof User) {
                return $participant;
            }
        }

        return null;
    }

    protected function authUser(): ?User
    {
        $user = auth()->user() ?? request()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Prefer Tool::name() so MCP wrappers log mcp_tools_* instead of "McpTool".
     */
    protected function resolveToolName(Tool $tool): string
    {
        if (method_exists($tool, 'name')) {
            $name = $tool->name();
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        return class_basename($tool);
    }
}
