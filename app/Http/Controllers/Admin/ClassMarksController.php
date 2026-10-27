<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\User;
use App\Services\Demo\LatestExamMarks;
use App\Services\Results\DisplayedGrade;
use App\Services\Results\ResultCell;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
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
        $standardId = $current
            ? (int) (StandardLink::query()->where('section_id', $current->id)->orderBy('id')->value('standard_id') ?? 0)
            : 0;
        $students = collect();
        if ($exam) {
            $anchor = $exam['exams']->first();
            $sheet = ($anchor && $anchor->academic_term_id && $anchor->exam_type_id)
                ? app(ResultsService::class)->sheet(new ResultsQuery(
                    schoolId: $schoolId,
                    sectionId: (int) $current->id,
                    academicTermId: (int) $anchor->academic_term_id,
                    examTypeId: (int) $anchor->exam_type_id,
                ))
                : null;
            $grades = app(DisplayedGrade::class);
            $bySubject = $exam['marks']->groupBy('student_id');
            $students = User::query()
                ->where('school_id', $schoolId)
                ->whereIn('id', $bySubject->keys())
                ->with('userprofile')
                ->get()
                ->map(function (User $student) use ($bySubject, $exam, $sheet, $grades, $schoolId, $standardId) {
                    $marks = $bySubject->get($student->id)->keyBy('subject_id');
                    $result = $sheet?->student((int) $student->id);
                    $rows = [];
                    foreach ($exam['subjects'] as $subject) {
                        $mark = $marks->get($subject['id']);
                        $cell = $result?->cell((int) $subject['id']);
                        $fromSheet = $cell !== null && $cell->status === ResultCell::MARKED && $cell->mark !== null && $result !== null;
                        $rows[] = [
                            'subject' => $subject['name'],
                            'mark' => $fromSheet
                                ? rtrim(rtrim(number_format($cell->mark, 1, '.', ''), '0'), '.')
                                : ($mark ? rtrim(rtrim(number_format((float) $mark->marks, 1, '.', ''), '0'), '.') : '–'),
                            'grade' => $fromSheet
                                ? $grades->subject($schoolId, $result->standardId, $cell->mark, $mark?->grade)
                                : ($mark
                                    ? ($mark->marks !== null
                                        ? $grades->subject($schoolId, $standardId, (float) $mark->marks, $mark->grade)
                                        : (string) $mark->grade)
                                    : ''),
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
