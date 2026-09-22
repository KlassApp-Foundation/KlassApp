<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Gates the teacher copies of the reception-desk surfaces (visitor log, call log,
 * postal record) behind a per-school setting, teacher_receptionist_access.
 *
 * Why this exists: those teacher routes were reachable by any teacher with no check at
 * all, they appear nowhere in the teacher sidebar, and the receptionist controllers are
 * the legitimate owners of the domain. The setting is DISABLED by default.
 *
 * Read actions are deliberately left alone: the audit finding was about writes. The one
 * exception is destructive-by-GET: /delete/{id} routes are Route::get, so a method-only
 * check would have missed every delete. Those are gated by path.
 */
namespace App\Http\Middleware;

use App\Helpers\SiteHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherReceptionistAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (int) $user->usergroup_id !== 5) {
            return $next($request); // the setting governs teachers only
        }

        $method = $request->getMethod();
        $isRead = in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
        $isDeleteViaGet = ! $isRead ? false : $request->is('teacher/*/delete/*');

        if ($isRead && ! $isDeleteViaGet) {
            return $next($request);
        }

        if (SiteHelper::teacherReceptionistAccessEnabled((int) $user->school_id)) {
            return $next($request);
        }

        abort(403, 'Reception-desk records are not enabled for teachers at this school.');
    }
}
