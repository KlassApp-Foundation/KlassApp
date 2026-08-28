<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;

class ChildrenController extends Controller
{
    public function index()
    {
        return view('parent.children');
    }
}
