<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\SectionRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\AcademicProcess;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Models\Section;
use App\Models\Standard;
use App\Traits\Common;
use Exception;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    use AcademicProcess;
    use LogActivity;
    use Common;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     public function index(){
        $school_id = Auth::user()->school_id;
        $sections = Section::where("school_id", $school_id)->get();
        return view("admin.school.sections.index", compact("sections"));
     }


 public function create(){
        $school_id = Auth::user()->school_id;
        $levels = Standard::where("school_id", $school_id)->get();

        $primary_nursery = [
                'Baby Class',  'Middle Class','Top Class', 'Primary One', 'Primary Two', 
                'Primary Three', 'Primary Four', 'Primary Five',   'Primary Six', 'Primary Seven',
                ];
                $secondary = ['Senior One', 'Senior Two','Senior Three', 'Senior Four', 'Senior Five', 'Senior Six'];
        $classes=[];
        // loop
        foreach ($levels as $level){
            if(in_array(strtolower($level->name), ["primary", "nursery"])){
                $classes = array_merge($classes, $primary_nursery);
            }elseif(in_array(strtolower($level->name), ["secondary"])){
                $classes = array_merge($classes, $secondary);
            }
        }
        // remove duplicates
        $classes = array_unique($classes);
        return view("admin.school.sections.create", compact("classes"));
     }

     public function save(Request $request){
        $school_id = Auth::user()->school_id;
        $validated = $request->validate(["name" =>[
            "required", "string", 
            Rule::unique("sections")->where("school_id", $school_id)->whereNull("deleted_at")
            ]]);
        Section::create([
            "name" => $validated["name"],
            "school_id" => $school_id,
            "status" => 1
        ]);
        return redirect()->route("admin.classes")->with("successmessage", "Class added!");
    }

    public function store(SectionRequest $request){
        dd($request);
        //
        try
        {
            $school_id = Auth::user()->school_id;
          
            $section = $this->createSection($school_id , $request);
            $message = trans('messages.add_success_msg',['module' => 'Section']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $section,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_ADD_SECTION,
                $message
            );
            
            $res['success'] = trans('messages.add_success_msg',['module' => 'Section']);

            return $res;
            // return redirect()->route("admin.classes")->with("successmessage", "Created class successfully!");
        }
        catch(Exception $e)
        {
            dd($e->getMessage());
        }
    }
public function destroy(Section $class){
    $user = Auth::user();
    // to add, check if isadmin
    $class->delete();
    return redirect()->route("admin.classes")->with("successmessage", "Class deleted successfully!");
}
   
}
