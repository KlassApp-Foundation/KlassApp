<?php
/**
 * Trait for processing AcademicProcess
 */
namespace App\Traits;

use Illuminate\Support\Facades\DB;
use App\Events\Notification\SingleNotificationEvent;
use App\Events\SinglePushEvent;
use App\Traits\EventProcess;
use App\Models\StandardLink;
use App\Models\Teacherlink;
use App\Models\Attendance;
use App\Models\Standard;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TempTimetable;
use App\Models\User;
use Carbon\Carbon;
use Exception;

/**
 *
 * @class trait
 * Trait for AcademicProcess Processes
 */
trait AcademicProcess
{
    use EventProcess;

    public function addStandard($school_id , $data)
    {
        try
        {  

            $map = [
                'nursery' => ['nursery'],
                 'primary' => ['nursery', 'primary'],
                 'O-Level' => ['O-Level'],
                 'A-Level' => ['A-Level'],
            ];
            
            $list = $map[$data->standards] ?? [];
            $standards = [];
            for($i = 0 ; $i < count($list) ; $i++) 
            {
                $standard = new Standard;
                $standard->school_id    =   $school_id;
                $standard->name         =   strtolower($list[$i]);
                $standard->order        =   $i;
                $standard->status       =   1;
                
                $standard->save();
                $standards[] = $standard;
            }
            
            return $standards;
        }
        catch(Exception $e)
        {
            dd($e->getMessage());
        } 
    }

//     public function addStandardUg($school_id, $data)
// {
//     try
//     {  
//         $lists = [
//             'nursery'    => 'Nursery',
//             'primary'    => 'Primary',
//             'a-level'  => 'a-level',
//             'advanced'   => 'Advanced',     // if you want to support it later
//             'international' => 'International',
//         ];

//         $selected = strtolower(trim($data->standards));

//         if (!array_key_exists($selected, $lists)) {
//             throw new \Exception("Invalid standards level selected: " . $selected);
//         }

//         $standardName = $lists[$selected];

//         // Check if it already exists for this school to avoid duplicates
//         $exists = Standard::where('school_id', $school_id)
//                           ->where('name', strtolower($standardName))
//                           ->exists();

//         if ($exists) {
//             throw new \Exception("This standard already exists for the school.");
//         }

//         $standard = new Standard;

//         $standard->school_id = $school_id;
//         $standard->name      = strtolower($standardName);   // nursery, primary, a-level
//         $standard->order     = $this->getNextOrder($school_id);  // we'll add this helper below
//         $standard->status    = 1;

//         $standard->save();

//         return $standard;
//     }
//     catch (Exception $e)
//     {
//         Log::error('addStandard error: ' . $e->getMessage());
//         throw $e;
//     } 
// }


    public function createStandard($school_id , $data)
    {
        try
        {  
            $standard = new Standard;

            $standard->school_id    =   $school_id;
            $standard->name         =   strtolower($data->standard);
            $ref_standard = Standard::where('id',$data->ref_standard_id)->first();
            if($data->position == 'before')
            {
                $value = $ref_standard->order-1;
            }
            elseif($data->position == 'after')
            {
                $value = $ref_standard->order+1;
            }
            $standard->order        =   $value;
            $standard->status       =   1;

           
            
            $standard->save();
            

            return $standard;
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        } 
    }

    public function createSection($school_id , $data)
    {
        try
        { 
            $section = new Section;

            $section->school_id    =   $school_id;
            $section->name         =   ucfirst($data->name);
            $section->status       =   1;

            $section->save();

            return $section;
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        } 
    }

    public function createSubject($school_id , $academic_year_id , $data)
    {
        try
        {            
            $subject = new Subject;

            $subject->school_id         =   $school_id;
            $subject->academic_year_id  =   $academic_year_id;
            $subject->standard_id       =   $data->subject_standard_id;
            $subject->section_id        =   $data->subject_section_id;
            $subject->name              =   ucfirst($data->subject);
            $subject->code              =   $data->code;
            $subject->type              =   $data->type;
            $subject->status            =   1;

            $subject->save();

            // return $subject;
            return redirect('/admin/sections')->with(
    'success',
    trans('messages.add_success_msg', ['module' => 'Section'])
);
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        } 
    }

