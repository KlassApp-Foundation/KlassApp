<?php

namespace App\Listeners\Toshi;

use App\Listeners\Toshi\Concerns\ResolvesToshiAuditIdentity;
use App\Models\School;
use App\Services\ToshiAuditService;
use Laravel\Ai\Events\InvokingTool;

/**
 * Optional pre-invoke audit for native laravel/ai tool calls (incl. MCP McpTool).
 * Kept lightweight — full outcome is logged by LogToolInvoked.
 * Approver stays null here; HITL uses ToolApprovalResolved / Tier-2.
 */
class LogInvokingTool
{
    use ResolvesToshiAuditIdentity;

    public function handle(InvokingTool $event): void
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

        ToshiAuditService::logInvoking(
            user: $user,
            school: $school,
            toolName: $this->resolveToolName($event->tool),
            arguments: $event->arguments,
            actingUser: $actingUser ?? $user,
            approver: null,
        );
    }
}
