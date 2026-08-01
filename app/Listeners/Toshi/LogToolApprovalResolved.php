<?php

namespace App\Listeners\Toshi;

use App\Services\ToshiAuditService;
use Laravel\Ai\Events\ToolApprovalResolved;

/**
 * Audit native laravel/ai HITL approval resolutions (approve / reject / edit).
 */
class LogToolApprovalResolved
{
    public function handle(ToolApprovalResolved $event): void
    {
        $user = auth()->user()
            ?? request()->user()
            ?? $event->conversationUser;

        if (! $user) {
            return;
        }

        foreach ($event->toolResults as $result) {
            ToshiAuditService::logApprovalResolved(
                user: $user,
                school: null,
                toolName: $result->name,
                arguments: $result->arguments,
                result: is_string($result->result) ? $result->result : json_encode($result->result),
                denied: (bool) ($result->denied ?? false),
            );
        }
    }
}