    public function createStandardLink($school_id , $academic_year_id , $data)
    {
        \DB::beginTransaction();
        try
        {            
            $standardLink = new StandardLink;

            $standardLink->school_id        = $school_id;
            $standardLink->academic_year_id = $academic_year_id;
            $standardLink->class_teacher_id = $data->class_teacher_id;
            $standardLink->standard_id      = $data->standard_id;
            $standardLink->no_of_students   = $data->no_of_students;
            if( ($data->standard_name == '11') || ($data->standard_name == '12') )
            {
                if($data->stream == 'others')
                {
                    $standardLink->stream           = $data->other_stream;
                }
                else
                {
                    $standardLink->stream           = $data->stream;
                }
            }
            $standardLink->section_id       = $data->section_id;
            $standardLink->status           = 1;

            $standardLink->save();

            $class_teacher = User::where('id',$standardLink->class_teacher_id)->first();

            $class_teacher->attachRole('student_leave_checker');

            for($i=0 ; $i<$data->count ; $i++)
            {
                $sub        = 'subject_id'.$i;
                $teacher    = 'teacher_id'.$i;
                $subject_type = 'subject_type'.$i;
              
                $subject = Subject::where('id',$data->$sub)->first();
                $teacherlink = new Teacherlink;
                $teacherlink->school_id         = $school_id;
                $teacherlink->academic_year_id  = $academic_year_id;
                $teacherlink->standardLink_id   = $standardLink->id;
                $teacherlink->subject_id        = $subject->id;
                $teacherlink->teacher_id        = $data->$teacher;
                $teacherlink->subject_type      = $data->$subject_type;
              
                $teacherlink->save();
            }
            \DB::commit();
            return $standardLink;
        }
        catch(Exception $e)
        {
            \DB::rollBack();
            dd($e->getMessage());
        } 
    }

    public function editStandardLink($school_id , $academic_year_id , $standardLink_id , $data)
    {
        \DB::beginTransaction();
        try
        {            
            $standardLink = StandardLink::where('id',$standardLink_id)->first();

            if($standardLink->class_teacher_id != $data->class_teacher_id)
            {
                $old_class_teacher = User::where('id',$standardLink->class_teacher_id)->first();

                $old_class_teacher->detachRole('student_leave_checker');

                $class_teacher = User::where('id',$data->class_teacher_id)->first();

                $class_teacher->attachRole('student_leave_checker');
            }
            else
            {
                $class_teacher = User::where('id',$standardLink->class_teacher_id)->first();
                if(!$class_teacher->hasRole('student_leave_checker'))
                {
                    $class_teacher->attachRole('student_leave_checker');
                }
            }

            $standardLink->class_teacher_id = $data->class_teacher_id;
            $standardLink->no_of_students   = $data->no_of_students;
            if( ($data->standard == '11') || ($data->standard == '12') )
            {
                if($data->stream == 'others')
                {
                    $standardLink->stream           = $data->other_stream;
                }
                else
                {
                    $standardLink->stream           = $data->stream;
                }
            }

            $standardLink->save();

            $teacherlinks = Teacherlink::where('standardLink_id',$standardLink_id)->get();
           
            //dd($temptimetable);
           
            foreach($teacherlinks as $teacher)
            {
                if(class_exists('Gegok12\Timetable\Models\TempTimetable'))//new
                {
                    $temptimetable = TempTimetable::where([['standardLink_id',$standardLink_id],['subject_id',$teacher->subject_id],['teacher_id',$teacher->teacher_id]])->delete();
                }
                
                $teacher->delete();

            }

            for($i=0 ; $i<$data->count ; $i++)
            {
                $sub        = 'subject_id'.$i;
                $teacher    = 'teacher_id'.$i;
                $subject_type = 'subject_type'.$i;
                $no_of_periods = 'no_of_periods'.$i;
                

                $subject = Subject::where('id',$data->$sub)->first();

                $teacherlink = new Teacherlink;

                $teacherlink->school_id         = $school_id;
                $teacherlink->academic_year_id  = $academic_year_id;
                $teacherlink->standardLink_id   = $standardLink->id;
                $teacherlink->subject_id        = $subject->id;
                $teacherlink->teacher_id        = $data->$teacher;
                $teacherlink->subject_type      = $data->$subject_type;
                $teacherlink->no_of_periods     = $data->$no_of_periods;
               

                $teacherlink->save();

               /* $timetabledelete=TempTimetable::where([['standardLink_id',$standardLink->id],['teacher_id',$data->$teacher],['subject_id',$subject->id]])->delete();*/


            }
            \DB::commit();
            return $standardLink;
        }
        catch(Exception $e)
        {
            \DB::rollBack();
            //dd($e->getMessage());
        } 
    }

