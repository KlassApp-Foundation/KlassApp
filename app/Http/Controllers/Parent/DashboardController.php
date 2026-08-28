<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('parent.dashboard');
    }
}
