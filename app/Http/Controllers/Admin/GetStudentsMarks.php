<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Results\DivisionRule;
use App\Contracts\Results\GradingScale;
use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\Section;
use App\Models\User;
use App\Services\Results\ResultsPolicy;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Support\Facades\Auth;

class GetStudentsMarks extends Controller
{
    public function GetStudentMarks(User $user, string $class, Exam $exam)
    {
        [$section, $term, $student, $sheet] = $this->studentSheet($user, $class, $exam);

        $subjects = [];
        foreach ($sheet->subjects as $subject) {
            $subjects[$subject['id']] = $subject['name'];
        }
        foreach ($sheet->sittings as $sitting) {
            foreach ($sitting->subjects as $subject) {
                $subjects[$subject['id']] = $subject['name'];
            }
        }

        return view('admin.marks.student', [
            'student' => $student,
            'sittings' => $sheet->sittings,
            'subjects' => $subjects,
            'section' => $section,
            'term' => $term,
        ]);
    }

    public function redirectLegacyReport(User $learner, string $class, Exam $exam)
    {
        $this->studentSheet($learner, $class, $exam);

        return redirect()->route('admin.marks.student.class', [
            'user' => $learner->getKey(),
            'class' => $class,
            'exam' => $exam->getKey(),
        ], 301);
    }

    /**
     * @return array{0: Section, 1: AcademicTerm, 2: \App\Services\Results\ResultStudent, 3: \App\Services\Results\ResultSheet}
     */
    private function studentSheet(User $user, string $class, Exam $exam): array
    {
        $admin = Auth::user();
        $schoolId = (int) $admin->school_id;
        $sectionId = (int) $class;

        $section = Section::query()
            ->where('id', $sectionId)
            ->where('school_id', $schoolId)
            ->first();
        $term = AcademicTerm::query()
            ->where('id', $exam->academic_term_id)
            ->where('school_id', $schoolId)
            ->first();

        if ($section === null
            || $term === null
            || (int) $user->school_id !== $schoolId
            || (int) $exam->school_id !== $schoolId
            || (int) $exam->section_id !== $sectionId
        ) {
            abort(404);
        }

        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: $schoolId,
            sectionId: $sectionId,
            academicTermId: (int) $term->id,
            studentId: (int) $user->id,
        ), new ResultsPolicy(
            app(GradingScale::class),
            app(DivisionRule::class),
            splitMidTermByMonth: true,
        ));

        $student = $sheet->student((int) $user->id);
        if ($student === null) {
            abort(404);
        }

        return [$section, $term, $student, $sheet];
    }
}
