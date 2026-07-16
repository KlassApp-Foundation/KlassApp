<?php

namespace App\AiAgents\Concerns;

use App\Services\ToshiActionService;

/**
 * For write tools that should require user confirmation before executing.
 *
 * Usage: add `use ConfirmsBeforeWrite;` to the tool class, then in handle():
 *
 *   $confirm = $this->confirmOrExecute(
 *       'toolRecordAttendance',
 *       $args,
 *       fn() => "Record attendance: {$args['student']} on {$args['date']}"
 *   );
 *   if ($confirm !== null) return $confirm;
 *
 * When ToshiActionService::$bypassConfirm is false (the default), the tool
 * returns a __tier2_confirm JSON that AgentToshi converts into the pending
 * confirmation card. When bypassConfirm is true (set by confirmYes() on the
 * second pass), confirmOrExecute() returns null and the tool proceeds to
 * call ToshiActionService and write to the database.
 */
trait ConfirmsBeforeWrite
{
    /**
     * If confirmation is required, return the JSON payload to trigger the
     * confirmation card. Returns null when bypassed — tool should execute.
     */
    protected function confirmOrExecute(string $toolName, array $args, callable $preview): ?string
    {
        return ToshiActionService::confirmationPayload(
            $toolName,
            $args,
            $preview(),
        );
    }
}
