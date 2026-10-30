<?php

namespace App\Http\Controllers\Admin\Setting;

/**
 * Site title, logo, favicon, and SEO live on the global settings table, which
 * has no school_id. These legacy pages are closed for every signed-in user,
 * including a platform admin. The stored values stay as they are.
 */
trait DeniesSchoolAdminBranding
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort(403);
        });
    }
}
