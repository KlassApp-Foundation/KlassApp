<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Api;

use App\Http\Resources\API\Attendance as AttendanceResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($student_id)
    {
        //
        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $student = User::where('id',$student_id)->first();
        $attendances = Attendance::where([['school_id',Auth::user()->school_id],['academic_year_id',$academic_year->id],['user_id',$student_id]])->get();
        $attendance = AttendanceResource::collection($attendances->where('status',0));

        $total              =   $attendances->count();
        $present            =   $attendances->where('status',1)->count();
        $array = $present=='' ? 0:number_format((float)( $present / $total )*100);
        
        return response()->json([
            'success'               =>  true,
            'message'               =>  'Attendance',
            'attendancePercentage'  =>  $array.'%',
            'data'                  =>  $attendance
        ],200);
    }
}
