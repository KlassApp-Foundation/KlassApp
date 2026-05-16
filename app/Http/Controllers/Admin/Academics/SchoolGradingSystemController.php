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
        $school_id = Auth::user()->school_id;
        $standard = Standard::where("school_id", $school_id)->value("name");
        $rankname = "";
        if($standard === "nursery" || $standard === "primary"){
            $rankname = "Rank";
        }else{
            $rankname = "Points";
        }
        
        $standards = Standard::where("school_id", $school_id)->get();
         return view("admin.school.grades.create", compact("standards", "rankname"));
    }

     public function store(CreateSchoolGradingSystem $request){
        $validated = $request->validated();
        SchoolGradingSystem::create($validated);
        return redirect()->route("admin.grades")->with("successmessage", "Grade Rule created!");
    }
    // retrieve comments
    public function index(){
        $schoolId = Auth::user()->school_id;
        $gradingRules = SchoolGradingSystem::with("standard")->where("school_id", $schoolId)->orderByDesc("max_score")->get();
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
        // dd($validated);
        $grade->update($validated);
       return redirect()->route("admin.grades")->with("successmessage", "Grade updated successfully!");
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
