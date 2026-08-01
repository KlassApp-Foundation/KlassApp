<?php

namespace App\Listeners\Toshi;

use App\Listeners\Toshi\Concerns\ResolvesToshiAuditIdentity;
use App\Services\ToshiAuditService;
use Laravel\Ai\Events\ToolApprovalResolved;

/**
 * Audit native laravel/ai HITL approval resolutions (approve / reject / edit).
 *
 * Approver (auth) and acting user (conversation participant) are logged as
 * separate fields — never conflated, even under self-approve.
 */
class LogToolApprovalResolved
{
    use ResolvesToshiAuditIdentity;

    public function handle(ToolApprovalResolved $event): void
    {
        $actingUser = $this->conversationUserFromEvent($event->conversationUser, $event->agent);
        $approver = $this->authUser();
        $user = $actingUser ?? $approver;

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
                approver: $approver,
                actingUser: $actingUser ?? $user,
            );
        }
    }
}
