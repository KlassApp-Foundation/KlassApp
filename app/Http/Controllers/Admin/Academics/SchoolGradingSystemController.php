<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSchoolGradingSystem;
use App\Http\Requests\UpdateSchoolGradingSystem;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\AcademicYear;
use App\Models\Standard;
use App\Services\GradingSystemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolGradingSystemController extends Controller
{
    // protected $gradingSystem;
    // public function __construct(GradingSystemService $gradingSystem)
    // {
    //      $this->gradingSystem = $gradingSystem;
    // }
// form data
private function formatData(){
        $school_id = Auth::user()->school_id;
        return [
         "standards" => Standard::where("school_id", $school_id)->get()
        ];
    }
      //store comments
    public function create(){
        // dd($grade);
        $school_id = Auth::user()->school_id;
        
        $standards = Standard::where("school_id", $school_id)->get();
         return view("admin.school.grades.create", compact("standards"));
    }

     public function store(CreateSchoolGradingSystem $request){
        $validated = $request->validated();
        SchoolGradingSystem::create($validated);
        return redirect()->route("admin.grades")->with("successmessage", "Grade Rule created!");
    }
    // retrieve comments
    public function index(){
        $schoolId = Auth::user()->school_id;
        $gradingRules = SchoolGradingSystem::where("school_id", $schoolId)->orderByDesc("max_score")->get();
        return view("admin.school.grades.index", compact("gradingRules"));
    }
    // update comments
    public function edit(SchoolGradingSystem $grade){
        // dd($grade);
    $school_id = auth()->user()->school_id;
    $standards = Standard::where("school_id", $school_id)->get();
    // $grade = SchoolGradingSystem::where("school_id", $school_id)->
    return view("admin.school.grades.create", compact("standards", "grade"));
}
    public function update(UpdateSchoolGradingSystem $request, SchoolGradingSystem $grade){
        $validated = $request->validated();
        $grade->update($validated);
       return redirect()->route("admin.grades")->with("successmessage", "Grade updated successfully!");
    }

    // create many
    public function storeMany(Request $request)
{
    $request->validate([
        'standard_id' => 'required|exists:standards,id',
        'grades' => 'required|array|min:1',

        'grades.*.grade' => 'required|string|max:2',
        'grades.*.min_score' => 'required|integer|min:0|max:100',
        'grades.*.max_score' => 'required|integer|min:0|max:100',
    ]);

    $school_id = auth()->user()->school_id;

    $data = [];

    foreach ($request->grades as $row) {
        $data[] = [
            'school_id' => $school_id,
            'standard_id' => $request->standard_id,
            'grade' => $row['grade'],
            'min_score' => $row['min_score'],
            'max_score' => $row['max_score'],
            'remark' => $row['remark'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    SchoolGradingSystem::insert($data);

    return redirect()->route('admin.grades')
        ->with('successmessage', 'Grading system saved!');
}
    // delete comment
    public function destroy(SchoolGradingSystem $grade){
       if ($grade->delete()) {
           return redirect()->route("admin.grades")
                      ->with("successmessage", "Grade Rule deleted!");
          }
        return redirect()->route("admin.grades")->with("successmessage", "Failed to delete grade!");
    }
}
