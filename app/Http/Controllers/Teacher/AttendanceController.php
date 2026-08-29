<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Teacher;

use App\Http\Resources\Studentlist as StudentlistResource;
use App\Http\Resources\StandardLink as StandardLinkResource;
use App\Http\Requests\AttendanceAddRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\AcademicProcess;
use App\Models\StudentAcademic;
use Illuminate\Http\Request;
use App\Models\StandardLink;
use App\Models\AbsentReason;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Models\Attendance;
use Illuminate\Support\Facades\Log;
use League\Csv\Writer;
use App\Traits\Common;
use Carbon\Carbon;
use Exception;

class AttendanceController extends Controller
{
    //
    use AcademicProcess;
    use LogActivity;
    use Common;

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
{
    $school_id = Auth::user()->school_id;
    $teacher_id = (int) Auth::id();
    $academic_year = SiteHelper::getAcademicYear($school_id);

    if (!$academic_year) {
        return [
            'standardlist'     => [],
            'studentlist'      => [],
            'absentReasonlist' => [],
        ];
    }

    // Same class_teacher_id scope as Api\Teacher\AttendanceController@index
    $classTeacherLinks = SiteHelper::getClassTeacherStandardLinks((int) $school_id, $teacher_id);
    $linkIds = $classTeacherLinks->pluck('id')->map(fn ($id) => (int) $id)->all();

    $standardLinklist = StandardLinkResource::collection($classTeacherLinks);

    $studentAcademic = StudentAcademic::with('user')
        ->where('school_id', $school_id)
        ->where('academic_year_id', $academic_year->id)
        ->when($linkIds !== [], fn ($q) => $q->whereIn('standardLink_id', $linkIds), fn ($q) => $q->whereRaw('1 = 0'))
        ->whereHas('user', function($q) {
            $q->where('status', 'active')
              ->whereNull('deleted_at');
        })
        ->get();

    $studentlist = [];
    $std_id = null;

    foreach ($studentAcademic as $student) {
        $std_id = $student->standardLink_id;
        if (!isset($studentlist[$std_id])) {
            $studentlist[$std_id] = [];
        }
        $profile = optional(optional($student->user)->userprofile);
        $studentlist[$std_id][] = [
            'user_id'        => $student->user_id,
            'id'             => $student->id,
            'name'           => $profile->firstname && $profile->lastname
                                    ? $profile->firstname . ' ' . $profile->lastname
                                    : ($student->user->name ?? 'No Name'),
            'avatar'         => $profile->AvatarPath ?? null,
            'standardLink_id'=> $student->standardLink_id,
        ];
    }

    $absentReasonlist = AbsentReason::where('status', 1)->get();

    return [
        'standardlist'     => $standardLinklist,
        'studentlist'      => $studentlist,
        'absentReasonlist' => $absentReasonlist,
        'std_id' => $std_id,
        'studentAcademic' => $studentAcademic
    ];
}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $standard = \Request::get('standardLink_id') ? \Request::get('standardLink_id'):'';
        return view('/teacher/attendance/create' ,['standard' => $standard]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AttendanceAddRequest $request)
{ 
    try
    {
        $school_id      = Auth::user()->school_id;
        $academic_year  = SiteHelper::getAcademicYear($school_id);
        $admin          = Auth::id();

        if (!$academic_year) {
            return response()->json(['error' => 'Academic year not set'], 422);
        }

        if (! SiteHelper::isClassTeacherOfStandardLink((int) $school_id, (int) $admin, (int) $request->standardLink_id)) {
            abort(403, 'You are not the class teacher for this class.');
        }

        $attendance = $this->createAttendance($school_id , $academic_year->id , $admin , $request);

        $message = trans('messages.add_success_msg',['module' => 'Attendance']);

        $ip = $this->getRequestIP();
        $this->doActivityLog(
            $attendance,
            Auth::user(),
            ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
            LOGNAME_ADD_ATTENDANCE,
            $message
        );

        return response()->json([
            'success' => $message
        ]);
    }
    catch(Exception $e)
    {
        Log::error($e->getMessage());
        return response()->json([
            'error' => 'Something went wrong',
            'message' => $e->getMessage()
        ], 422);
    }
}

    public function export($standardLink_id)
    {
        try
        {
            //
            $school_id      = Auth::user()->school_id;
            $academic_year = SiteHelper::getAcademicYear($school_id);

            if (! SiteHelper::isClassTeacherOfStandardLink((int) $school_id, (int) Auth::id(), (int) $standardLink_id)) {
                abort(403, 'You are not the class teacher for this class.');
            }

            $standardLink = StandardLink::where('id',$standardLink_id)->first();
            $standard = $standardLink->StandardName;
            $section = $standardLink->section->name;
            $csv_name = 'SP Student Attendance Export_'.$standard.'_'.$section.'_'.date('_d-m-Y_H:i').'.csv';
            $attendances  = Attendance::where([
                ['school_id',$school_id],
                ['academic_year_id',$academic_year->id],
                ['standardLink_id',$standardLink_id],
                ['status',0]
            ])->orderBy('date','DESC')->get()->groupBy([function($attendance) {
                    return Carbon::parse($attendance->date)->format('d-m-Y'); 
                },'session']);
            $csv = Writer::createFromFileObject(new \SplTempFileObject());

            if(count($attendances) > 0)
            {
                $csv->insertOne(['Date','Forenoon_Absent_Count','Afternoon_Absent_Count']);
          
                $i = 0;
                foreach ($attendances as $key => $attendance) 
                {
                    foreach ($attendance as $key1 => $student) 
                    { 
                        if($key1 == 'forenoon')
                        {
                            $forenoon_count[$i] = count($student);
                        }
                        else
                        {
                            $afternoon_count[$i] = count($student);
                        }
                    }
                    $csv->insertOne([ $key, $forenoon_count[$i] , $afternoon_count[$i] ]);
                    $i++;
                }
            }
            else
            {
               $csv->insertOne(['No Records Found']);
               $csv->output($csv_name);
            }
            $csv->output($csv_name);
            $message= trans('messages.export_success_msg',['module' => 'Student Attendance']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                Auth::user(),
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_EXPORT_STUDENT_ATTENDANCE,
                $message
            );
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }
}
