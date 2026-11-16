<?php

namespace App\Http\Middleware;

use App\Support\SchoolAdmin;
use Closure;

/**
 * Settings, school details, and the other owner screens.
 *
 * Site admin, school admin, and a head teacher (usergroup 4) pass, and so
 * does a co-admin (usergroup 2). A head teacher may open only their own
 * school: another school's id is not found.
 */
class MustBeFullSchoolAdmin
{
    public function handle($request, Closure $next)
    {
        $user = \Auth::user();

        if (! $this->allows($user)) {
            abort(404);
        }

        if ($this->namesAnotherSchool($request, $user)) {
            abort(404);
        }

        return $next($request);
    }

    private function allows($user): bool
    {
        if ($user === null) {
            return false;
        }

        // Co-admins kept this access before head teachers were added.
        if ((int) $user->usergroup_id === 2) {
            return true;
        }

        return SchoolAdmin::is($user);
    }

    /**
     * School-details URLs carry {school_id}. A head teacher of a different
     * school must not see that page.
     */
    private function namesAnotherSchool($request, $user): bool
    {
        if ((int) $user->usergroup_id !== 4) {
            return false;
        }

        $schoolId = $request->route('school_id');
        if ($schoolId === null || $schoolId === '') {
            return false;
        }

        return (int) $user->school_id !== (int) $schoolId;
    }
}
