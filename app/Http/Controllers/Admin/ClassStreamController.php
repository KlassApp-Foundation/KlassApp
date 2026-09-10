<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Services\ClassStructureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ClassStreamController extends Controller
{
    public function __construct(private ClassStructureService $structure)
    {
    }

    public function create(Section $section)
    {
        $user = Auth::user();
        abort_if(
            (int) $user->school_id !== (int) $section->school_id,
            403,
            'You are not authorized for this school.'
        );
        abort_if((int) $section->status !== 1, 404, 'Class not found.');

        $base = $this->structure->resolveBaseSection($section);

        return view('admin.class-stream.create', [
            'section' => $section,
            'base' => $base,
        ]);
    }

    public function store(Request $request, Section $section)
    {
        $user = Auth::user();
        abort_if(
            (int) $user->school_id !== (int) $section->school_id,
            403,
            'You are not authorized for this school.'
        );
        abort_if((int) $section->status !== 1, 404, 'Class not found.');

        $validated = $request->validate([
            'stream' => 'required|string|max:50',
        ]);

        $school = $user->school;
        $year = SiteHelper::getAcademicYear((int) $user->school_id);
        if (! $year) {
            return redirect()
                ->route('admin.classes')
                ->with('errormessage', 'Set an academic year before adding streams.');
        }

        try {
            $result = $this->structure->addStream($school, $year, $section, $validated['stream']);
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors());
        }

        $name = $result['section']->name;
        $msg = $result['created']
            ? "Stream \"{$name}\" added. Base class kept."
            : "Stream \"{$name}\" already existed.";

        return redirect()->route('admin.classes')->with('successmessage', $msg);
    }
}
