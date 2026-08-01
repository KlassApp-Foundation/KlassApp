<?php

namespace App\Listeners\Toshi;

use App\Listeners\Toshi\Concerns\ResolvesToshiAuditIdentity;
use App\Services\ToshiAuditService;
use Laravel\Ai\Events\ToolApprovalRequested;

/**
 * Audit native laravel/ai HITL approval pauses (platform Plan tools).
 */
class LogToolApprovalRequested
{
    use ResolvesToshiAuditIdentity;

    public function handle(ToolApprovalRequested $event): void
    {
        $actingUser = $this->conversationUserFromEvent($event->conversationUser, $event->agent);
        $user = $actingUser ?? $this->authUser();

        if (! $user) {
            return;
        }

        foreach ($event->pendingApprovals as $pending) {
            ToshiAuditService::logApprovalRequested(
                user: $user,
                school: null,
                toolName: $pending->tool,
                arguments: $pending->arguments,
                reason: $pending->reason,
                actingUser: $actingUser ?? $user,
            );
        }
    }
}
