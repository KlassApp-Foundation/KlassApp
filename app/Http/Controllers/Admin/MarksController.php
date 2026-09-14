<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use App\Services\GradingSystemService;
use App\Services\StudentPromotionService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class MarksController extends Controller
{
    protected $studentPromotion;

    public function __construct(StudentPromotionService $studentPromotion)
    {
        $this->studentPromotion = $studentPromotion;
    }

    public function schoolMarksOverview()
    {
        $schoolId = Auth::user()->school_id;

        $students = User::with(
            ['marks.subject', 'marks.exam', 'marks.student', 'marks.teacher', 'marks.school']
        )
            ->whereHas('marks.exam', function ($query) use ($schoolId) {
                $query->forSchool($schoolId);
            })
            ->where('usergroup_id', 6)
            ->get();

        $studentCount = $students->pluck('student_id')->unique()->count();
        $subjects = Subject::where('school_id', $schoolId)->get();

        return view('admin.marks.school-overview', compact('students', 'studentCount', 'subjects'));
    }

    public function classExamOverview(Request $request, GradingSystemService $gradingSystem)
    {
        $schoolId = Auth::user()->school_id;
        $academicYear = SiteHelper::getAcademicYear($schoolId);
        $academic_year_id = $academicYear?->id
            ?? AcademicYear::where('school_id', $schoolId)->where('status', 1)->value('id');

        $years = AcademicYear::where('school_id', $schoolId)->get();
        $standards = Standard::where('school_id', $schoolId)->get();
        $classes = Section::where('school_id', $schoolId)->orderByDesc('id')->get();
        $terms = AcademicTerm::where('school_id', $schoolId)->get();
        $examTypes = ExamType::all();

        $base = [
            'years' => $years,
            'standards' => $standards,
            'classes' => $classes,
            'terms' => $terms,
            'examTypes' => $examTypes,
            'subjects' => collect(),
            'students' => new LengthAwarePaginator([], 0, 6),
            'missingSubjects' => collect(),
            'subjectsCovered' => 0,
            'class' => null,
            'type' => null,
            'exam' => null,
            'promotion' => null,
            'marks' => collect(),
            'year' => $academicYear?->name,
            'term' => $request->term,
            'termName' => null,
            'filtered' => false,
            'marksSubtitle' => 'Select class, term and exam type to open the marks grid.',
            'headers' => ['Total', 'Average', 'Grade', 'Position', 'Actions'],
        ];

        if (! $request->filled(['term', 'class', 'examType'])) {
            return view('admin.marks.filter', $base);
        }

        $query = User::query()
            ->with(['marks' => function ($q) use ($request) {
                $q->with('exam', 'subject')
                    ->whereHas('exam', function ($e) {
                        $e->where('status', 'submitted');
                    });

                $q->when($request->filled('term'), function ($q2) use ($request) {
                    $q2->whereHas('exam', fn ($e) => $e->where('academic_term_id', $request->term));
                });

                $q->when($request->filled('class'), function ($q2) use ($request) {
                    $q2->whereHas('exam', fn ($e) => $e->where('section_id', $request->class));
                });

                $q->when($request->filled('examType'), function ($q2) use ($request) {
                    $q2->whereHas('exam', fn ($e) => $e->where('exam_type_id', $request->examType));
                });

                $q->when($request->filled('year'), function ($q2) use ($request) {
                    $q2->whereHas('exam', fn ($e) => $e->where('academic_year_id', $request->year));
                });

                $q->when($request->filled('subject'), function ($q2) use ($request) {
                    $q2->where('subject_id', $request->subject);
                });
            }, 'studentAcademic.standardLink', 'userprofile'])
            ->where('usergroup_id', 6)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereHas('studentAcademic', function ($query) use ($request) {
                $query->whereHas('standardLink', function ($q2) use ($request) {
                    $q2->where('section_id', $request->class);
                });
            })
            ->latest('created_at');

        $exams = Exam::where('school_id', $schoolId)
            ->where('section_id', $request->class)
            ->where('academic_term_id', $request->term)
            ->where('exam_type_id', $request->examType)
            ->where('academic_year_id', $academic_year_id);
        $examsDone = $exams->count();
        $exam = $exams->first();
        $students = $query->get();

        $students = $students->map(function ($student) use ($examsDone, $exam, $gradingSystem) {
            $total = $student->marks->sum('marks');
            $aggregates = $gradingSystem->aggregates($student, $exam);
            $student->total = $total;
            $student->average = $examsDone ? $total / $examsDone : 0;
            $student->avg = $aggregates;

            return $student;
        });
        $students = $students->sortByDesc('total')->values();

        $position = 1;
        $prevTotal = null;
        $students = $students->map(function ($student, $index) use (&$position, &$prevTotal) {
            if ($prevTotal !== null && $student->total < $prevTotal) {
                $position = $index + 1;
            }
            $student->position = $position;
            $prevTotal = $student->total;

            return $student;
        });

        $type = ExamType::find($request->examType);
        $promotion = null;
        if ($type?->name === 'End Of Year') {
            $promotion = $this->studentPromotion->promoteStudents($students, $schoolId, $request->class, $examsDone);
        }

        $page = request()->get('page', 1);
        $perpage = 6;
        $students = new LengthAwarePaginator(
            $students->forPage($page, $perpage),
            $students->count(),
            $perpage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $subjects = Subject::where('school_id', $schoolId)->where('section_id', $request->class)->get();

        $subjectsCovered = Exam::where('school_id', $schoolId)
            ->where('section_id', $request->class)
            ->where('academic_term_id', $request->term)
            ->distinct('subject_id')
            ->count('subject_id');

        $coveredSubjectIds = Exam::where('school_id', $schoolId)
            ->where('section_id', $request->class)
            ->where('academic_term_id', $request->term)
            ->where('exam_type_id', $request->examType)
            ->where('status', 'submitted')
            ->pluck('subject_id')
            ->unique()
            ->filter();

        $missingSubjects = $subjects->filter(
            fn ($subject) => ! $coveredSubjectIds->contains($subject->id)
        )->values();

        $class = Section::find($request->class);
        $exam = Exam::where('school_id', $schoolId)
            ->where('section_id', $class?->id)
            ->where('academic_year_id', $academic_year_id)
            ->where('academic_term_id', $request->term)
            ->whereHas('examType', fn ($q) => $q->where('contributes_to_report_total', 1))
            ->first();

        $termModel = AcademicTerm::find($request->term);
        $termName = $termModel?->name;
        $studentTotal = $students->total();
        $marksSubtitle = trim(implode(' · ', array_filter([
            $class?->name,
            $termName,
            $type?->name,
            $studentTotal ? $studentTotal.' students' : null,
        ])));

        return view('admin.marks.filter', array_merge($base, [
            'subjects' => $subjects,
            'students' => $students,
            'missingSubjects' => $missingSubjects,
            'subjectsCovered' => $subjectsCovered,
            'class' => $class,
            'type' => $type,
            'exam' => $exam,
            'promotion' => $promotion,
            'year' => $academicYear?->name,
            'term' => $request->term,
            'termName' => $termName,
            'filtered' => true,
            'marksSubtitle' => $marksSubtitle !== '' ? $marksSubtitle : 'Marks grid',
        ]));
    }

    public function promoteStudents(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $sectionId = $request->sectionId;
        $this->studentPromotion->finalizePromotions($schoolId, $sectionId);

        return redirect()->route('admin.marks.filter')->with('successmessage', 'Success, Learners have been promoted to the next class!');
    }

    public function demoteStudent()
    {
        //
    }
}
