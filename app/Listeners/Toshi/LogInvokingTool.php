<?php

namespace App\Listeners\Toshi;

use App\Listeners\Toshi\Concerns\ResolvesToshiAuditIdentity;
use App\Models\School;
use App\Services\ToshiAuditService;
use Laravel\Ai\Events\InvokingTool;

/**
 * Optional pre-invoke audit for native laravel/ai tool calls.
 * Kept lightweight — full outcome is logged by LogToolInvoked.
 */
class LogInvokingTool
{
    use ResolvesToshiAuditIdentity;

    public function handle(InvokingTool $event): void
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

        ToshiAuditService::logInvoking(
            user: $user,
            school: $school,
            toolName: class_basename($event->tool),
            arguments: $event->arguments,
            actingUser: $actingUser ?? $user,
            approver: $approver,
        );
    }
}