    public function createAttendance($school_id , $academic_year_id , $admin , $data)
    { 
        \DB::beginTransaction();
        try
        {            
            for($i=0 ; $i < $data->absentCount ; $i++)
            {
                $student    = 'user_id'.$i;
                $reason     = 'reason_id'.$i;
                $remarks    = 'remarks'.$i;

                $attendance = new Attendance;

                $attendance->school_id          = $school_id;
                $attendance->academic_year_id   = $academic_year_id;
                $attendance->standardLink_id    = $data->standardLink_id;
                $attendance->date               = date('Y-m-d',strtotime($data->date));
                $attendance->session            = $data->session;
                $attendance->user_id            = $data->$student;
                $attendance->reason_id          = $data->$reason;
                $attendance->remarks            = $data->$remarks;
                $attendance->status             = 0;
                $attendance->recorded_by        = $admin;

                $attendance->save();
                $student = User::where('id',$data->$student)->first();
                foreach ($student->parents as $parent) 
                {
                    $array=[];

                    $array['school_id']  = $school_id;
                    $array['user_id']    = $parent->userParent->id;
                    $array['message']    = 'Dear Parent, Kindly make a note that your child '.$student->FullName.' is absent for school today('.ucfirst($data->session).').';
                    $array['type']       = 'private message';
                            
                    event(new SinglePushEvent($array));


                    $this->sendToAttendanceReminder($school_id,$attendance->date,$parent->userParent->id,$parent->userParent->mobile_no,$parent->userParent->email,$student->FullName);
                } 

                    $datas = [];
                    //$child = User::where('id',$student->id)->first();
                    $datas['user']       =   $student;
                    $datas['type']       =   'attendance';
                    $datas['details']    =   'Dear Parent, Kindly make a note that your child '.$student->FullName.' is absent for school today('.ucfirst($data->session).').';
                    event(new SingleNotificationEvent($datas));
 
            }

            for($i=0 ; $i < $data->presentCount ; $i++)
            {
                $student    = 'present_id'.$i;
                if($data->$student != 'false')
                {
                    $attendance = new Attendance;

                    $attendance->school_id          = $school_id;
                    $attendance->academic_year_id   = $academic_year_id;
                    $attendance->standardLink_id    = $data->standardLink_id;
                    $attendance->date               = date('Y-m-d',strtotime($data->date));
                    $attendance->session            = $data->session;
                    $attendance->user_id            = $data->$student;
                    $attendance->status             = 1;
                    $attendance->recorded_by        = $admin;

                    $attendance->save();
                }
            }
            \DB::commit();
            return $attendance;
        }
        catch(Exception $e)
        {
            \DB::rollBack();
            //dd($e->getMessage());
        } 
    }

   public function createStaffAttendance($school_id, $academic_year_id, $admin, $data)
{ 
    \DB::beginTransaction();
    try
    {            
        // ==================== ABSENT STAFF ====================
        for($i = 0; $i < ($data->absentCount ?? 0); $i++)
        {
            $userKey    = 'user_id' . $i;
            $reasonKey  = 'reason_id' . $i;
            $remarksKey = 'remarks' . $i;

            $user_id   = $data->$userKey ?? null;
            $reason_id = $data->$reasonKey ?? null;
            $remarks   = $data->$remarksKey ?? null;

            if(empty($user_id)) continue;

            $attendance = new Attendance;

            $attendance->school_id          = $school_id;
            $attendance->academic_year_id   = $academic_year_id;
            $attendance->date               = $this->parseDate($data->date ?? null);
            $attendance->session            = $data->session ?? 'forenoon';
            $attendance->user_id            = $user_id;
            $attendance->reason_id          = $reason_id;
            $attendance->remarks            = $remarks;
            $attendance->status             = 0;
            $attendance->recorded_by        = $admin;

            $attendance->save();

            // Get staff details for notification
            $staff = User::where('id', $user_id)->first();

            if($staff) {
                $array = [
                    'school_id' => $school_id,
                    'user_id'   => $staff->id,
                    'message'   => 'Dear ' . ($staff->FullName ?? 'Staff') . ', you were marked absent today.',
                    'type'      => 'private message'
                ];
                
                event(new SinglePushEvent($array));

                $this->sendToAttendanceReminder(
                    $school_id, 
                    $attendance->date, 
                    $staff->id, 
                    $staff->mobile_no ?? null,
                    $staff->email ?? null,
                    $staff->FullName ?? 'Staff'
                );
            }
        }

        // ==================== PRESENT STAFF ====================
        for($i = 0; $i < ($data->presentCount ?? 0); $i++)
        {
            $presentKey = 'present_id' . $i;
            $present_id = $data->$presentKey ?? null;

            if(empty($present_id) || $present_id === 'false') {
                continue;
            }

            $attendance = new Attendance;

            $attendance->school_id          = $school_id;
            $attendance->academic_year_id   = $academic_year_id;
            $attendance->date               = $this->parseDate($data->date ?? null);
            $attendance->session            = $data->session ?? 'forenoon';
            $attendance->user_id            = $present_id;
            $attendance->status             = 1;
            $attendance->recorded_by        = $admin;

            $attendance->save();
        }

        \DB::commit();
        return true; // or the last attendance if needed
    }
    catch(Exception $e)
    {
        \DB::rollBack();
        \Log::error('Staff Attendance Error: ' . $e->getMessage());
        throw $e; // This will help you see the real error
    } 
}

/**
 * Safe date parser
 */
private function parseDate($date)
{
    if(empty($date)) {
        return now()->format('Y-m-d');
    }

    try {
        return Carbon::parse($date)->format('Y-m-d');
    } catch(Exception $e) {
        return now()->format('Y-m-d');
    }
}
}