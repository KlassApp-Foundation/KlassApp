<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TeacherProfileService;
use App\Support\TeacherProfileTabs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherProfileController extends Controller
{
    public function show(Request $request, string $name, TeacherProfileService $profiles)
    {
        $schoolId = (int) Auth::user()->school_id;
        $teacher = $profiles->find($schoolId, $name);
        $tab = (string) $request->query('tab', 'overview');
        if (! in_array($tab, TeacherProfileTabs::VISIBLE, true)) {
            $tab = 'overview';
        }

        return view('admin.teacher.show', [
            'user' => $teacher,
            'page' => $profiles->page($schoolId, $teacher),
            'tab' => $tab,
            'tabs' => TeacherProfileTabs::labels(),
        ]);
    }

    public function deactivate(string $name, TeacherProfileService $profiles)
    {
        $schoolId = (int) Auth::user()->school_id;
        $teacher = $profiles->deactivate($schoolId, $name);
        $key = filled($teacher->name) ? $teacher->name : $teacher->id;

        return redirect('/admin/teacher/show/'.$key);
    }
}
