<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherExamRequest;
use App\Http\Requests\Teacher\UpdateTeacherExamRequest;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamAuthorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(private ExamAuthorization $examAuthorization)
    {
    }

    public function create(Request $request): View
    {
        $teacher = $this->actor();
        $schoolId = (int) $teacher->school_id;
        $year = $this->currentYear($schoolId);

        if (! $year) {
            abort(403, 'No current academic year configured.');
        }

        $sectionIds = $this->examAuthorization->sectionIdsForClassTeacher($teacher, $schoolId, (int) $year->id);
        if ($sectionIds === []) {
            abort(403, 'Only class teachers can create exams.');
        }

        $sections = Section::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $sectionIds)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $selectedSectionId = (int) $request->get('section', $sections->first()?->id);
        if ($selectedSectionId && ! in_array($selectedSectionId, $sectionIds, true)) {
            abort(403, 'You are not the class teacher for this class.');
        }

        $subjects = Subject::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $selectedSectionId)
            ->where('academic_year_id', $year->id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $defaultTeacherId = null;
        $selectedSubjectId = (int) old('subject_id', $request->get('subject_id', 0));
        if ($selectedSubjectId > 0) {
            $defaultTeacherId = $this->examAuthorization->defaultTeacherIdForSubject(
                $schoolId,
                (int) $year->id,
                $selectedSectionId,
                $selectedSubjectId,
                $teacher
            );
        }

        return view('teacher.exams.form', [
            'exam' => null,
            'sections' => $sections,
            'selectedSectionId' => $selectedSectionId,
            'subjects' => $subjects,
            'academicYears' => AcademicYear::where('school_id', $schoolId)->orderByDesc('id')->get(),
            'currentYearId' => $year->id,
            'terms' => AcademicTerm::where('school_id', $schoolId)->where('academic_year_id', $year->id)->get(),
            'examTypes' => ExamType::query()->orderBy('name')->get(),
            'teachers' => $this->schoolTeachers($schoolId),
            'defaultTeacherId' => $defaultTeacherId ?? $teacher->id,
        ]);
    }

    public function store(StoreTeacherExamRequest $request): RedirectResponse
    {
        $teacher = $this->actor();
        $validated = $request->validated();

        $this->examAuthorization->authorizeCreateOrAbort(
            $teacher,
            (int) $validated['school_id'],
            (int) $validated['academic_year_id'],
            (int) $validated['section_id']
        );

        $teacherId = ! empty($validated['teacher_id'])
            ? (int) $validated['teacher_id']
            : $this->examAuthorization->defaultTeacherIdForSubject(
                (int) $validated['school_id'],
                (int) $validated['academic_year_id'],
                (int) $validated['section_id'],
                (int) $validated['subject_id'],
                $teacher
            );

        $exam = Exam::create([
            'school_id' => $validated['school_id'],
            'standard_id' => $validated['standard_id'],
            'section_id' => $validated['section_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'academic_term_id' => $validated['academic_term_id'],
            'subject_id' => $validated['subject_id'],
            'teacher_id' => $teacherId,
            'exam_type_id' => $validated['exam_type_id'],
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => 'undone',
        ]);

        activity('exams')
            ->performedOn($exam)
            ->causedBy($teacher)
            ->withProperties([
                'action' => 'teacher_create',
                'teacher_id' => $teacherId,
                'section_id' => $exam->section_id,
                'subject_id' => $exam->subject_id,
            ])
            ->log('Class teacher created exam');

        return redirect()
            ->route('teacher.exam.marks')
            ->with('successmessage', 'Exam created successfully.');
    }

    public function edit(Exam $exam): View
    {
        $teacher = $this->actor();
        $this->examAuthorization->authorizeOrAbort($teacher, $exam);

        $schoolId = (int) $teacher->school_id;
        $yearId = (int) $exam->academic_year_id;
        $sectionIds = $this->examAuthorization->sectionIdsForClassTeacher($teacher, $schoolId, $yearId);

        // Subject teachers (non-CT) may edit only via canActOnExam (their assigned exam),
        // but section dropdown stays limited to the exam's section.
        $sections = Section::query()
            ->where('school_id', $schoolId)
            ->where(function ($q) use ($sectionIds, $exam) {
                if ($sectionIds !== []) {
                    $q->whereIn('id', $sectionIds);
                } else {
                    $q->whereKey($exam->section_id);
                }
            })
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $subjects = Subject::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $exam->section_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('teacher.exams.form', [
            'exam' => $exam,
            'sections' => $sections,
            'selectedSectionId' => $exam->section_id,
            'subjects' => $subjects,
            'academicYears' => AcademicYear::where('school_id', $schoolId)->orderByDesc('id')->get(),
            'currentYearId' => $yearId,
            'terms' => AcademicTerm::where('school_id', $schoolId)->where('academic_year_id', $yearId)->get(),
            'examTypes' => ExamType::query()->orderBy('name')->get(),
            'teachers' => $this->schoolTeachers($schoolId),
            'defaultTeacherId' => $exam->teacher_id,
        ]);
    }

    public function update(UpdateTeacherExamRequest $request, Exam $exam): RedirectResponse
    {
        $teacher = $this->actor();
        $this->examAuthorization->authorizeOrAbort($teacher, $exam);

        $validated = $request->validated();
        $previousTeacherId = (int) $exam->teacher_id;

        // Keep class scope fixed on edit — CT cannot move exam to another section via this form.
        unset($validated['section_id'], $validated['standard_id'], $validated['school_id']);

        if (array_key_exists('teacher_id', $validated) && $validated['teacher_id'] === null) {
            unset($validated['teacher_id']);
        }

        $exam->update($validated);
        $exam->refresh();

        if ((int) $exam->teacher_id !== $previousTeacherId) {
            activity('exams')
                ->performedOn($exam)
                ->causedBy($teacher)
                ->withProperties([
                    'action' => 'teacher_reassign',
                    'from_teacher_id' => $previousTeacherId,
                    'to_teacher_id' => (int) $exam->teacher_id,
                ])
                ->log('Exam teacher reassigned');
        }

        return redirect()
            ->route('teacher.exam.marks')
            ->with('successmessage', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $teacher = $this->actor();
        $this->examAuthorization->authorizeOrAbort($teacher, $exam, 'You are not authorized to delete this exam.');

        if (in_array($exam->status, ['submitted', 'done'], true)) {
            return redirect()
                ->route('teacher.exam.marks')
                ->with('errormessage', 'Cannot delete a submitted or completed exam.');
        }

        activity('exams')
            ->performedOn($exam)
            ->causedBy($teacher)
            ->withProperties(['action' => 'teacher_delete'])
            ->log('Class teacher deleted exam');

        $exam->delete();

        return redirect()
            ->route('teacher.exam.marks')
            ->with('successmessage', 'Exam deleted successfully.');
    }

    private function actor(): User
    {
        $user = Auth::user();
        if (! $user instanceof User || (int) $user->usergroup_id !== 5) {
            abort(403, 'Not Authorized');
        }

        return $user;
    }

    private function currentYear(int $schoolId): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first()
            ?? AcademicYear::query()
                ->where('school_id', $schoolId)
                ->where('name', (string) now()->year)
                ->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function schoolTeachers(int $schoolId)
    {
        return User::query()
            ->with('userprofile')
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}
