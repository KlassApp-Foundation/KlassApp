<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\TimetableSlot;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Models\Teacherlink;
use App\Helpers\SiteHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TimetableSlotController extends Controller
{
    public function teacherIndex(Request $request)
    {
        [$teacher, $year, $sections, $subjects, $terms, $years] = $this->teacherOptions();
        $slots = TimetableSlot::where('school_id', $teacher->school_id)
            ->where('academic_year_id', $year?->id)
            ->where('teacher_id', $teacher->id)
            ->with(['subject', 'section', 'term'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return view('teacher.timetable.manage', compact('slots', 'sections', 'subjects', 'terms', 'years'));
    }

    public function teacherCreate()
    {
        [, , $sections, $subjects, $terms, $years] = $this->teacherOptions();
        return view('teacher.timetable.form', compact('sections', 'subjects', 'terms', 'years'));
    }

    public function teacherStore(Request $request)
    {
        $teacher = Auth::user();
        $data = $this->validateTeacherSlot($request, $teacher->id);
        $data['school_id'] = $teacher->school_id;
        $data['teacher_id'] = $teacher->id;
        $data['academic_year_id'] = $this->teacherYear($teacher->school_id)->id;

        $this->saveTeacherSlot($data);

        return redirect()->route('teacher.timetable.index')->with('successmessage', 'Timetable slot added.');
    }

    public function teacherEdit(TimetableSlot $slot)
    {
        $this->authorizeTeacherSlot($slot);
        [, , $sections, $subjects, $terms, $years] = $this->teacherOptions();
        return view('teacher.timetable.form', compact('sections', 'subjects', 'terms', 'years', 'slot'));
    }

    public function teacherUpdate(Request $request, TimetableSlot $slot)
    {
        $this->authorizeTeacherSlot($slot);
        $data = $this->validateTeacherSlot($request, $slot->teacher_id, $slot->id);
        $data['academic_year_id'] = $slot->academic_year_id;
        $slot->update($data);
        $slot->syncCalendarEvent();

        return redirect()->route('teacher.timetable.index')->with('successmessage', 'Timetable slot updated.');
    }

    public function teacherDestroy(TimetableSlot $slot)
    {
        $this->authorizeTeacherSlot($slot);
        \App\Models\Events::where('timetable_slot_id', $slot->id)->delete();
        $slot->delete();

        return redirect()->route('teacher.timetable.index')->with('successmessage', 'Timetable slot removed.');
    }

    private function teacherOptions(): array
    {
        $teacher = Auth::user();
        $year = $this->teacherYear($teacher->school_id);
        $links = $year
            ? Teacherlink::query()->where('school_id', $teacher->school_id)->where('academic_year_id', $year->id)->where('teacher_id', $teacher->id)->get()
            : collect();
        $sections = Section::whereIn('id', $links->map(fn ($link) => $link->standardLink?->section_id)->filter()->unique())->orderBy('name')->get();
        $subjects = Subject::whereIn('id', $links->pluck('subject_id')->unique())->orderBy('name')->get();
        $terms = AcademicTerm::where('school_id', $teacher->school_id)->get();
        $years = AcademicYear::where('school_id', $teacher->school_id)->orderByDesc('id')->get();

        return [$teacher, $year, $sections, $subjects, $terms, $years];
    }

    private function teacherYear(int $schoolId): ?AcademicYear
    {
        return SiteHelper::getAcademicYear($schoolId) ?? AcademicYear::where('school_id', $schoolId)->orderByDesc('id')->first();
    }

    private function validateTeacherSlot(Request $request, int $teacherId, ?int $excludeId = null): array
    {
        $data = $request->validate([
            'section_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'academic_term_id' => 'nullable|integer',
        ]);

        $year = $this->teacherYear(Auth::user()->school_id);
        $allowed = Teacherlink::where('school_id', Auth::user()->school_id)
            ->where('academic_year_id', $year?->id)
            ->where('teacher_id', $teacherId)
            ->where('subject_id', $data['subject_id'])
            ->whereHas('standardLink', fn ($query) => $query->where('section_id', $data['section_id']))
            ->exists();
        abort_unless($allowed, 403, 'You can only manage timetable slots for your assigned classes and subjects.');

        $conflict = TimetableSlot::detectConflict(Auth::user()->school_id, $data['section_id'], $teacherId, $data['day_of_week'], $data['start_time'], $data['end_time'], $excludeId);
        if ($conflict) {
            throw ValidationException::withMessages(['conflict' => $conflict]);
        }

        return $data;
    }

    private function saveTeacherSlot(array $data): void
    {
        $slot = TimetableSlot::create($data);
        $slot->syncCalendarEvent();
    }

    private function authorizeTeacherSlot(TimetableSlot $slot): void
    {
        abort_unless((int) $slot->school_id === (int) Auth::user()->school_id && (int) $slot->teacher_id === (int) Auth::id(), 403);
    }

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
