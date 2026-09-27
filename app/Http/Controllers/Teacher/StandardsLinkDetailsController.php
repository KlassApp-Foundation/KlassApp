<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Teacher;

use App\Http\Resources\ClassUpcomingExam as ClassUpcomingExamResource;
use App\Http\Resources\Classwall\ClassPost as ClassPostResource;
use App\Http\Resources\Attendance as AttendanceResource;
use App\Http\Resources\Homework as HomeworkResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExamSchedule;
use App\Models\StandardLink;
use App\Helpers\SiteHelper;
use App\Models\Attendance;
use App\Models\Homework;
use App\Models\User;
use App\Models\Post;
use Carbon\Carbon;

class StandardsLinkDetailsController extends Controller
{
    //

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showHomework(Request $request)
    {
        //
        $homework = Homework::where('school_id',Auth::user()->school_id)->where('date','>=',date('Y-m-d'))->orderBy('date','DESC');
        if(count((array)\Request::getQueryString())>0)
        {
            if($request->showPast == 'true')
            { 
                $homework = $homework->orWhere('date','<',date('Y-m-d'));
            }

            if($request->standardLink_id != '')
            { 
                $homework = $homework->where('standardLink_id',$request->standardLink_id);
            }
        }
        $homework=$homework->get();
        $homeworklist = HomeworkResource::collection($homework);
        
        return $homeworklist;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getStudentAttendance($id)
    {
        //
        $standardLink = StandardLink::where('id',$id)->first();

        if(Gate::allows('standardlink',$standardLink))
        {
            $array = [];
            $academic_year  = SiteHelper::getAcademicYear(Auth::user()->school_id);
            $startDate  = date('Y-m-d',strtotime($academic_year->start_date));  
            $endDate    = date('Y-m-d',strtotime($academic_year->end_date));
            
            $attendances    = Attendance::with('user')->where([
                ['school_id',Auth::user()->school_id],
                ['academic_year_id',$academic_year->id],
                ['standardLink_id',$id],
                ['status',0],
                ['date','>=',$startDate],
                ['date','<=',$endDate]
            ])->orderBy('date','DESC')->get()->groupBy([function($attendance) {
                    return Carbon::parse($attendance->date)->format('M Y'); 
                },'user_id','session']);
            $i = 0;
            
            foreach ($attendances as $key => $attendance) 
            {
                $array['months'][$i] = $key;
                foreach ($attendance as $user_id => $sessions) 
                {
                    $user = User::where('id',$user_id)->first();
                    foreach ($sessions as $session => $value) 
                    {
                        $array['students'][$user->name]['FullName'] = $user->FullName;
                        if($attendance[$user_id] != null)
                        {
                            $array['students'][$user->name][$key] = count($value)*0.5;
                        }
                        else
                        {
                            $array['students'][$user->name][$key] = 0;
                        }
                    }
                }
                $i++;
            }
            for ($j = 0; $j < $i; $j++)
            {
                $array['forenoon_present'][$j]    = $forenoonpresent[$j]    ?? 0;
                $array['forenoon_absent'][$j]     = $forenoonabsent[$j]     ?? 0;
                $array['afternoon_present'][$j]   = $afternoonpresent[$j]   ?? 0;
                $array['afternoon_absent'][$j]    = $afternoonabsent[$j]    ?? 0;
            }
            return $array;
        }
        else
        {
            abort(403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getAttendance($id)
    {
        //
        $standardLink = StandardLink::where('id',$id)->first();

        if(Gate::allows('standardlink',$standardLink))
        {
            $array = [];
            $academic_year  = SiteHelper::getAcademicYear(Auth::user()->school_id);
            $array['select_month']  = Carbon::now()->format('m-Y');
            $array['months'] = [];
            $months = [];
            $start = strtotime('last month', strtotime($academic_year->start_date));
            $now = strtotime($academic_year->end_date);
            $i = 0;
            while(($start = strtotime('next month', $start)) <= $now) 
            {
                // Same fix as the admin controller: was written to an uninitialised
                // value and 500'd the class attendance view for every class.
                $array['months'][$i] = ['id' => date('m-Y', $start), 'name' => date('M Y', $start)];
                $i++;
            }
            $startDate  = Carbon::now()->firstOfMonth()->format('Y-m-d');  
            $endDate    = Carbon::now()->lastOfMonth()->format('Y-m-d');
            
            $attendances    = Attendance::where([
                ['school_id',Auth::user()->school_id],
                ['academic_year_id',$academic_year->id],
                ['standardLink_id',$id],
                ['status',0],
                ['date','>=',$startDate],
                ['date','<=',$endDate]
            ])->orderBy('date','DESC')->get();
            $array['attendances'] = AttendanceResource::collection($attendances)->groupBy([function($attendance) {
                    return Carbon::parse($attendance->date)->format('d M Y'); 
                },'session']);

            $attendancechart  = Attendance::where([
                ['school_id',Auth::user()->school_id],
                ['academic_year_id',$academic_year->id],
                ['standardLink_id',$id],
                ['date','>=',$startDate],
                ['date','<=',$endDate]
            ])->orderBy('date','DESC')->get()->groupBy([function($attendancechart) {
                    return Carbon::parse($attendancechart->date)->format('d M Y'); 
                },'session','status']);

            $i = 0;
            foreach ($attendancechart as $date => $attendance) 
            {
                $array['dates'][$i]  =   $date;
                foreach ($attendance as $session => $student) 
                { 
                    // A date can have only one session and only one status, so the
                    // missing groups must default to empty rather than be counted
                    // directly: count($student[1]) threw "count(): Argument #1 must be
                    // of type Countable|array, null given" and 500'd the page for any
                    // class whose data lacked a group.
                    $present = count($student[1] ?? []);
                    $absent  = count($student[0] ?? []);
                    if($session == 'forenoon')
                    {
                        $forenoonpresent[$i]    = $present;
                        $forenoonabsent[$i]     = $absent;
                    }
                    else
                    {
                        $afternoonpresent[$i]   = $present;
                        $afternoonabsent[$i]    = $absent;
                    }
                }
                $forenoonpresent[$i]    = $forenoonpresent[$i]    ?? 0;
                $forenoonabsent[$i]     = $forenoonabsent[$i]     ?? 0;
                $afternoonpresent[$i]   = $afternoonpresent[$i]   ?? 0;
                $afternoonabsent[$i]    = $afternoonabsent[$i]    ?? 0;
                $i++;
            }
            
            return $array;
        }
        else
        {
            abort(403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showAttendance(Request $request,$id)
    {
        //
        $standardLink = StandardLink::where('id',$id)->first();

        if(Gate::allows('standardlink',$standardLink))
        {
            $date = Carbon::createFromFormat('m-Y', $request->select_month);
            $array = [];
            $array['select_month']  = $request->select_month;
            $academic_year  = SiteHelper::getAcademicYear(Auth::user()->school_id);
            $months = [];
            $start = strtotime('last month', strtotime($academic_year->start_date));
            $now = strtotime($academic_year->end_date);
            $i = 0;
            while(($start = strtotime('next month', $start)) <= $now) 
            {
                // Same fix as the admin controller: was written to an uninitialised
                // value and 500'd the class attendance view for every class.
                $array['months'][$i] = ['id' => date('m-Y', $start), 'name' => date('M Y', $start)];
                $i++;
            }
            $startDate      = Carbon::parse($date)->firstOfMonth()->format('Y-m-d');  
            $endDate        = Carbon::parse($date)->lastOfMonth()->format('Y-m-d'); 
            
            $attendances    = Attendance::where([
                ['school_id',Auth::user()->school_id],
                ['academic_year_id',$academic_year->id],
                ['standardLink_id',$id],
                ['status',0],
                ['date','>=',$startDate],
                ['date','<=',$endDate]
            ])->orderBy('date','DESC')->get();
            $array['attendances'] = AttendanceResource::collection($attendances)->groupBy([function($attendance) {
                    return Carbon::parse($attendance->date)->format('d M Y'); 
                },'session']);

            $attendancechart  = Attendance::where([
                ['school_id',Auth::user()->school_id],
                ['academic_year_id',$academic_year->id],
                ['standardLink_id',$id],
                ['date','>=',$startDate],
                ['date','<=',$endDate]
            ])->orderBy('date','DESC')->get()->groupBy([function($attendancechart) {
                    return Carbon::parse($attendancechart->date)->format('d M Y'); 
                },'session','status']);

            $i = 0;
            foreach ($attendancechart as $date => $attendance) 
            {
                $array['dates'][$i]  =   $date;
                foreach ($attendance as $session => $student) 
                { 
                    // A date can have only one session and only one status, so the
                    // missing groups must default to empty rather than be counted
                    // directly: count($student[1]) threw "count(): Argument #1 must be
                    // of type Countable|array, null given" and 500'd the page for any
                    // class whose data lacked a group.
                    $present = count($student[1] ?? []);
                    $absent  = count($student[0] ?? []);
                    if($session == 'forenoon')
                    {
                        $forenoonpresent[$i]    = $present;
                        $forenoonabsent[$i]     = $absent;
                    }
                    else
                    {
                        $afternoonpresent[$i]   = $present;
                        $afternoonabsent[$i]    = $absent;
                    }
                }
                $forenoonpresent[$i]    = $forenoonpresent[$i]    ?? 0;
                $forenoonabsent[$i]     = $forenoonabsent[$i]     ?? 0;
                $afternoonpresent[$i]   = $afternoonpresent[$i]   ?? 0;
                $afternoonabsent[$i]    = $afternoonabsent[$i]    ?? 0;
                $i++;
            }
            
            return $array;
        }
        else
        {
            abort(403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showUpcomingExams($id)
    {
        //
        $standardLink = StandardLink::where('id',$id)->first();

        if(Gate::allows('standardlink',$standardLink))
        {
            $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);
            if(class_exists('Gegok12\Exam\Models\ExamSchedule'))
            {
                $upcomingExams  = \Gegok12\Exam\Models\ExamSchedule::whereHas('exam',function($query) use($academic_year)
                    { 
                        $query->where('academic_year_id',$academic_year->id);
                    })->where('standard_id',$standardLink->id)->where('start_time','>=',date('Y-m-d H:i:s'))->orderBy('start_time','ASC')->get(); 
            }
            else
            {
                $upcomingExams  = ExamSchedule::whereHas('exam',function($query) use($academic_year)
                    { 
                        $query->where('academic_year_id',$academic_year->id);
                    })->where('standard_id',$standardLink->id)->where('start_time','>=',date('Y-m-d H:i:s'))->orderBy('start_time','ASC')->get();
            }
            $upcomingExams = ClassUpcomingExamResource::collection($upcomingExams)->groupBy('exam_id');

            return $upcomingExams;
        }
        else
        {
            abort(403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showClassWall($id)
    {
        //
        $standardLink = StandardLink::where('id',$id)->first();

        if(Gate::allows('standardlink',$standardLink))
        {
            $school_id = Auth::user()->school_id;
            $academic_year = SiteHelper::getAcademicYear($school_id);
            
            $posts = Post::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['is_posted',1],['visible_for',$id]])->orderBy('post_created_at','DESC')->get(); 

            $posts = ClassPostResource::collection($posts);

            return $posts;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showComments($post_id)
    {
        //
        $post = Post::where('id',$post_id)->first();
        
        $array = [];

        $array['comment_list']['comments_count']    = count($post->PostComments);
        $array['comment_list']['comments']          = $post->PostComments;

        return $array;
    }
}