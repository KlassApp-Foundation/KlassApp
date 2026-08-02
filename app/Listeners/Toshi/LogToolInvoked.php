<?php

namespace App\Listeners\Toshi;

use App\Listeners\Toshi\Concerns\ResolvesToshiAuditIdentity;
use App\Models\School;
use App\Services\ToshiAuditService;
use Laravel\Ai\Events\ToolInvoked;

/**
 * Audit every Laravel AI SDK tool invocation via ToshiAuditService.
 *
 * Covers native agent tools and agent-mediated MCP tools (Laravel\Ai\Tools\McpTool).
 * Approver identity is NOT set here — ToolInvoked is the read/execute path.
 * HITL approver_id comes from LogToolApprovalResolved or AgentToshi Tier-2 confirm.
 * School call sites (AgentToshi::executeConfirmedTool) remain unchanged.
 *
 * MCP Approvable/HITL for write tools is deferred.
 */
class LogToolInvoked
{
    use ResolvesToshiAuditIdentity;

    public function handle(ToolInvoked $event): void
    {
        $actingUser = $this->conversationUserFromEvent(null, $event->agent);
        $authUser = $this->authUser();
        $user = $actingUser ?? $authUser;

        if (! $user) {
            return;
        }

        $school = $user->school_id
            ? School::find($user->school_id)
            : null;

        ToshiAuditService::logExecution(
            user: $user,
            school: $school,
            toolName: $this->resolveToolName($event->tool),
            arguments: $event->arguments,
            result: is_string($event->result) ? $event->result : json_encode($event->result),
            approver: null,
            actingUser: $actingUser ?? $user,
        );
    }
}
