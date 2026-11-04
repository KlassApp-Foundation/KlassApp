<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Marks and attendance for the AUTHENTICATED STUDENT, own record only.
 *
 * There is deliberately no student id parameter anywhere in this controller. The parent
 * equivalents take one and enforce ownership through StudentParentLink; a student reading
 * their own record needs no such surface, so the id does not exist to be tampered with.
 * Every query is scoped to Auth::id() and the authenticated school, and the shape mirrors
 * the proven logic in ParentPortalService::grades() and ::attendance().
 */
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Parent\ParentPortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecordsController extends Controller
{
    public function __construct(private ParentPortalService $portal) {}

    public function marks(Request $request)
    {
        $student = Auth::user();
        $grades = $this->portal->releasedResults($student, (int) $student->school_id, $request->query('year'));

        return view('student.records.marks', [
            'grades' => $grades,
            'message' => $grades['exam_groups'] === [] ? 'No results published yet.' : null,
        ]);
    }

    public function attendance()
    {
        $student = Auth::user();
        $summary = $this->portal->attendanceSummary($student, (int) $student->school_id);

        return view('student.records.attendance', [
            'present' => $summary['present'],
            'absent' => $summary['absent'],
            'late' => $summary['late'],
            'total' => $summary['total'],
            'since' => $summary['since'],
            'recent' => $summary['recent'],
            'chart' => $summary['chart'],
        ]);
    }

    public function fees()
    {
        $student = Auth::user();

        return view('student.records.fees', [
            'fees' => $this->portal->feeSummary($student, (int) $student->school_id),
        ]);
    }
}
