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

        return view('admin.marks.filter', $sheet->viewData($schoolId, $request));
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
