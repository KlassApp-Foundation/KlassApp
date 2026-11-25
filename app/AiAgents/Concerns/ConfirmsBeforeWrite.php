<?php

namespace App\AiAgents\Concerns;

use App\Services\ToshiActionService;
use App\Services\Toshi\WriteContext;

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
 * When no confirmed WriteContext is passed (the default), the tool returns a
 * __tier2_confirm JSON that AgentToshi converts into the pending confirmation
 * card. When a confirmed context for this tool and these arguments is passed,
 * confirmOrExecute() returns null and the tool proceeds to write.
 *
 * The context is an argument, not a flag: a write can only run unconfirmed if
 * the caller explicitly says who confirmed it.
 */
trait ConfirmsBeforeWrite
{
    /**
     * If confirmation is required, return the JSON payload to trigger the
     * confirmation card. Returns null when the passed context confirms it.
     */
    protected function confirmOrExecute(
        string $toolName,
        array $args,
        callable $preview,
        ?WriteContext $context = null,
    ): ?string {
        return ToshiActionService::confirmationPayload(
            $toolName,
            $args,
            $preview(),
            $context,
        );
    }
}
