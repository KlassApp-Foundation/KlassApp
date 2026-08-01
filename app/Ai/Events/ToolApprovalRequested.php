<?php

namespace App\Ai\Events;

use App\Ai\Approvals\PendingApproval;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Agent;

/**
 * App-level mirror of Laravel\Ai\Events\ToolApprovalRequested (laravel/ai ^0.10).
 * Fired by PlatformToolInvoker when an Approvable tool pauses for human approval.
 */
class ToolApprovalRequested
{
    /**
     * @param  Collection<int, PendingApproval>  $pendingApprovals
     */
    public function __construct(
        public string $invocationId,
        public ?Agent $agent,
        public Collection $pendingApprovals,
        public ?string $conversationId = null,
        public ?object $conversationUser = null,
    ) {}
}
