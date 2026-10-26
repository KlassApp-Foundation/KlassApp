<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Standard;
use App\Services\ClassTeacherAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassesController extends Controller
{
    //
    public function create(){
        $school_id = Auth::user()->school_id;
        $standards = Standard::where("school_id", $school_id)->get();
        $sects = Section::where("school_id", $school_id)->get();
        $names = app(ClassTeacherAssignmentService::class)->namesForSchool((int) $school_id);
        $nursery_classes = [];
        $primary_classes = $names;
        $secondary_classes = [];

        return view("admin.classes.create", compact(
            "school_id", "standards", "nursery_classes", "primary_classes", "secondary_classes", "sects"
            ));
    }


    public function store(Request $request, ClassTeacherAssignmentService $assignments)
    {
        $validated = $request->validate([
            'standard_id' => 'required',
            'name' => 'required|string|max:255',
        ]);

        $assignments->storeNamedClass(
            Auth::user(),
            (int) $validated['standard_id'],
            (string) $validated['name'],
        );

        return redirect()->route('admin.classes.add')->with('successmessage', 'Class created successfully!');
    }
}
