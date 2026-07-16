<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExamType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamTypeController extends Controller
{
    public function index()
    {
        $schoolId = Auth::user()->school_id;
        $examTypes = ExamType::orderBy('id')->get();

        // Load current toggles: each school's preference is stored in a JSON column
        // on the schools table, or falls back to the exam_types default.
        $school = \App\Models\School::find($schoolId);
        $prefs = [];
        if ($school && $school->exam_type_preferences) {
            $prefs = is_array($school->exam_type_preferences)
                ? $school->exam_type_preferences
                : json_decode($school->exam_type_preferences, true) ?? [];
        }

        return view('admin.settings.exam-types', compact('examTypes', 'prefs'));
    }

    public function update(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $school = \App\Models\School::findOrFail($schoolId);

        $prefs = $request->input('contributes', []);
        $school->exam_type_preferences = $prefs;
        $school->save();

        return redirect()->route('admin.settings.exam-types')
            ->with('successmessage', 'Exam type preferences updated.');
    }
}
