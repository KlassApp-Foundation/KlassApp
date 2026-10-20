<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ConnectedTools\ConnectedToolsRegistry;
use App\Services\Google\GoogleConnectionService;
use Illuminate\Support\Facades\Auth;

class ConnectedToolsController extends Controller
{
    public function index(ConnectedToolsRegistry $tools, GoogleConnectionService $connections)
    {
        $user = Auth::user();

        if (! $connections->isSchoolMember($user)) {
            abort(404);
        }

        $layout = match ((int) $user->usergroup_id) {
            5 => 'layouts.teacher.layout',
            6 => 'layouts.student.layout',
            7 => 'layouts.parent.layout',
            default => 'layouts.admin.layout',
        };

        return view('admin.connected-tools.index', [
            'layout' => $layout,
            'tools' => $tools->cardsFor($user),
            'allowRows' => (int) $user->usergroup_id === 3
                ? $connections->allowRows((int) $user->school_id)
                : null,
        ]);
    }
}
