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
        $notice = NoticeBoard::where(function ($query) use ($academic_year, $request) {
            $query->where('school_id', Auth::user()->school_id)
                  ->where('academic_year_id', $academic_year->id)
                  ->where(function ($q) {
                      $q->where('expire_date', '>=', date('Y-m-d'))
                        ->where('status', 1);
                  });
            if ($request->showExpired == 'true') {
                $query->orWhere(function ($q) {
                    $q->where('status', 0)
                      ->where('expire_date', '<=', date('Y-m-d'));
                });
            }
        });

            if($request->standardLink_id != '')
            { 
                $notice = $notice->where('standardLink_id',$request->standardLink_id);
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