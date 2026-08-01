<?php

namespace App\AiAgents\Concerns;

use App\Exceptions\ToshiUnauthorizedActionException;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

trait AuthorizesTeacherToshiAction
{
    protected function authorizeTeacherAction(User $user): void
    {
        $response = Gate::inspect('toshi-teacher-action', $user);

        if ($response->denied()) {
            throw new ToshiUnauthorizedActionException($response->message());
        }

        if (! $user->school_id) {
            throw new ToshiUnauthorizedActionException(
                'You are not assigned to a school. Cannot perform this action.'
            );
        }
    }

    protected function authorizeTeacherOrMessage(?User $user): ?string
    {
        if (! $user) {
            return '❌ Authentication required.';
        }

        try {
            $this->authorizeTeacherAction($user);

            return null;
        } catch (ToshiUnauthorizedActionException $e) {
            return '❌ '.$e->getMessage();
        }
    }
}
