<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\TimetableSlot;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimetableSlotController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $sectionId = $request->input('section_id');

        $sections = Section::where('school_id', $schoolId)->get();
        $terms = AcademicTerm::where('school_id', $schoolId)->get();
        $years = AcademicYear::where('school_id', $schoolId)->get();

        $slots = collect();
        if ($sectionId) {
            $slots = TimetableSlot::where('school_id', $schoolId)
                ->where('section_id', $sectionId)
                ->with(['subject', 'teacher', 'term', 'section'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->groupBy('day_of_week');
        }

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return view('admin.timetable.index', compact('sections', 'terms', 'years', 'slots', 'sectionId', 'dayNames'));
    }

    public function create(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $sectionId = $request->input('section_id');

        $sections = Section::where('school_id', $schoolId)->get();
        $terms = AcademicTerm::where('school_id', $schoolId)->get();
        $years = AcademicYear::where('school_id', $schoolId)->get();
        $subjects = Subject::where('school_id', $schoolId)->get();
        $teachers = User::where('school_id', $schoolId)->where('usergroup_id', 5)->get();

        return view('admin.timetable.form', compact('sections', 'terms', 'years', 'subjects', 'teachers', 'sectionId'));
    }

    public function store(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $data = $request->validate([
            'section_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'teacher_id' => 'required|integer',
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'academic_term_id' => 'nullable|integer',
        ]);

        $data['school_id'] = $schoolId;
        $data['academic_year_id'] = $request->input('academic_year_id', AcademicYear::where('school_id', $schoolId)->where('status', 1)->first()?->id);

        if (empty($data['academic_year_id'])) {
            return back()->withErrors(['academic_year_id' => 'No active academic year found.'])->withInput();
        }

        // Conflict detection
        $conflict = TimetableSlot::detectConflict(
            $schoolId, $data['section_id'], $data['teacher_id'],
            $data['day_of_week'], $data['start_time'], $data['end_time']
        );
        if ($conflict) {
            return back()->withErrors(['conflict' => $conflict])->withInput();
        }

        // class_teacher_links warning
        $warning = TimetableSlot::checkTeacherLink(
            $schoolId, $data['teacher_id'], $data['subject_id'], $data['section_id']
        );

        $slot = TimetableSlot::create($data);
        $slot->syncCalendarEvent();

        return redirect()->route('admin.timetable.index', ['section_id' => $data['section_id']])
            ->with('successmessage', 'Timetable slot added.' . ($warning ? " {$warning}" : ''));
    }

    public function edit(TimetableSlot $slot)
    {
        $schoolId = Auth::user()->school_id;
        abort_if($slot->school_id !== $schoolId, 403);

        $sections = Section::where('school_id', $schoolId)->get();
        $terms = AcademicTerm::where('school_id', $schoolId)->get();
        $years = AcademicYear::where('school_id', $schoolId)->get();
        $subjects = Subject::where('school_id', $schoolId)->get();
        $teachers = User::where('school_id', $schoolId)->where('usergroup_id', 5)->get();

        return view('admin.timetable.form', compact('sections', 'terms', 'years', 'subjects', 'teachers', 'slot'));
    }

    public function update(Request $request, TimetableSlot $slot)
    {
        $schoolId = Auth::user()->school_id;
        abort_if($slot->school_id !== $schoolId, 403);

        $data = $request->validate([
            'section_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'teacher_id' => 'required|integer',
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'academic_term_id' => 'nullable|integer',
        ]);

        $data['academic_year_id'] = $request->input('academic_year_id', $slot->academic_year_id);

        // Conflict detection (exclude self)
        $conflict = TimetableSlot::detectConflict(
            $schoolId, $data['section_id'], $data['teacher_id'],
            $data['day_of_week'], $data['start_time'], $data['end_time'],
            $slot->id
        );
        if ($conflict) {
            return back()->withErrors(['conflict' => $conflict])->withInput();
        }

        $warning = TimetableSlot::checkTeacherLink(
            $schoolId, $data['teacher_id'], $data['subject_id'], $data['section_id']
        );

        $slot->update($data);
        $slot->syncCalendarEvent();

        return redirect()->route('admin.timetable.index', ['section_id' => $data['section_id']])
            ->with('successmessage', 'Timetable slot updated.' . ($warning ? " {$warning}" : ''));
    }

    public function destroy(TimetableSlot $slot)
    {
        $schoolId = Auth::user()->school_id;
        abort_if($slot->school_id !== $schoolId, 403);

        // Calendar events cascade via nullOnDelete FK, but explicitly
        // remove them to be safe (same pattern as exam-calendar delete)
        \App\Models\Events::where('timetable_slot_id', $slot->id)->delete();
        $slot->delete();

        return redirect()->route('admin.timetable.index', ['section_id' => $slot->section_id])
            ->with('successmessage', 'Timetable slot removed.');
    }

    public function teacherWeekly()
    {
        $teacherId = Auth::user()->id;
        $schoolId = Auth::user()->school_id;

        $slots = TimetableSlot::where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->with(['subject', 'section', 'term'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return view('admin.timetable.teacher-weekly', compact('slots', 'dayNames'));
    }
}
