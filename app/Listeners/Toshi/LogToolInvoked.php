<?php

namespace App\Listeners\Toshi;

use App\Services\ToshiAuditService;
use Laravel\Ai\Events\ToolInvoked;

/**
 * Audit every Laravel AI SDK tool invocation via ToshiAuditService.
 * School call sites (AgentToshi::executeConfirmedTool) remain unchanged.
 */
class LogToolInvoked
{
    public function handle(ToolInvoked $event): void
    {
        $user = auth()->user() ?? request()->user();

        if (! $user) {
            return;
        }

        $school = $user->school_id
            ? \App\Models\School::find($user->school_id)
            : null;

        ToshiAuditService::logExecution(
            user: $user,
            school: $school,
            toolName: class_basename($event->tool),
            arguments: $event->arguments,
            result: is_string($event->result) ? $event->result : json_encode($event->result),
        );
    }
}
