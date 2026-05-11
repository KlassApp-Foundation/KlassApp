<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;

class MarksReportService
{

 public function getMarks($request, $schoolId)
    {
        return User::with(['marks' => function ($q) use ($request) {
            $q->with('exam', 'subject')
              ->whereHas("exam", function ($e) {
                  $e->where("status", "submitted");
              });

            $q->when($request->filled('term'), fn($q2) =>
                $q2->whereHas('exam', fn($e) =>
                    $e->where('academic_term_id', $request->term)));

            $q->when($request->filled('year'), fn($q2) =>
                $q2->whereHas('exam', fn($e) =>
                    $e->where('academic_year_id', $request->year)));

            $q->when($request->filled('subject'), fn($q2) =>
                $q2->where('subject_id', $request->subject));

            $q->when($request->filled("class"), fn($q2) =>
                $q2->whereHas("exam", fn($e) =>
                    $e->where("section_id", $request->class)));

        }, "studentAcademic.standardLink"])
        ->where('usergroup_id', 6)
        ->where('school_id', $schoolId)
        ->whereHas("studentAcademic", function ($query) use ($request) {
            $query->whereHas("standardLink", function ($q2) use ($request) {
                $q2->where("section_id", $request->class);
            });
        })
        ->get();
    }

    // marks builder
    public function buildMarksheet($students, $examsDone)
{
    $students = $students->map(function ($student) use ($examsDone) {

        $total = $student->marks->sum('marks');

        $student->total = $total;
        $student->average = $examsDone ? $total / $examsDone : 0;

        return $student;
    });

    $students = $students->sortByDesc("total")->values();

    $position = 1;
    $prevTotal = null;

    return $students->map(function ($student, $index) use (&$position, &$prevTotal) {

        if ($prevTotal !== null && $student->total < $prevTotal) {
            $position = $index + 1;
        }

        $student->position = $position;
        $prevTotal = $student->total;

        return $student;
    });
}
}