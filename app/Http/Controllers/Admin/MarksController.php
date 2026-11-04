<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Results\CombinedSheetPage;
use App\Services\StudentPromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarksController extends Controller
{
    protected $studentPromotion;

    public function __construct(StudentPromotionService $studentPromotion)
    {
        $this->studentPromotion = $studentPromotion;
    }

    public function classExamOverview(Request $request, CombinedSheetPage $sheet)
    {
        $schoolId = (int) Auth::user()->school_id;
        $data = $sheet->viewData($schoolId, $request);
        // Drives the empty state: without exams the grid has nothing to show.
        $data['hasAnyExams'] = \App\Models\Academics\Exam::where('school_id', $schoolId)->exists();

        return view('admin.marks.filter', $data);
    }

    public function promoteStudents(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $sectionId = $request->sectionId;
        $this->studentPromotion->finalizePromotions($schoolId, $sectionId);

        return redirect()->route('admin.marks.filter')->with('successmessage', 'Success, Learners have been promoted to the next class!');
    }

    public function demoteStudent()
    {
        //
    }
}
