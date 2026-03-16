<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Accountant;

use App\Http\Resources\Notice as NoticeResource;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\NoticeBoard;
use App\Helpers\SiteHelper;

class NoticeBoardController extends Controller
{

    public function showList(Request $request)
    {
        //
        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $notices = NoticeBoard::where([['school_id',Auth::user()->school_id],['academic_year_id',$academic_year->id]])->where('expire_date','>=',date('Y-m-d'))->where('status',1);
        if(count((array)\Request::getQueryString())>0)
        {
            if($request->showExpired == 'true')
            { 
                $notices = $notices->orWhere('status',0)->orWhere('expire_date','<=',date('Y-m-d'));
            }

            if($request->standardLink_id != '')
            { 
                $notices = $notices->where('standardLink_id',$request->standardLink_id);
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

        return view('/accountant/noticeboard/index' ,['query' => $query]);
    }
}