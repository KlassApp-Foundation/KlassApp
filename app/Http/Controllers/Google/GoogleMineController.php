<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Services\Google\GoogleConnectionService;
use Illuminate\Support\Facades\Auth;

class GoogleMineController extends Controller
{
    public function index(GoogleConnectionService $connections)
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

        return view('google.mine', [
            'layout' => $layout,
            'googleConnectors' => $connections->cardRows($user),
        ]);
    }
}
