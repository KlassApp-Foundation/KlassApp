<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\User;
use App\Services\StudentReportCardService;
use Illuminate\Support\Facades\Auth;

class DownloadStudentReport extends Controller
{
    public function download(StudentReportCardService $reportCards, User $learner, $section, Exam $exam)
    {
        $admin = Auth::user();
        $schoolId = (int) $admin->school_id;

        return $reportCards->download(
            $schoolId,
            $learner,
            (int) $section,
            $exam,
            $admin->school
        );
    }
}
