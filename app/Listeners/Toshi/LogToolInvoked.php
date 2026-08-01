<?php

namespace App\Listeners\Toshi;

use App\Listeners\Toshi\Concerns\ResolvesToshiAuditIdentity;
use App\Models\School;
use App\Services\ToshiAuditService;
use Laravel\Ai\Events\ToolInvoked;

/**
 * Audit every Laravel AI SDK tool invocation via ToshiAuditService.
 * School call sites (AgentToshi::executeConfirmedTool) remain unchanged.
 */
class LogToolInvoked
{
    use ResolvesToshiAuditIdentity;

    public function handle(ToolInvoked $event): void
    {
        $actingUser = $this->conversationUserFromEvent(null, $event->agent);
        $approver = $this->authUser();
        $user = $actingUser ?? $approver;

        if (! $user) {
            return;
        }

        $school = $user->school_id
            ? School::find($user->school_id)
            : null;

        ToshiAuditService::logExecution(
            user: $user,
            school: $school,
            toolName: class_basename($event->tool),
            arguments: $event->arguments,
            result: is_string($event->result) ? $event->result : json_encode($event->result),
            approver: $approver,
            actingUser: $actingUser ?? $user,
        );
    }
}
