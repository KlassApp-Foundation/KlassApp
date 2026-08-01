<?php

namespace App\Policies;

use App\Enums\ToshiScope;
use App\Models\User;
use App\Services\Toshi\ToshiAvailabilityGate;
use Laravel\Ai\Models\Conversation;

/**
 * Authorize laravel/ai agent conversations for platform ops approval UI.
 *
 * Bound to Laravel\Ai\Models\Conversation (not App\Models\Conversation messaging).
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        if (! app(ToshiAvailabilityGate::class)->allows($user, ToshiScope::Platform)) {
            return false;
        }

        return $conversation->participant_type === Conversation::participantType($user)
            && (string) $conversation->participant_id === (string) Conversation::participantKey($user);
    }
}
