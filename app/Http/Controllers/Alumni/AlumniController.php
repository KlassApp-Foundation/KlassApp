<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Academics\Marks;
use App\Models\Academics\Exam;
use App\Models\Standard;
use App\Helpers\SiteHelper;
use PDF;

class AlumniController extends Controller
{
    /**
     * Alumni Dashboard — past records, directory, profile.
     */
    public function dashboard()
    {
        $user = Auth::user();
        $schoolId = $user->school_id;

        // Past marks for this alumni
        $marks = Marks::with(['exam', 'subject'])
            ->where('student_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // Alumni directory — other graduates from the same school
        $alumni = User::where('school_id', $schoolId)
            ->where('usergroup_id', 9)
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->with('userprofile')
            ->orderBy('name')
            ->take(50)
            ->get();

        // Graduation year derived from last academic year the student had marks
        $lastMarkYear = Marks::where('student_id', $user->id)
            ->join('exams', 'marks.exam_id', '=', 'exams.id')
            ->join('academic_years', 'exams.academic_year_id', '=', 'academic_years.id')
            ->value('academic_years.name');

        $stats = [
            'total_marks' => $marks->count(),
            'subjects'    => $marks->pluck('subject_id')->unique()->count(),
            'alumni_count'=> $alumni->count() + 1, // +1 includes self
            'grad_year'   => $lastMarkYear ?? '—',
        ];

        return view('alumni.dashboard', compact('marks', 'alumni', 'stats', 'user'));
    }

    /**
     * Show detailed marks history for the alumni.
     */
    public function marks()
    {
        $user = Auth::user();
        $marks = Marks::with(['exam', 'subject'])
            ->where('student_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('alumni.marks', compact('marks', 'user'));
    }

    /**
     * Alumni directory — browse other graduates.
     */
    public function directory()
    {
        $user = Auth::user();
        $schoolId = $user->school_id;

        $alumni = User::where('school_id', $schoolId)
            ->where('usergroup_id', 9)
            ->where('status', 'active')
            ->with('userprofile')
            ->orderBy('name')
            ->paginate(30);

        return view('alumni.directory', compact('alumni', 'user'));
    }

    /**
     * Download report card / marks summary as PDF.
     */
    public function downloadReportCard()
    {
        $user = Auth::user();
        $marks = Marks::with(['exam', 'subject'])
            ->where('student_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = PDF::loadView('alumni.pdf-report-card', [
            'user'  => $user,
            'marks' => $marks,
        ]);

        return $pdf->download('report-card-' . $user->name . '.pdf');
    }
}
