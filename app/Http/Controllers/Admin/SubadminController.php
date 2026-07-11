<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Academics\Classes;
use App\Models\FeesCategories;

class SubadminController extends Controller
{
    /**
     * School Subadmin Dashboard — landing page after login.
     * Shows KPIs and quick links to all admin modules except Settings.
     */
    public function index()
    {
        $schoolId = Auth::user()->school_id;
        $user = Auth::user();

        // Basic counts for KPI cards
        $studentCount = User::BySchool($schoolId)->ByRole(6)->count();
        $teacherCount = User::BySchool($schoolId)->ByRole(5)->count();
        $classCount   = Classes::where('school_id', $schoolId)->count();
        $feeCount     = FeesCategories::where('school_id', $schoolId)->count();

        return view('admin.subadmin.dashboard', compact(
            'studentCount', 'teacherCount', 'classCount', 'feeCount', 'user'
        ));
    }
}
