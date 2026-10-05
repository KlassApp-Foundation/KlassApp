<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\HandlesMarksImport;
use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;

/** Teacher side of the marks import: only exams the teacher may enter marks for. */
class MarksImportController extends Controller
{
    use HandlesMarksImport;

    protected function marksImportContext(): array
    {
        return [
            'layout' => 'layouts.teacher.layout',
            'routePrefix' => 'teacher.exam.marks.import',
            // The template download already on main (#986), not a second copy.
            'templateRoute' => 'teacher.exam.marks.template',
            'backRoute' => 'teacher.exam.marks',
            'backLabel' => 'Back to my exams',
        ];
    }

    protected function marksViewUrl(Exam $exam): string
    {
        return route('teacher.exam.marks.view', $exam);
    }
}
