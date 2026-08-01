<?php

namespace App\Listeners\Toshi;

use App\Services\ToshiAuditService;
use Laravel\Ai\Events\ToolApprovalRequested;

/**
 * Audit native laravel/ai HITL approval pauses (platform Plan tools).
 */
class LogToolApprovalRequested
{
    public function handle(ToolApprovalRequested $event): void
    {
        $user = auth()->user()
            ?? request()->user()
            ?? $event->conversationUser;

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
            );
        }
    }
}
