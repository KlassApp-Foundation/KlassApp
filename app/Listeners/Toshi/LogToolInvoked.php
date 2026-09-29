<?php

namespace App\Listeners\Toshi;

use App\Listeners\Toshi\Concerns\ResolvesToshiAuditIdentity;
use App\Models\School;
use App\Services\ToshiAuditService;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Tools\McpTool;

/**
 * Audit every Laravel AI SDK tool invocation via ToshiAuditService.
 *
 * Covers native agent tools. Agent-mediated MCP tools (McpTool) are audited at the
 * lowest Client::callTool layer (AuditingMcpClient / AuditingWebClient) so this
 * listener skips McpTool to avoid double rows.
 * Approver identity: read-only executions log approver null. Executions of
 * Approvable (write) tools only happen after a human approval (HTTP resume or
 * Tier-2 confirm), so the authenticated user in the executing request is the
 * approver — recorded here as well as on the LogToolApprovalResolved row.
 * School call sites (AgentToshi::executeConfirmedTool) remain unchanged.
 *
 * MCP Approvable/HITL for write tools is deferred.
 */
class LogToolInvoked
{
    use ResolvesToshiAuditIdentity;

    public function handle(ToolInvoked $event): void
    {
        if ($event->tool instanceof McpTool) {
            return;
        }

        $actingUser = $this->conversationUserFromEvent(null, $event->agent);
        $authUser = $this->authUser();
        $user = $actingUser ?? $authUser;

        if (! $user) {
            return;
        }

        $school = $user->school_id
            ? School::find($user->school_id)
            : null;

        $approver = $event->tool instanceof Approvable ? $this->authUser() : null;

        ToshiAuditService::logExecution(
            user: $user,
            school: $school,
            toolName: $this->resolveToolName($event->tool),
            arguments: $event->arguments,
            result: is_string($event->result) ? $event->result : json_encode($event->result),
            approver: $approver,
            actingUser: $actingUser ?? $user,
        );
    }
}
