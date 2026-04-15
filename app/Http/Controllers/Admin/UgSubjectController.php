<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUgSubjectRequest;
use App\Http\Requests\UpdateUgSubjectRequest;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UgSubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected function shipData(){
         $school_id = Auth::user()->school_id;
         return [
            // "sections" => Section::where("school_id", $school_id)->get(),
        "standards" => Standard::where("school_id", $school_id)->get(),
        "types" => ["Core", "Elective"]
         ];
    }
    public function index()
    {

        $school_id = Auth::user()->school_id;
        $subject = Subject::where("school_id", $school_id)->get();
        $archievedSubjects = Subject::onlyTrashed() 
                                 ->where("school_id", $school_id)
                                //  ->where("section_id", $section)
                                 ->get();      
        // dd($subject);
        return view("admin.subject.index", compact(
            "subject", "school_id", "archievedSubjects"
            ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("admin.subject.create", $this->shipData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUgSubjectRequest $request)
    {
        //
        $validated = $request->validated();
        Subject::create($validated);
        return redirect()->route("admin.subjects")->with("successmessage", "Subject Added!");
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subject $subject)
    {
        return view("admin.subject.create", array_merge(
            $this->shipData(),
            ["subject" => $subject]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUgSubjectRequest $request, string $subject)
    {
        //
        $validated = $request->validated();
        // dd($validated);
        Subject::where("id", $subject)->update($validated);
        return redirect()->route("admin.subjects")->with("successmessage", "Subject Updated!");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $subject)
    {
        $school_id = Auth::user()->school_id;
        Subject::where("id", $subject)->where("school_id", $school_id)->delete();
        return redirect()->route("admin.subjects")->with("successmessage", "Subject Moved To Trash!");
    }

    // restore deleted subject
     public function restore(string $subject)
    {
        Subject::withTrashed()->find($subject)->restore();
        return redirect()->route("admin.subjects")->with("successmessage", "Subject Restored!");
    }

    // force delete
     public function forceDestroy(string $subject)
    {
        Subject::withTrashed()->find($subject)->forceDelete();
        return redirect()->route("admin.subjects")->with("successmessage", "Subject Deleted!");
    }
}
