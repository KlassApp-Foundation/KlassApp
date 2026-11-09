<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Marks, attendance, and fees for the authenticated student, own record only.
 * There is no student id on these routes. Every read goes through ParentPortalService,
 * which applies the same release gate the parent portal uses.
 */
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Parent\ParentPortalService;
use App\Support\DisplayName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecordsController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    public function marks(Request $request)
    {
        return $this->page($request, 'marks', 'student.records.marks');
    }

    public function attendance(Request $request)
    {
        return $this->page($request, 'attendance', 'student.records.attendance');
    }

    public function fees(Request $request)
    {
        return $this->page($request, 'fees', 'student.records.fees');
    }

    public function report()
    {
        $student = Auth::user();
        $result = $this->portal->releasedReportPdf($student, (int) $student->school_id);
        if (! ($result['ok'] ?? false)) {
            return back()->with('failmessage', $result['message'] ?? 'No report card has been released yet.');
        }

        return response($result['pdf'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"',
        ]);
    }

    private function page(Request $request, string $panel, string $view)
    {
        $student = Auth::user();
        $schoolId = (int) $student->school_id;
        $year = $panel === 'marks' ? $request->query('year') : null;
        $bundle = $this->portal->bundle($student, $schoolId, $year);
        [$bundle, $loading] = $this->preview($request, $bundle);
        $name = DisplayName::title($student->displayName ?: $student->name);
        $link = $student->studentAcademicLatest?->standardLink;
        $className = ($link && (int) $link->school_id === $schoolId) ? ($link->StandardSection ?: '') : '';

        return view($view, [
            'panel' => $panel,
            'childName' => $name,
            'className' => $className,
            'studentId' => $student->id,
            'bundle' => $bundle,
            'loading' => $loading,
            'grades' => $bundle['grades'],
            'fees' => $bundle['fees'],
            'attendance' => $bundle['attendance'],
            'message' => ($bundle['grades']['exam_groups'] ?? []) === [] ? 'No results published yet.' : null,
            'links' => [
                'dashboard' => route('student.dashboard'),
                'marks' => route('student.marks'),
                'attendance' => route('student.attendance'),
                'fees' => route('student.fees'),
                'report' => route('student.report'),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function preview(Request $request, array $bundle): array
    {
        $state = (string) $request->query('state', '');
        if (! in_array($state, ['loading', 'empty', 'error'], true) || ! app()->environment(['local', 'testing'])) {
            return [$bundle, false];
        }
        if ($state === 'loading') {
            return [$bundle, true];
        }
        if ($state === 'empty') {
            $bundle['grades']['exam_groups'] = [];
            $bundle['grades']['chart'] = ['labels' => [], 'scores' => []];
            $bundle['headline'] = ['average' => null, 'label' => '', 'delta' => null, 'since' => null, 'has_unreleased' => false];

            return [$bundle, false];
        }
        $bundle['errors']['fees'] = true;

        return [$bundle, false];
    }
}
