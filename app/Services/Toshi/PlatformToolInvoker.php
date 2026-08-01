<?php

namespace App\Services\Toshi;

use App\Ai\Approvals\Decision;
use App\Ai\Approvals\PendingApproval;
use App\Ai\Contracts\Approvable;
use App\Ai\Events\ToolApprovalRequested;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Tools\Request;

/**
 * Invokes platform tools with optional Approvable gating.
 *
 * Until laravel/ai is bumped to ^0.10 (native Approvable + agent pause/resume),
 * Plan tools use this invoker so approval is enforced before handle() mutates.
 */
class PlatformToolInvoker
{
    /**
     * Set while handle() runs after Decision::approve (or for non-Approvable tools).
     * Approvable Plan tools refuse mutation unless this is true.
     */
    private static bool $executionApproved = false;

    public static function isExecutionApproved(): bool
    {
        return self::$executionApproved;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{status:string,result:?string,pending:?PendingApproval}
     */
    public function invoke(
        Tool $tool,
        array $arguments,
        ?Decision $decision = null,
        ?Agent $agent = null,
    ): array {
        $request = new Request($arguments);
        $invocationId = (string) Str::uuid();
        $toolInvocationId = (string) Str::uuid();
        $toolName = class_basename($tool);

        if ($tool instanceof Approvable) {
            $approval = $tool->shouldRequestApproval($request);

            if ($approval !== null) {
                if ($decision === null) {
                    $pending = new PendingApproval(
                        id: $toolInvocationId,
                        tool: $toolName,
                        arguments: $arguments,
                        reason: $approval->reason,
                    );

                    Event::dispatch(new ToolApprovalRequested(
                        invocationId: $invocationId,
                        agent: $agent,
                        pendingApprovals: Collection::make([$pending]),
                    ));

                    return [
                        'status' => 'pending_approval',
                        'result' => null,
                        'pending' => $pending,
                    ];
                }

                if ($decision->isRejected()) {
                    $result = '❌ Rejected: '.($decision->result ?? 'Not approved.');

                    if ($agent) {
                        Event::dispatch(new ToolInvoked(
                            $invocationId,
                            $toolInvocationId,
                            $agent,
                            $tool,
                            $arguments,
                            $result,
                        ));
                    }

                    return [
                        'status' => 'rejected',
                        'result' => $result,
                        'pending' => null,
                    ];
                }

                if (is_array($decision->arguments)) {
                    $arguments = array_merge($arguments, $decision->arguments);
                    $request = new Request($arguments);
                }
            }
        }

        self::$executionApproved = true;

        try {
            $result = (string) $tool->handle($request);
        } finally {
            self::$executionApproved = false;
        }

        if ($agent) {
            Event::dispatch(new ToolInvoked(
                $invocationId,
                $toolInvocationId,
                $agent,
                $tool,
                $arguments,
                $result,
            ));
        }

        return [
            'status' => 'executed',
            'result' => $result,
            'pending' => null,
        ];
    }
}
