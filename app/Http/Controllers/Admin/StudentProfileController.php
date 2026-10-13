<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StudentProfileService;
use App\Support\StudentProfileTabs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentProfileController extends Controller
{
    public function show(Request $request, int $student, StudentProfileService $profiles)
    {
        $schoolId = (int) Auth::user()->school_id;
        $record = $profiles->find($schoolId, $student);
        $tab = (string) $request->query('tab', 'overview');
        if (! in_array($tab, StudentProfileTabs::VISIBLE, true)) {
            $tab = 'overview';
        }

        return view('admin.students.profile', [
            'page' => $profiles->page($schoolId, $record),
            'tab' => $tab,
            'tabs' => StudentProfileTabs::labels(),
        ]);
    }

    public function deactivate(int $student, StudentProfileService $profiles)
    {
        $schoolId = (int) Auth::user()->school_id;
        $record = $profiles->deactivate($schoolId, $student);

        return redirect('/admin/students/'.$record->id);
    }
}
