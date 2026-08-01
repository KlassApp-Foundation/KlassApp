<?php

namespace App\Listeners\Toshi\Concerns;

use App\Models\User;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;

/**
 * Resolves distinguishable acting-user vs approver identities for Toshi audit logs.
 *
 * - acting user: conversation participant from forUser() / continue(..., as:)
 * - approver: authenticated user who submitted the approval decision (when applicable)
 */
trait ResolvesToshiAuditIdentity
{
    protected function conversationUserFromEvent(?object $conversationUser, ?Agent $agent = null): ?User
    {
        if ($conversationUser instanceof User) {
            return $conversationUser;
        }

        if ($agent instanceof Conversational && method_exists($agent, 'conversationParticipant')) {
            $participant = $agent->conversationParticipant();
            if ($participant instanceof User) {
                return $participant;
            }
        }

        return null;
    }

    protected function authUser(): ?User
    {
        $user = auth()->user() ?? request()->user();

        return $user instanceof User ? $user : null;
    }
}
