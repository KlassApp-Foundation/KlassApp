<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Standard;
use Illuminate\Http\Request;

// ========= ADDED FOR UGANDAN SCHOOLS ===========
class FilterMarksForm extends Controller
{
    //
    public function filterForm(){
    $classes = Standard::all();
    $years   = AcademicYear::all();
    $terms   = [1,2,3];

    return view('admin.marks.filter', compact('classes','years','terms'));
}

}
