<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExamType;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// ========= ADDED FOR UGANDAN SCHOOLS ===========
class FilterMarksForm extends Controller
{
    //
    
    public function filterForm(){
    $school_id = Auth::user()->school_id;

    $standards = Standard::where("school_id", $school_id)->get();
    $years   = AcademicYear::where("school_id", $school_id)->get();
    $terms   = [1,2,3];
    $subjects = Subject::where("school_id", $school_id)->get();
    $classes = Section::where("school_id", $school_id)->get();
     $examTypes = ExamType::all();
    return view('admin.marks.filter', compact(
        'standards','years','terms', "subjects", "classes", "examTypes"
        ));
}

}
