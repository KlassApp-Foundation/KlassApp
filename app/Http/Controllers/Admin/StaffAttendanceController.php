<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Resources\AttendanceUser as AttendanceUserResource;
use App\Http\Resources\Teacherlist as TeacherlistResource;
use App\Http\Resources\StaffAttendanceResource;
use App\Http\Requests\StaffAttendanceRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\AcademicProcess;
use App\Models\StudentAcademic;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AbsentReason;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Models\Attendance;
use League\Csv\Writer;
use App\Traits\Common;
use Carbon\Carbon;
use Exception;
use Log;

class StaffAttendanceController extends Controller
{
    use AcademicProcess;
    use LogActivity;
    use Common;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function list()
    {
        $array = [];
        $school_id = Auth::user()->school_id;

        $academic_year = SiteHelper::getAcademicYear($school_id);



        $staff    = User::ByRole(5)->where([['school_id',Auth::user()->school_id],['status','active']])->get()->sortBy('userprofile.firstname');
        $stafflist = TeacherlistResource::collection($staff);

        $absentReasonlist   = AbsentReason::where('status',1)->get();

        $array['stafflist']       = $stafflist;
        $array['absentReasonlist']  = $absentReasonlist;

        return $array;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
         return view('/admin/staff_attendance/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StaffAttendanceRequest $request)
    {
        //
       // dd($request->all());
        try
        {
         $school_id      = Auth::user()->school_id;
            $academic_year  = SiteHelper::getAcademicYear($school_id);
            $admin          = Auth::id();

            $attendance = $this->createStaffAttendance($school_id , $academic_year->id , $admin , $request);

            $message = trans('messages.add_success_msg',['module' => 'Attendance']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $attendance,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_ADD_ATTENDANCE,
                $message
            );

            $res['success'] = $message;

            return $res;
            }
        catch(Exception $e)
        {
            //Log::info($e->getMessage());
            dd($e->getMessage());
        }
    }

 
     public function staff()
    {
        //
        $school_id      = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $absentees = Attendance::ByRole(5)->where([
                        ['school_id',$school_id],
                        ['academic_year_id',$academic_year->id],
                        ['date',date('Y-m-d')],
                        ['status',0]
                    ])->count();

        return [ 'studentAbsentees' => $absentees];
    }

    public function stafflist()
    {
        //
        $school_id      = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $absentees = Attendance::ByRole(5)->where([
                        ['school_id',$school_id],
                        ['academic_year_id',$academic_year->id],
                        ['date',date('Y-m-d')],
                        ['status',0]
                    ])->get();
        $attendance = StaffAttendanceResource::collection($absentees);

        return $attendance;
    }

    public function getStudentAttendance($name)
    {
        //
        $staff = User::where('name',$name)->first();

            $array = [];
            $academic_year  = SiteHelper::getAcademicYear(Auth::user()->school_id);
            $startDate  = date('Y-m-d',strtotime($academic_year->start_date));  
            $endDate    = date('Y-m-d',strtotime($academic_year->end_date));
            
            $attendances    = Attendance::with('user')->where([
                ['school_id',Auth::user()->school_id],
                ['academic_year_id',$academic_year->id],
                ['user_id',$staff->id],
                ['status',0],
                ['date','>=',$startDate],
                ['date','<=',$endDate]
            ])->orderBy('date','DESC')->get()->groupBy([function($attendance) {
                    return Carbon::parse($attendance->date)->format('M Y'); 
                },'user_id']);
            $i = 0;
            
            foreach ($attendances as $key => $attendance) 
            {
                //$array['months'][$i] = $key;
                foreach ($attendance as $user_id => $value) 
                {
                    $user = User::where('id',$user_id)->first();
                   // $array['staff'][$user->name]['FullName'] = $user->FullName;
                    if($attendance[$user_id] != null)
                    {
                        $array['staff'][$key] = count($value)*0.5;
                    }
                    else
                    {
                        $array['staff'][$key] = 0;
                    }
                }
                $i++;
            }
            return $array;
        
    }

    public function showAttendance($name)
    {
        //
        $staff = User::where('name', $name)->first();
      
        $attendances = AttendanceUserResource::collection($staff->AttendanceUserAbsent);
         
        return $attendances;
    }
}
