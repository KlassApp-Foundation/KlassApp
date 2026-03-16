<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Accountant;

use App\Http\Resources\Holiday as HolidayResource;
use App\Http\Requests\HolidayUpdateRequest;
use App\Http\Requests\HolidayAddRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\Events;
use App\Traits\Common;
use Exception;

class HolidaysController extends Controller
{
    use LogActivity;
    use Common;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $holidays = Events::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['category','holidays']])->orderBy('start_date','ASC')->paginate(10);
        $holidays = HolidayResource::collection($holidays);

        return $holidays;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return view('/accountant/holiday/index');
    }
}