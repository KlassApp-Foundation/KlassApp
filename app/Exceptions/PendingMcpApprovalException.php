<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by RouteTo* MCP skill tools after populating
 * ToshiActionService::$pendingConfirmPayload, so the parent Orchestrator
 * loop aborts immediately instead of continuing past a nested pause.
 *
 * ToshiSdkV2Service::ask() converts this (or a swallowed null from
 * Orchestrator::run) back into the panel __tier2_confirm JSON via the
 * side-channel — preventing stranded agent_conversation approvals with
 * no confirm card in the UI.
 */
class PendingMcpApprovalException extends RuntimeException
{
    public function __construct(string $message = 'MCP tool paused for human approval.')
    {
        parent::__construct($message);
    }
}
