<?php
/**
 * Trait for processing Dashboard
 */
namespace App\Traits;

use App\Http\Resources\Teacher\Timetable as TimetableResource;
use App\Models\TeacherLeaveApplication;
use Illuminate\Support\Facades\Cache;
use App\Models\TeacherProfile;
use App\Models\BookCategory;
use App\Models\Subscription;
use App\Models\ExamSchedule;
use App\Models\BookLending;
use App\Models\LibraryCard;
use App\Models\Teacherlink;
use App\Models\NoticeBoard;
use App\Models\Userprofile;
use App\Models\ActivityLog;
use App\Helpers\SiteHelper;
use App\Models\Attendance;
use Gegok12\Timetable\Models\Timetable;
use App\Models\Bulletin;
use App\Models\Feedback;
use App\Models\Product;
use App\Models\Events;
use App\Models\Video;
use App\Models\Mark;
use App\Models\User;
use App\Models\Task;
use App\Models\Book;
use Carbon\Carbon;

/**
 *
 * @class trait
 * Trait for Dashboard Processes
 */
trait Dashboard
{
    public function adminDashboard($school_id,$admin_id)
    {
        $seconds = 300;
        $array = [];

        $academic_year = SiteHelper::getAcademicYear($school_id);
    
        $array['studentCount'] = Cache::remember('studentCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)  {
                                  return User::where([['status','!=','exit']])->BySchool($school_id)->ByRole(6)->count();
                              });

