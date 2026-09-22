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

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Academics\Exam;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RecordsController extends Controller
{
    public function marks()
    {
        $studentId = (int) Auth::id();
        $schoolId = (int) Auth::user()->school_id;
        $academicYear = SiteHelper::getAcademicYear($schoolId);

        $examQuery = Exam::query()
            ->whereHas('marks', fn ($q) => $q->where('student_id', $studentId))
            ->where('school_id', $schoolId)
            ->with([
                'marks' => fn ($q) => $q->where('student_id', $studentId)->with('subject'),
                'examType',
                'subject',
            ])
            ->latest('id');

        if ($academicYear) {
            $examQuery->where('academic_year_id', $academicYear->id);
        }

        $exams = $examQuery->take(15)->get();

        $groups = [];
        foreach ($exams->groupBy(fn ($e) => $e->examType?->name ?? 'Exam') as $typeName => $typeExams) {
            $subjects = [];
            foreach ($typeExams as $exam) {
                foreach ($exam->marks as $mark) {
                    $subjects[] = [
                        'name' => $mark->subject?->name ?? $exam->subject?->name ?? 'Subject',
                        'score' => (float) ($mark->marks ?? 0),
                        'grade' => $mark->grade ?? '-',
                    ];
                }
            }

            if (! empty($subjects)) {
                $groups[$typeName] = $subjects;
            }
        }

        return view('student.records.marks', ['groups' => $groups]);
    }

    public function attendance()
    {
        $studentId = (int) Auth::id();
        $schoolId = (int) Auth::user()->school_id;
        $since = Carbon::now()->subMonth();

        $records = Attendance::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $studentId)
            ->where('date', '>=', $since)
            ->orderByDesc('date')
            ->get();

        return view('student.records.attendance', [
            'present' => $records->where('status', 1)->count(),
            'absent' => $records->where('status', 0)->count(),
            'late' => $records->where('status', 2)->count(),
            'total' => $records->count(),
            'since' => $since->format('d M Y'),
            'recent' => $records->take(20)->map(fn ($r) => [
                'date' => Carbon::parse($r->date)->format('d M Y'),
                'session' => ucfirst((string) $r->session),
                'status' => (int) $r->status === 1 ? 'Present' : ((int) $r->status === 2 ? 'Late' : 'Absent'),
            ])->values()->all(),
        ]);
    }
}
