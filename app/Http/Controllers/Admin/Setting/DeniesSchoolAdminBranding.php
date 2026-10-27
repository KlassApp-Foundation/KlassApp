<?php

namespace App\Http\Controllers\Admin\Setting;

/**
 * Site title, logo, favicon, and SEO live on the global settings table, which
 * has no school_id. Only a platform admin (usergroup 1) may open these routes.
 * A school admin is refused before any write.
 */
trait DeniesSchoolAdminBranding
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if ($user === null || (int) $user->usergroup_id !== 1) {
                abort(403);
            }

            return $next($request);
        });
    }
}
