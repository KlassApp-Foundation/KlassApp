<?php

namespace App\Listeners\Toshi;

use App\Services\ToshiAuditService;
use Laravel\Ai\Events\InvokingTool;

/**
 * Optional pre-invoke audit for native laravel/ai tool calls.
 * Kept lightweight — full outcome is logged by LogToolInvoked.
 */
class LogInvokingTool
{
    public function handle(InvokingTool $event): void
    {
        $user = auth()->user() ?? request()->user();

        if (! $user) {
            return;
        }

        $school = $user->school_id
            ? \App\Models\School::find($user->school_id)
            : null;

        ToshiAuditService::logInvoking(
            user: $user,
            school: $school,
            toolName: class_basename($event->tool),
            arguments: $event->arguments,
        );
    }
}
