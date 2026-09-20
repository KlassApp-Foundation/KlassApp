<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Traits\SettingProcess;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    use SettingProcess;
 
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('admin.settings.maintenancesettings');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $school = \Auth::user()->school;

        if (! $school) {
            abort(403);
        }

        // MULTI-TENANCY: write ONLY to this admin's own school, resolved from the
        // authenticated user. A school id is NEVER taken from request input, so a
        // tampered request cannot reach another school's settings.
        $school->setDetailValue('login_status', $request->login_status == 1 ? '1' : '0');
        $school->setDetailValue('maintenance', $request->maintenance == 1 ? '1' : '0');

        // NOTE: 'register'/'register_status' is a PLATFORM-level switch (public
        // signup) and is intentionally NOT writable from a school admin page.
        // It is managed by SiteAdmin (SystemSettingsService).

        return redirect()->back();
    }
}
