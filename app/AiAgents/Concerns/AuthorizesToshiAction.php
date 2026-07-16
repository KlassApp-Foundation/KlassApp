<?php

namespace App\AiAgents\Concerns;

use App\Exceptions\ToshiUnauthorizedActionException;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\Gate;

trait AuthorizesToshiAction
{
    /**
     * Authorize the current user for a school-level Toshi action.
     *
     * Throws on failure. Callers in Tool handle() methods should use
     * authorizeOrMessage() instead for a string-returning convenience wrapper.
     *
     * @throws \App\Exceptions\ToshiUnauthorizedActionException
     */
    protected function authorizeSchoolAction(
        User $user,
        string $ability = 'toshi-school-action',
        mixed $target = null
    ): void {
        $response = Gate::inspect($ability, [$user, $target]);

        if ($response->denied()) {
            throw new ToshiUnauthorizedActionException($response->message());
        }

        $effectiveSchoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $effectiveSchoolId) {
            throw new ToshiUnauthorizedActionException(
                'You are not assigned to a school. Cannot perform this action.'
            );
        }

        if ($target !== null && property_exists($target, 'school_id') && $target->school_id !== null) {
            if ((int) $target->school_id !== (int) $effectiveSchoolId) {
                throw new ToshiUnauthorizedActionException(
                    'The target record does not belong to your school.'
                );
            }
        }
    }

    /**
     * Convenience wrapper for Tool handle() methods.
     * Returns null on success, or an error string (prefixed with ❌) on failure.
     */
    protected function authorizeOrMessage(?User $user): ?string
    {
        if (! $user) {
            return '❌ Authentication required.';
        }

        try {
            $this->authorizeSchoolAction($user);
            return null;
        } catch (ToshiUnauthorizedActionException $e) {
            return '❌ ' . $e->getMessage();
        }
    }

    /**
     * Resolve the effective user for the Toshi tool context.
     * Handles Superadmin-impersonating-SchoolAdmin resolution.
     */
    protected function resolveToshiUser(): User
    {
        $user = auth()->user() ?? request()->user();

        if (! $user) {
            throw new ToshiUnauthorizedActionException('Authentication required.');
        }

        return ToshiActionService::getEffectiveUser($user);
    }
}
