<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Teacher;

use App\Http\Resources\Teacher\Notice as NoticeResource;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\StandardLink;
use App\Models\Teacherlink;
use App\Models\NoticeBoard;
use App\Helpers\SiteHelper;

class NoticeBoardController extends Controller
{

    public function list(Request $request)
    {
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);

        $standardLinks = StandardLink::where([
                ['school_id',$school_id],
                ['academic_year_id',$academic_year->id],
                ['class_teacher_id',Auth::id()]
            ])->pluck('id')->toArray();

        $teacherlinks = Teacherlink::where([
            ['school_id',$school_id],
            ['academic_year_id',$academic_year->id],
            ['teacher_id',Auth::id()]
        ])->pluck('standardLink_id')->toArray();

        $standards = array_merge($standardLinks,$teacherlinks);
        array_push($standards, null);

        $showExpired = $request->showExpired == 'true';

        $notices = NoticeBoard::where(function ($query) use ($school_id, $academic_year_id, $standards, $showExpired) {
            $query->where('school_id', $school_id)
                  ->where('academic_year_id', $academic_year_id)
                  ->where(function ($q) use ($standards, $showExpired) {
                      $q->where('expire_date', '>=', date('Y-m-d'))
                        ->where('status', 1)
                        ->orWhereIn('standardLink_id', $standards);
                      if ($showExpired) {
                          $q->orWhere(function ($q2) {
                              $q2->where('status', 0)
                                 ->where('expire_date', '<=', date('Y-m-d'));
                          });
                      }
                  });
        });

        $notices = $notices->get();
        $notices = NoticeResource::collection($notices);

        return $notices;
    }

    public function index()
    {
        $query = \Request::getQueryString();

        return view('/teacher/noticeboard/index' ,['query' => $query]);
    }
}
