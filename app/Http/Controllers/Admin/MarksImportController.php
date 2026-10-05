<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesMarksImport;
use App\Exports\MarksheetExport;
use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\User;
use App\Services\MarksImport\MarksImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/** Admin side of the marks import: any exam in the admin's school. */
class MarksImportController extends Controller
{
    use HandlesMarksImport;

    protected function marksImportContext(): array
    {
        return [
            'layout' => 'layouts.admin.layout',
            'routePrefix' => 'admin.exams.marks.import',
            'templateRoute' => 'admin.exams.marks.import.template',
            'backRoute' => 'admin.exams',
            'backLabel' => 'Back to exams',
        ];
    }

    /**
     * Admin template download. Teachers use #986's teacher.exam.marks.template; this serves the
     * same headings, roster and file naming from the import service so both import identically.
     */
    public function importTemplate(Request $request, Exam $exam, MarksImportService $service)
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User, 403, 'Not Authorized');

        $rows = $service->templateRows($exam, $actor);
        $format = in_array($request->query('format'), ['csv', 'xlsx'], true) ? $request->query('format') : 'xlsx';

        return Excel::download(
            new MarksheetExport($service->templateHeadings(), $rows, 'Marks Template'),
            sprintf('marks-template-exam-%d.%s', $exam->id, $format),
        );
    }
}
