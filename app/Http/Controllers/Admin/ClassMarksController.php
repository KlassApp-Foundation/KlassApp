<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\User;
use App\Services\Demo\LatestExamMarks;
use App\Support\DemoStudentCode;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only marks of one class for its latest exam: one card per student
 * (by code), a row per subject with the grade under the mark. Reached from
 * the dashboard's "Performance by class" card. Scoped to the signed-in
 * user's school; another school's class is a 404.
 */
class ClassMarksController extends Controller
{
    public function show(?int $section = null)
    {
        $schoolId = (int) Auth::user()->school_id;

        $sections = Section::query()->where('school_id', $schoolId)->orderBy('name')->get(['id', 'name'])
            ->filter(fn (Section $s) => LatestExamMarks::forSection($schoolId, (int) $s->id) !== null)
            ->values();

        if ($section !== null) {
            $current = Section::query()->where('school_id', $schoolId)->whereKey($section)->first();
            abort_if($current === null, 404);
        } else {
            $current = $sections->first();
        }

        $exam = $current ? LatestExamMarks::forSection($schoolId, (int) $current->id) : null;
        $students = collect();
        if ($exam) {
            $bySubject = $exam['marks']->groupBy('student_id');
            $students = User::query()
                ->where('school_id', $schoolId)
                ->whereIn('id', $bySubject->keys())
                ->with('userprofile')
                ->get()
                ->map(function (User $student) use ($bySubject, $exam) {
                    $marks = $bySubject->get($student->id)->keyBy('subject_id');
                    $rows = [];
                    foreach ($exam['subjects'] as $subject) {
                        $mark = $marks->get($subject['id']);
                        $rows[] = [
                            'subject' => $subject['name'],
                            'mark' => $mark ? rtrim(rtrim(number_format((float) $mark->marks, 1, '.', ''), '0'), '.') : '–',
                            'grade' => $mark ? (string) $mark->grade : '',
                        ];
                    }
                    $first = trim((string) ($student->userprofile?->getRawOriginal('firstname') ?? '')) ?: (string) strtok((string) $student->name, ' ');

                    return [
                        'code' => DemoStudentCode::fromKls($student->registration_number) ?? '#' . $student->id,
                        'first_name' => \Illuminate\Support\Str::title(mb_strtolower($first)),
                        'rows' => $rows,
                    ];
                })
                ->sortBy('code', SORT_NATURAL)
                ->values();
        }

        return view('admin.marks.class-marks', [
            'sections' => $sections,
            'current' => $current,
            'exam' => $exam,
            'students' => $students,
        ]);
    }
}
