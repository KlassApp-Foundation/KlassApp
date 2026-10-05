<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMarksImport;
use App\Http\Controllers\Controller;

/** Admin side of the marks import: any exam in the admin's school. */
class MarksImportController extends Controller
{
    use HandlesMarksImport;

    protected function marksImportContext(): array
    {
        return [
            'layout' => 'layouts.admin.layout',
            'routePrefix' => 'admin.exams.marks.import',
            'backRoute' => 'admin.exams',
            'backLabel' => 'Back to exams',
        ];
    }
}
