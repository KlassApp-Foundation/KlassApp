<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ParentProfileService;
use App\Support\ParentProfileTabs;
use App\Traits\ResetPasswordProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentProfileController extends Controller
{
    use ResetPasswordProcess;

    public function show(Request $request, string $name, ParentProfileService $profiles)
    {
        $schoolId = (int) Auth::user()->school_id;
        $parent = $profiles->find($schoolId, $name);
        $tab = (string) $request->query('tab', 'overview');
        if (! in_array($tab, ParentProfileTabs::VISIBLE, true)) {
            $tab = 'overview';
        }
        $key = filled($parent->name) ? $parent->name : $parent->id;

        return view('admin.parent.show', [
            'user' => $parent,
            'page' => $profiles->page($schoolId, $parent),
            'tab' => $tab,
            'tabs' => ParentProfileTabs::labels(),
            'showUrl' => url('/admin/parent/show/'.$key),
        ]);
    }

    public function deactivate(string $name, ParentProfileService $profiles)
    {
        $schoolId = (int) Auth::user()->school_id;
        $parent = $profiles->deactivate($schoolId, $name);
        $key = filled($parent->name) ? $parent->name : $parent->id;

        return redirect('/admin/parent/show/'.$key);
    }

    public function resetPassword(string $name, ParentProfileService $profiles)
    {
        $schoolId = (int) Auth::user()->school_id;
        $parent = $profiles->find($schoolId, $name);
        $this->resetPasswordToUser($parent);
        $key = filled($parent->name) ? $parent->name : $parent->id;

        return redirect('/admin/parent/show/'.$key);
    }
}
