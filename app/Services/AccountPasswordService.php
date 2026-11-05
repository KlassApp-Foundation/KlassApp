<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Change the signed-in user's password and keep them signed in on THIS device.
 *
 * Every role's change-password screen calls this, so they all behave the same:
 *  - the new hash is saved on the guard's own user instance (a second User::find() copy
 *    leaves the session guard holding the old hash);
 *  - Auth::logoutOtherDevices() re-hashes it and re-issues this device's remember-me cookie
 *    with the new hash. Without that, the cookie minted at sign-in no longer validates
 *    (SessionGuard::userFromRecaller), and the person is signed out when their session next
 *    lapses, which is the "changing my password logged me out" report;
 *  - AuthenticateSessionUnlessImpersonating (web group) then refuses every other device.
 */
class AccountPasswordService
{
    public const SUCCESS_MESSAGE = 'Password changed. You are still signed in here; any other device has been signed out.';

    public function change(User $expected, string $newPassword): void
    {
        $user = Auth::guard()->user();

        if (! $user instanceof User || ! $user->is($expected)) {
            abort(403, 'Not Authorized');
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        Auth::guard()->logoutOtherDevices($newPassword);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip' => request()->ip(),
                'details' => (string) request()->userAgent(),
            ])
            ->useLog(LOGNAME_CHANGE_PASSWORD)
            ->log('Changed Profile Password.');
    }
}
