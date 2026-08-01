<?php

namespace App\Services\Superadmin;

use App\Models\User;
use App\Traits\Common;
use Illuminate\Validation\ValidationException;

/**
 * School-admin impersonation for superadmins (Batch E / ImpersonateController).
 *
 * Session key: `impersonate` via Nckg\Impersonate CanImpersonate trait.
 * Start: setImpersonating(targetId). Stop: stopImpersonating() clears session.
 */
class ImpersonationService
{
    use Common;

    /**
     * Start impersonating a school admin (usergroup_id=3).
     * Same gate as ImpersonateController@schoolAdminimpersonate.
     *
     * @throws ValidationException
     */
    public function startSchoolAdminImpersonation(User $actor, int $targetUserId): User
    {
        if ((int) $actor->usergroup_id !== 1) {
            throw ValidationException::withMessages([
                'user_id' => 'Only superadmins may impersonate school admins.',
            ]);
        }

        $target = User::find($targetUserId);

        if (! $target) {
            throw ValidationException::withMessages([
                'user_id' => 'User not found.',
            ]);
        }

        if (! self::is_admin($target->id)) {
            throw ValidationException::withMessages([
                'user_id' => 'Target is not a school admin (usergroup_id=3). Impersonation disabled for this user.',
            ]);
        }

        $actor->setImpersonating($target->id);

        return $target;
    }

    /**
     * Whether an impersonation session is active for the given actor.
     */
    public function isImpersonating(User $actor): bool
    {
        return $actor->isImpersonating();
    }

    /**
     * Impersonated user id from session, if any.
     */
    public function impersonatedUserId(): ?int
    {
        $id = session('impersonate');

        return $id !== null ? (int) $id : null;
    }
}
