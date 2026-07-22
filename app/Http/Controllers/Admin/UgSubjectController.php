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
         $sections = Section::where("school_id", $school_id)->with('standardLink')->get();
         // Map section_id -> standard_id for the auto-populating JS in the form
         $sectionStandardMap = [];
         foreach ($sections as $section) {
             $firstLink = $section->standardLink->first();
             $sectionStandardMap[$section->id] = $firstLink?->standard_id ?? '';
         }
         return [
            "sections" => $sections,
        "standards" => Standard::where("school_id", $school_id)->get(),
        "types" => ["Core", "Elective"],
        "sectionStandardMap" => $sectionStandardMap,
         ];
    }
    public function index(Request $request)
    {
        $school_id = Auth::user()->school_id;
        $search = $request->get('search');

        $query = Subject::where("school_id", $school_id);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        $subject = $query->get();

        $archivedQuery = Subject::onlyTrashed()->where("school_id", $school_id);
        if ($search) {
            $archivedQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        $archievedSubjects = $archivedQuery->get();

        return view("admin.subject.index", compact(
            "subject", "school_id", "archievedSubjects", "search"
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
