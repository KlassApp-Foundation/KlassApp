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
use App\Events\SinglePushEvent;
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

    public function list(Request $request)
    {
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);

        $date = $request->query('date', date('Y-m-d'));
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $attendances = Attendance::ByRole(5)
            ->where('school_id', $school_id)
            ->where('academic_year_id', $academic_year->id)
            ->whereDate('date', $date)
            ->with(['user.userprofile', 'absentReason'])
            ->orderBy('session', 'ASC')
            ->get();

        return view('admin.staff_attendance.index', [
            'attendances' => $attendances,
            'date' => $date,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $school_id = Auth::user()->school_id;

        $staff = User::ByRole(5)
            ->where([['school_id', $school_id], ['status', 'active']])
            ->get()
            ->sortBy('userprofile.firstname');

        $absentReasonlist = AbsentReason::where('status', 1)->get();

        return view('/admin/staff_attendance/create', [
            'stafflist' => $staff,
            'absentReasonlist' => $absentReasonlist,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StaffAttendanceRequest $request)
    {
        /*
         * Old collection-based attendance storage.
         * Kept here for reference while the new one-by-one flow is implemented.
         *
         * $school_id      = Auth::user()->school_id;
         * $academic_year  = SiteHelper::getAcademicYear($school_id);
         * $admin          = Auth::id();
         * $attendance = $this->createStaffAttendance($school_id , $academic_year->id , $admin , $request);
         */

        try {
            $school_id     = Auth::user()->school_id;
            $academic_year = SiteHelper::getAcademicYear($school_id);
            $admin         = Auth::id();

            $attendance = $this->createSingleStaffAttendance($school_id, $academic_year->id, $admin, $request);

            $message = trans('messages.add_success_msg', ['module' => 'Attendance']);
            $ip = $this->getRequestIP();

            if ($attendance instanceof Attendance) {
                $this->doActivityLog(
                    $attendance,
                    Auth::user(),
                    ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                    LOGNAME_ADD_ATTENDANCE,
                    $message
                );
            } else {
                activity()
                    ->causedBy(Auth::user())
                    ->withProperties(['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']])
                    ->useLog(LOGNAME_ADD_ATTENDANCE)
                    ->log($message);
            }
// ['success' => $message];
            return redirect('/admin/attendance/staff/list')->with('successmessage', $message);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    private function createSingleStaffAttendance($school_id, $academic_year_id, $admin, $data)
    {
        \DB::beginTransaction();
        try {
            $attendance = new Attendance;
            $attendance->school_id        = $school_id;
            $attendance->academic_year_id = $academic_year_id;
            $attendance->date             = !empty($data->date) ? date('Y-m-d', strtotime($data->date)) : now()->format('Y-m-d');
            $attendance->session          = $data->session;
            $attendance->user_id          = $data->user_id;
            $attendance->status           = intval($data->status);
            $attendance->reason_id        = $attendance->status === 0 ? $data->reason_id : null;
            $attendance->remarks          = $data->remarks ?? null;
            $attendance->recorded_by      = $admin;
            $attendance->save();

            if ($attendance->status === 0) {
                $staff = User::find($attendance->user_id);
                if ($staff) {
                    event(new SinglePushEvent([
                        'school_id' => $school_id,
                        'user_id'   => $staff->id,
                        'message'   => 'Dear ' . ($staff->FullName ?? 'Staff') . ', you were marked absent today.',
                        'type'      => 'private message',
                    ]));

                    if (method_exists($this, 'sendToAttendanceReminder')) {
                        $this->sendToAttendanceReminder(
                            $school_id,
                            $attendance->date,
                            $staff->id,
                            $staff->mobile_no ?? null,
                            $staff->email ?? null,
                            $staff->FullName ?? ''
                        );
                    }
                }
            }

            \DB::commit();
            return $attendance;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Staff Attendance Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
            throw $e;
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
