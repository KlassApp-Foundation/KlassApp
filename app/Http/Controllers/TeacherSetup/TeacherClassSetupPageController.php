<?php

namespace App\Http\Controllers\TeacherSetup;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TeacherClassSetupPageController extends Controller
{
    public function show(): View
    {
        return view('teacher-setup.page');
    }
}
