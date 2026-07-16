<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;

/**
 * Thin wrapper around the existing activity_log table for Toshi-specific audit trail.
 *
 * All Toshi write and cancel actions route through this single service so there's
 * exactly one place that decides what gets logged, at what severity, and with what
 * school-scoping. This is the only path that writes to activity_log for Toshi —
 * no stray logging in individual tools or elsewhere.
 *
 * @see \App\Livewire\AgentToshi::executeConfirmedTool()  — the single execution point
 * @see \App\Livewire\AgentToshi::confirmNo()              — the single cancel point
 */
class ToshiAuditService
{
    const LOG_NAME = 'toshi';

    /**
     * Log a successful (or failed) tool execution.
     *
     * Called from executeConfirmedTool() after the tool's handle() returns.
     * Every Toshi write goes through exactly this one logging call.
     */
    public static function logExecution(
        User $user,
        ?School $school,
        string $toolName,
        array $arguments,
        string $result,
    ): ActivityLog {
        $success = !str_starts_with($result, '❌');

        return self::write(
            user: $user,
            school: $school,
            toolName: $toolName,
            description: $success ? "Executed {$toolName}" : "Failed {$toolName}",
            arguments: $arguments,
            status: $success ? 'success' : 'failed',
            result: $result,
        );
    }

    /**
     * Log a user-initiated cancellation (user clicked No on a confirmation card).
     *
     * Called from confirmNo() and the text-fallback cancel path in
     * handleAssistantQuery(). Captures what was proposed and rejected.
     */
    public static function logCancellation(
        User $user,
        ?School $school,
        string $toolName,
        array $arguments,
    ): ActivityLog {
        return self::write(
            user: $user,
            school: $school,
            toolName: $toolName,
            description: "Cancelled {$toolName}",
            arguments: $arguments,
            status: 'cancelled',
            result: 'User cancelled — no changes made.',
        );
    }

    /**
     * Core write — single place that inserts into activity_log.
     */
    private static function write(
        User $user,
        ?School $school,
        string $toolName,
        string $description,
        array $arguments,
        string $status,
        string $result,
    ): ActivityLog {
        return ActivityLog::create([
            'log_name'    => self::LOG_NAME,
            'description' => $description,
            'causer_id'   => $user->id,
            'causer_type' => User::class,
            'school_id'   => $school?->id,
            'subject_id'  => null,
            'subject_type'=> null,
            'properties'  => [
                'tool'      => $toolName,
                'arguments' => $arguments,
                'status'    => $status,
                'result'    => $result,
            ],
            'batch_uuid'  => null,
        ]);
    }
}
