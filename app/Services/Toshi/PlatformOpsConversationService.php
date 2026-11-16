<?php

namespace App\Services\Toshi;

use App\Ai\Agents\PlatformOperationsAgent;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Platform ops conversation helpers for the human approval review gate.
 */
class PlatformOpsConversationService
{
    /**
     * Reconstruct pending approvals from the latest paused assistant message.
     *
     * @return Collection<int, PendingApproval>
     */
    public function pendingApprovals(Conversation $conversation): Collection
    {
        $message = $this->latestPausedMessage($conversation);

        if (! $message) {
            return collect();
        }

        return collect($message->tool_calls ?? [])
            ->filter(fn (mixed $call): bool => is_array($call) && PendingApproval::isPending($call))
            ->map(function (array $call): PendingApproval {
                $reason = $call['approval_reason'] ?? null;

                return new PendingApproval(
                    (string) ($call['id'] ?? ''),
                    (string) ($call['name'] ?? 'unknown'),
                    is_array($call['arguments'] ?? null) ? $call['arguments'] : [],
                    is_string($reason) ? $reason : null,
                );
            })
            ->values();
    }

    public function hasPendingApprovals(Conversation $conversation): bool
    {
        return $this->pendingApprovals($conversation)->isNotEmpty();
    }

    /**
     * Continue PlatformOperationsAgent with a text prompt or Decisions payload.
     */
    public function prompt(User $user, Conversation $conversation, string|Decisions $prompt): AgentResponse
    {
        return (new PlatformOperationsAgent)
            ->continue($conversation->id, as: $user)
            ->prompt($prompt);
    }

    public function latestPausedMessage(Conversation $conversation): ?ConversationMessage
    {
        return $conversation->messages()
            ->where('role', 'assistant')
            ->where('status', MessageStatus::Paused->value)
            ->orderByDesc('created_at')
            ->get()
            ->first(function (ConversationMessage $message): bool {
                return collect($message->tool_calls ?? [])
                    ->contains(fn (mixed $call): bool => is_array($call) && PendingApproval::isPending($call));
            });
    }
}
