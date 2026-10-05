<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\HandlesMarksImport;
use App\Http\Controllers\Controller;

/** Teacher side of the marks import: only exams the teacher may enter marks for. */
class MarksImportController extends Controller
{
    use HandlesMarksImport;

    protected function marksImportContext(): array
    {
        return [
            'layout' => 'layouts.teacher.layout',
            'routePrefix' => 'teacher.exam.marks.import',
            'backRoute' => 'teacher.exam.marks',
            'backLabel' => 'Back to my exams',
        ];
    }
}
