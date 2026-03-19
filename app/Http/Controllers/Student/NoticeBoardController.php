<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Student;

use App\Http\Resources\Student\Notice as NoticeResource;
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
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);

        $notices = NoticeBoard::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['type','!=','teacher']])->where([['expire_date','>=',date('Y-m-d')],['status',1]])->where('standardLink_id',null)->orWhere('standardLink_id',Auth::user()->studentAcademicLatest->standardLink_id);

        if(count((array)\Request::getQueryString())>0)
        {
            if($request->showExpired == 'true')
            { 
                $notices = $notices->orWhere([['status',0],['expire_date','<=',date('Y-m-d')]]);
            }
        }

        $notices = $notices->get();

        $notices = NoticeResource::collection($notices);
        
        return $notices;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    { 
        $query = \Request::getQueryString();

        return view('/student/noticeboard/index' ,['query' => $query]);
    }  
}