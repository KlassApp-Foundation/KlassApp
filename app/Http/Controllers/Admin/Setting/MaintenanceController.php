<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Traits\SettingProcess;
use App\Helpers\SiteHelper;
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

        // Teacher access to the reception-desk surfaces. Explicit on/off: an unticked box
        // writes '0', which matches the fail-safe default of disabled.
        $school->setDetailValue(SiteHelper::TEACHER_RECEPTIONIST_ACCESS_KEY, $request->input('teacher_receptionist_access') == 1 ? '1' : '0');
        SiteHelper::forgetTeacherReceptionistAccess((int) $school->id);

        // Attendance scope (classes_i_teach | class_teacher_only | school_wide).
        // Fail-safe server-side: an unknown or missing value stores nothing, which leaves
        // the default in force. The scope is read from the authenticated user's school.
        $scope = $request->input('attendance_scope');
        if (is_string($scope) && in_array(trim($scope), SiteHelper::ATTENDANCE_SCOPES, true)) {
            $school->setDetailValue(SiteHelper::ATTENDANCE_SCOPE_KEY, trim($scope));
            SiteHelper::forgetAttendanceScope((int) $school->id);
        }

        // NOTE: 'register'/'register_status' is a PLATFORM-level switch (public
        // signup) and is intentionally NOT writable from a school admin page.
        // It is managed by SiteAdmin (SystemSettingsService).

        return redirect()->back();
    }
}
