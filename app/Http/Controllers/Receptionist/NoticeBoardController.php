<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Receptionist;

use App\Http\Resources\Notice as NoticeResource;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Models\NoticeBoard;
use App\Helpers\SiteHelper;
use App\Traits\Common;

class NoticeBoardController extends Controller
{
    use Common;

    public function list(Request $request)
    {
        //
        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $notice = NoticeBoard::where([['school_id',Auth::user()->school_id],['academic_year_id',$academic_year->id]])->where('expire_date','>=',date('Y-m-d'))->where('status',1);
        if(count((array)\Request::getQueryString())>0)
        {
            if($request->showExpired == 'true')
            { 
                $notice = $notice->orWhere('status',0)->orWhere('expire_date','<=',date('Y-m-d'));
            }

            if($request->standardLink_id != '')
            { 
                $notice = $notice->where('standardLink_id',$request->standardLink_id);
            }
        }
        $notice=$notice->get();
        $noticelist = NoticeResource::collection($notice);
        
        return $noticelist;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    { 
        $query = \Request::getQueryString();

        return view('/reception/noticeboard/index' ,['query' => $query]);
    }
}