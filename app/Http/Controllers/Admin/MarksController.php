<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarksController extends Controller
{


// all marks according to exam and standard
 public function schoolMarksOverview()
{
    $schoolId = Auth::user()->school_id;

   $students = User::with(
    ["marks.subject", "marks.remark", "marks.exam", "marks.student", "marks.teacher", "marks.school" ])
            ->whereHas("marks.exam", function ($query) use($schoolId){
                $query->forSchool($schoolId);
            })
            ->where("usergroup_id", 6)
            ->get();

    //
    // $marks = Marks::with(["subject", "remark", "exam", "student", "teacher", "school" ])
    // ->whereHas("exam", function ($query) use($schoolId) {
    //     $query->forSchool($schoolId);
    // })
    // ->whereHas("student", function($query){
    //     $query->students();
    // })
    // ->get();
$studentCount = $students->pluck('student_id')->unique()->count();
// to flter subjects depending on class
$subjects = Subject::where("school_id", $schoolId)->get();

    return view('admin.marks.school-overview', compact('exams', "students", "studentCount", "subjects"));
}
    // get all student marks per class
    public function classExamOverview(Exam $exam)
{
    // $exam = Exam::first();
    $schoolId = Auth::user()->school_id; // or pass as param if multi-school admin

    // Get all marks for this exam + school
    $marksQuery = Marks::with(['student', 'subject', 'remark', "exam"])
        ->where('exam_id', $exam->id)
        ->where('school_id', $schoolId)
        ->whereHas('student', fn($q) => $q->students());

    // Group by student → compute aggregates
    $studentMarks = $marksQuery->get()
        ->groupBy('student_id') //to add class here later on
        ->map(function ($group) use ($exam) {
            $student = $group->first()->student;

            $totalObtained = $group->sum('marks');
            $subjectCount  = $group->count(); // or fixed if all take same subjects

            // Assume you have max_marks per subject or total_max on Exam model
            $totalPossible = $exam->total_max_marks ?? ($subjectCount * 100); // fallback

            $average = $subjectCount ? round($totalObtained / $subjectCount, 2) : 0;

            $grade = $this->computeGrade($average); // your helper fn
            $comment = $group->first()->remark?->comment ?? 'N/A'; // or aggregate best/worst

            return [
                'student_id'     => $student->id,
                'name'           => $student->name . ' ' . ($student->userprofile->lastname ?? ''),
                'class'          => $student->class_name ?? 'N/A', // assume you have accessor or relation
                'exam'           => $exam->name,
                'obtained'       => $totalObtained,
                'possible'       => $totalPossible,
                'grade'          => $grade,
                'average'        => $average,
                'total'          => $totalObtained, // or format as "$obtained / $possible"
                'comment'        => $comment,
                'raw_marks'      => $group, // for details modal
            ];
        });

    // Sort for position (desc by average or total)
    $sorted = $studentMarks->sortByDesc('average')->values();

    // Assign positions (handle ties)
    $position = 1;
    $prevAvg  = null;
    $ranked = $sorted->map(function ($item, $index) use (&$position, &$prevAvg) {
        if ($prevAvg !== null && $item['average'] < $prevAvg) {
            $position = $index + 1;
        }
        $prevAvg = $item['average'];

        $item['position'] = $position;
        return $item;
    });

    return view('admin.marks.class-overview', [
        'rankedStudents' => $ranked,
        'exam'           => $exam,
        // add filters: class/stream, subject if needed
    ]);
}
// private function to compute grade
private function computeGrade($average)
{
    if ($average >= 80) return 'A';
    if ($average >= 70) return 'B';
    if ($average >= 60) return 'C';
    if ($average >= 50) return 'D';
    return 'E';
}
}