        $array['parentCount']    =  Cache::remember('parentCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return User::BySchool($school_id)->ByRole(7)->whereHas('children', function($q) use ($search){
    
                $q->whereHas('userStudent', function($q) 
                {
                    $q->where([['status','!=','exit']]);
                });
            })->count();
                                });

        $array['teacherCount']   = Cache::remember('teacherCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return User::where([['status','!=','exit']])->BySchool($school_id)->ByRole(5)->count();
                                });

        $array['nonteachingCount']   = User::where([['status','!=','exit']])->where('school_id',$school_id)->whereIn('usergroup_id',[8,10,11,12,13])->count();


        $array['maleCount']      = Cache::remember('maleCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return User::where([['status','!=','exit']])->BySchool($school_id)->ByRole(6)->ByGender('male')->count();
                                });
        $array['femaleCount']    = Cache::remember('femaleCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return User::where([['status','!=','exit']])->BySchool($school_id)->ByRole(6)->ByGender('female')->count();
                                });

        $array['eventCount']     = Cache::remember('eventCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return Events::where([['school_id',$school_id],['category','!=','holidays']])->count();
                                });
           $array['videoCount'] =0;
             if (class_exists('App\Models\Video')) {

               $array['videoCount']     = Cache::remember('videoCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return Video::where('school_id',$school_id)->count();
                                });

          }
        $array['bulletinCount']  = Bulletin::where('school_id',$school_id)->count();

        $array['subscription']   = Subscription::where('school_id',$school_id)->first();

        $array['noticeboard']    = NoticeBoard::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->orderBy('created_at','DESC')->take(5)->get();

        //$array['activitylog']    = ActivityLog::where('causer_id',$admin_id)->orderBy('id','DESC')->take(6)->get();

       /* $array['feedbacks']       =  Feedback::with(['parent', 'admin','feedbackMessage'])->where([['school_id',$school_id],['created_at','>=',$academic_year->start_date],['created_at','<=',$academic_year->end_date]])->whereHas('feedbackMessage' , function ($query){
            $query->where('is_seen','0');
        })->orderBy('id','DESC')->take(5)->get();*///imp

        $array['feedbacks']       =  Feedback::with(['parent', 'admin','feedbackMessage'])->where('school_id',$school_id)->whereHas('feedbackMessage' , function ($query){
            $query->where('is_seen','0');
        })->orderBy('id','DESC')->take(5)->get();

        //new
         // $events         =   Events::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['status','active']]);


         $events =  Events::where([
            ['school_id',$school_id],
            ['academic_year_id',$academic_year->id],
            ['status','inactive'],
            ['category','!=','holidays']
        ]);
         if(!config('gexam.enabled', false)) //new
        {
            $events =$events->where('category','!=','exam');
        }

         $array['events']=$events->orderBy('id','DESC')->take(5)->get();

        $array['products'] =[];
        if (class_exists('App\Models\Product')) {
            $array['products']    = Product::where('school_id',$school_id)->where('product_type','sellable')->orderBy('created_at','DESC')->take(5)->get();
        } 

   

        // $array['nonteachingCount']   = User::where('school_id',$school_id)->Where('usergroup_id',13)->count();
                $array['upcomingExam'] =[];
      if (class_exists('Gegok12\Exam\Models\ExamSchedule')) {
        $array['upcomingExam']   = \Gegok12\Exam\Models\ExamSchedule::with('exam')->whereHas('exam',function($query) use($academic_year)
                              { 
                                $query->where('academic_year_id',$academic_year->id);
                              })->where('start_time','>=',date('Y-m-d H:i:s'))->orderBy('start_time','DESC')->take(10)->get()->groupBy('start_time'); 
    }

        $array['standardLinks']  = SiteHelper::getStandardLinkList($school_id);

        $array['teachers']  = SiteHelper::getTeachingStaffList($school_id,$academic_year->id);

        //working
        /*$startDate  = date('Y-m-d',strtotime($academic_year->start_date));  
        $endDate    = date('Y-m-d',strtotime($academic_year->end_date));
            
        $attendances    = Attendance::with('user')->where([
            ['school_id',$school_id],
            ['academic_year_id',$academic_year->id],
            ['status',0],
            ['date','>=',$startDate],
            ['date','<=',$endDate]
        ])->orderBy('date','DESC')->get()->groupBy([function($attendance) {
                    return Carbon::parse($attendance->date)->format('M Y'); 
                },'user_id','session']);
        $i = 0;
            
        foreach ($attendances as $key => $attendance) 
        {
            //$array['attendances']['months'][$i] = $key;
            foreach ($attendance as $user_id => $sessions) 
            {
                $user = User::where('id',$user_id)->first();
                $array['attendances']['students'][$user->name]['FullName'] = $user->FullName;
                $array['attendances']['students'][$user->name]['class'] = $user->studentAcademicLatest->standardLink->StandardSection;
                if($attendance[$user_id] != null)
                {
                    $array['attendances']['students'][$user->name][$key] = (int)count($sessions)*0.5;
                }
                else
                {
                    $array['attendances']['students'][$user->name][$key] = 0;
                }
            }
            $i++;
        }*/ //working

        return $array;
    }

    public function studentDashboard($school_id,$user_id,$standardLink_id,$subject,$exam,$mark,$exam_date)
    {
        $array = [];

        $academic_year      =   SiteHelper::getAcademicYear($school_id);
        $total              =   Attendance::where('user_id',$user_id->id)->count();
        $present            =   Attendance::where([['user_id',$user_id->id],['status',1]])->count();
        $absent             =   Attendance::where([['user_id',$user_id->id],['status',0]])->count();

        $date=date('Y-m-d H:i:s');
        
        if(class_exists('Gegok12\Exam\Models\Mark'))
        {

            $marks              =   \Gegok12\Exam\Models\Mark::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['user_id',$user_id->id]]);

            
            if($mark != '')
            { 
                $marks = $marks->where(function ($query) use($mark)
                { 
                    $query->where('obtained_marks',$mark);

                });
            }
            if($subject != '')
            {
                $marks = $marks->whereHas('subject',function ($query) use($subject)
                { 
                    $query->where('name','LIKE','%'.$subject.'%');
                });
            }
            if($exam != '')
            {
                $marks = $marks->whereHas('exam',function ($query) use($exam)
                {
                    $query->where('name','LIKE','%'.$exam.'%');
                });
            }
        }
   
        if($present != 0)
        {
            $array['presentPercentage'] = $present=='' ? 0:number_format((float)( $present / $total )*100);
        }
        if($absent != 0)
        {
            $array['absentPercentage']  = number_format((float)( $absent / $total )*100);
        }
        $array['presentDay']        = $present/2;
        $array['absentDay']         = $absent/2;
        $array['noticeboard']       = NoticeBoard::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['type','!=','teacher']])->orWhere('standardLink_id',$standardLink_id)->orderBy('created_at','DESC')->take(5)->get();
        $array['upcomingeventCount']  = Events::where([['school_id',$school_id],['standard_id',$standardLink_id],['end_date','>',$date],['category','!=','holidays']])->count();
        $array['upcomingholidayCount']  = Events::where([['school_id',$school_id],['end_date','>=',$date],['category','=','holidays']])->count();

        if(class_exists('Gegok12\Exam\Models\Mark'))
        {
            $array['marks']             = $marks->take(5)->get();
        }
        

        return $array;
    }

    public function teacherDashboard($school_id,$teacher_id)
    {
        $array = [];

        $teacher = User::find($teacher_id);
        $user = User::with('teacherlink')->where('id',$teacher_id)->get();
        $academic_year  = SiteHelper::getAcademicYear($school_id);
        $teacherlinks   = $teacher->teacherlinkCurrentAcademicYear;

        $teachersubjects = [];
        foreach ($teacherlinks as $teacherlink) 
        {
            $teachersubjects[$teacherlink->id]['subject']   = $teacherlink->subject->name;
            $teachersubjects[$teacherlink->id]['class']     = $teacherlink->standardLink->StandardSection;
        }
        $standardLinks = $teacherlinks->pluck('standardLink_id')->toArray();

        $array['activitylog']   = $teacher->activitylog()->orderBy('id','DESC')->take(5)->get();

        $array['subject']       = $teachersubjects;
         $array['timetable'] = [];
         if (class_exists('Gegok12\Timetable\Models\Timetable')) {
        $timetables     = Timetable::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['day',date('l')]])->whereIn('standardLink_id',$standardLinks)->get();
       
        foreach ($timetables as $key => $timetable) 
        {
            foreach ($teachersubjects as $teachersubject) 
            {
                foreach ($timetable->schedule as $key1 => $schedule) 
                {
                    foreach ($schedule as $index => $value) 
                    {
                        if($index == 'subject_id')
                        {
                            if($teachersubject['subject'] == $value)
                            {
                                $array['timetable'][$timetable->standardLink->StandardSection][$key1]['period'] = $schedule['period'];
                                $array['timetable'][$timetable->standardLink->StandardSection][$key1]['subject'] = $value;
                                $array['timetable'][$timetable->standardLink->StandardSection][$key1]['start_time'] = $schedule['start_time'];
                                $array['timetable'][$timetable->standardLink->StandardSection][$key1]['end_time'] = $schedule['end_time'];
                            }
                        }
                    }
                }
            }
        }
    }

        $array['noticeboard']   = NoticeBoard::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['type','!=','class']])->orderBy('created_at','DESC')->take(5)->get();
        $array['upcomingExam']=[];
         if (class_exists('Gegok12\Exam\Models\ExamSchedule')) {
        $array['upcomingExam']  = \Gegok12\Exam\Models\ExamSchedule::with('exam','subject')->whereIn('standard_id',$standardLinks)->whereHas('exam',function($query) use($academic_year)
        { 
            $query->where('academic_year_id',$academic_year->id);
        })->where('start_time','>=',date('Y-m-d H:i:s'))->orderBy('start_time','DESC')->take(10)->get()->groupBy('start_time');
    }

        return $array;
    }

    public function receptionDashboard($school_id,$receptionist_id)
    {
        $seconds = 300;
        $array = [];

        $date=date('Y-m-d H:i:s');

        $academic_year = SiteHelper::getAcademicYear($school_id);
    
        $array['studentCount'] = Cache::remember('studentCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)  {
                                  return User::BySchool($school_id)->ByRole(6)->count();
                              });
   
        $array['teacherCount']   = Cache::remember('teacherCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return User::BySchool($school_id)->ByRole(5)->count();
                                });

        $array['eventCount']     = Cache::remember('eventCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return Events::where([['school_id',$school_id],['category','!=','holidays']])->count();
                                });
 
        $array['noticeboard']    = NoticeBoard::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->orderBy('created_at','DESC')->take(5)->get();
        $array['events']    = Events::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['category','!=','holidays'],['end_date','>',$date]])->orderBy('created_at','DESC')->take(5)->get();
        //$array['activitylog']    = ActivityLog::where('causer_id',$admin_id)->orderBy('id','DESC')->take(6)->get();
  

        return $array;
    }

    public function librarianDashboard($school_id,$librarian_id)
    {
        $seconds = 300;
        $array = [];

        $date=date('Y-m-d H:i:s');

        $academic_year = SiteHelper::getAcademicYear($school_id);
    
        $array['bookCount'] =  Cache::remember('bookCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return Book::where('school_id',$school_id)->count();
                                });

        $array['booklendingCount']    =  Cache::remember('booklendingCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return BookLending::whereHas('book' , function($query) use($school_id){
                                        $query->where('school_id',$school_id);
                                    })->count();
                                });

        $array['cardHolderCount']   = Cache::remember('cardHolderCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return LibraryCard::where('school_id',$school_id)->count();
                                });

        $array['categoryCount']      = Cache::remember('categoryCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return BookCategory::where('school_id',$school_id)->count();
                                   });
  
        $array['noticeboard']    = NoticeBoard::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->orderBy('created_at','DESC')->take(5)->get();

        $array['events']    = Events::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['category','!=','holidays'],['end_date','>',$date]])->orderBy('created_at','DESC')->take(5)->get();

        $array['booklendings']    = BookLending::where('return_date','<',$date)->whereHas('book' , function($query) use($school_id){
                $query->where('school_id',$school_id);
            })->orderBy('created_at','DESC')->take(5)->get();

        return $array;
    }

    public function accountantDashboard($school_id,$accountant_id)
    {
        $seconds = 300;
        $array = [];

        $date=date('Y-m-d H:i:s');

        $academic_year = SiteHelper::getAcademicYear($school_id);
    
        $array['bookCount'] =  Cache::remember('bookCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return Book::where('school_id',$school_id)->count();
                                });

        $array['booklendingCount']    =  Cache::remember('booklendingCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return BookLending::whereHas('book' , function ($query) use($school_id) {
                                    $query->where('school_id',$school_id);
                                })->count();
                              });

        $array['cardHolderCount']   = Cache::remember('cardHolderCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return LibraryCard::where('school_id',$school_id)->count();
                                });

        $array['categoryCount']      = Cache::remember('categoryCount_'.$school_id, env('CACHE_TIME'), function () use ($school_id)                          {
                                  return BookCategory::where('school_id',$school_id)->count();
                                   });
  
        $array['noticeboard']    = NoticeBoard::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->orderBy('created_at','DESC')->take(5)->get();

        $array['events']    = Events::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['category','!=','holidays'],['end_date','>',$date]])->orderBy('created_at','DESC')->take(5)->get();

        return $array;
    }
}