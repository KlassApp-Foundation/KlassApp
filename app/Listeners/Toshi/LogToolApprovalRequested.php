<?php

namespace App\Listeners\Toshi;

use App\Ai\Events\ToolApprovalRequested;
use App\Services\ToshiAuditService;

/**
 * Audit Approvable tool approval requests (platform Plan tools).
 */
class LogToolApprovalRequested
{
    public function handle(ToolApprovalRequested $event): void
    {
        $user = auth()->user() ?? request()->user();

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
