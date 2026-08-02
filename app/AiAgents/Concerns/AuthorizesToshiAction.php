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

        $this->assertEffectiveSchoolScope($user, $target);
    }

    /**
     * Dual-allow school admin (toshi-school-action) OR deputy admin (toshi-deputy-action).
     *
     * Shared school tools call authorizeOrMessage() → this path so ug4 can use the same
     * tool classes as ug3 without widening toshi-school-action. Owner-only tools
     * (AddCoAdminTool, SetCurriculumTool) must use authorizeSchoolAdminOrMessage() instead.
     *
     * @throws \App\Exceptions\ToshiUnauthorizedActionException
     */
    protected function authorizeSchoolOrDeputyAction(User $user, mixed $target = null): void
    {
        $school = Gate::inspect('toshi-school-action', [$user, $target]);
        if ($school->allowed()) {
            $this->assertEffectiveSchoolScope($user, $target);

            return;
        }

        $deputy = Gate::inspect('toshi-deputy-action', [$user, $target]);
        if ($deputy->allowed()) {
            $this->assertEffectiveSchoolScope($user, $target);

            return;
        }

        throw new ToshiUnauthorizedActionException($school->message());
    }

    /**
     * @throws \App\Exceptions\ToshiUnauthorizedActionException
     */
    protected function assertEffectiveSchoolScope(User $user, mixed $target = null): void
    {
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
     * Convenience wrapper for shared school Tool handle() methods.
     * Dual-allows ug3 (toshi-school-action) or ug4 (toshi-deputy-action).
     * Returns null on success, or an error string (prefixed with ❌) on failure.
     */
    protected function authorizeOrMessage(?User $user): ?string
    {
        if (! $user) {
            return '❌ Authentication required.';
        }

        try {
            $this->authorizeSchoolOrDeputyAction($user);

            return null;
        } catch (ToshiUnauthorizedActionException $e) {
            return '❌ '.$e->getMessage();
        }
    }

    /**
     * Owner-level school-admin only (ug3 / ug1 impersonating ug3).
     * Use for AddCoAdminTool and SetCurriculumTool — not dual-allowed for deputies.
     */
    protected function authorizeSchoolAdminOrMessage(?User $user): ?string
    {
        if (! $user) {
            return '❌ Authentication required.';
        }

        try {
            $this->authorizeSchoolAction($user);

            return null;
        } catch (ToshiUnauthorizedActionException $e) {
            return '❌ '.$e->getMessage();
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
