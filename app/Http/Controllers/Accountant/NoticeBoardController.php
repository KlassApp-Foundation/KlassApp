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
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);

        $notices = NoticeBoard::where(function ($query) use ($school_id, $academic_year, $request) {
            $query->where('school_id', $school_id)
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

        if ($request->standardLink_id != '') {
            $notices = $notices->where('standardLink_id', $request->standardLink_id);
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