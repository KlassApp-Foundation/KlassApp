<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ConnectedTools\ConnectedToolsRegistry;
use Illuminate\Support\Facades\Auth;

class ConnectedToolsController extends Controller
{
    public function index(ConnectedToolsRegistry $tools)
    {
        $schoolId = (int) Auth::user()->school_id;

        if ($schoolId < 1) {
            abort(404);
        }

        return view('admin.connected-tools.index', [
            'tools' => $tools->cardsFor($schoolId),
        ]);
    }
}
