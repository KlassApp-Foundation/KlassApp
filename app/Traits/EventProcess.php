<?php

namespace App\Traits;

use App\Events\AdminBirthdayReminderEvent;
use App\Events\PrayerReminderEvent;
use App\Events\UserReminderEvent;
use App\Events\ReminderEvent;
use App\Models\Events;
use App\Models\User;
use App\Models\RoomLink;
use Exception;
use Log;

trait EventProcess
{
  
    public function sendToReminderEvent($events,$executed_at,$check)
    {
        try
        { 
            $options = ['notification','sms','mail'];

            foreach($options as $option)
            {
                $school_id    =  $events->school_id;
                $from         =  env('MAIL_FROM_ADDRESS');
                $subject      =  "Event Remainder Mail";
                $message      =  "Event Notification Mail";
                $entity_id    =  $events->id;
                $entity_name  =  "App\\Models\\Events";
                $via          =  $option;
                $data         =  array('date'=>date('Y-m-d',strtotime($events->start_date)),'location'=>$events->location);

                if($check=='next')
                {
                    if($events->repeats == '1')
                    {
                        $freq = $events->freq;
                        if($events->freq_term == 'week')
                        {
                            $executed_at  =  date('Y-m-d', strtotime('+'.$freq. 'week', strtotime($executed_at)));          
                        }
                        elseif($events->freq_term == 'month')
                        {
                            $executed_at  =  date('Y-m-d', strtotime('+'.$freq. 'month', strtotime($executed_at)));
                        }
                        elseif($events->freq_term == 'year')
                        {
                            $executed_at  =  date('Y-m-d', strtotime('+'.$freq. 'year', strtotime($executed_at)));
                        }
                        elseif($events->freq_term == 'day')
                        {
                            $executed_at  =  date('Y-m-d', strtotime('+'.$freq. 'days', strtotime($executed_at)));  
                        }
                    }
                    else
                    {
                        $executed_at = $executed_at;
                    }
                }
                if($executed_at < $events->end_date )
                {
                    event(new ReminderEvent($school_id,$from,$subject,$message,$entity_id,$entity_name,$via,$data,$executed_at));      
                } 
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        } 
    }
  
    public function sendToBirthdayReminder($school_id,$entity_id,$date_of_birth,$data,$mobile_no,$mail,$type)
    {
        try
        {
            $options = ['notification','sms','mail'];
            foreach($options as $option)
            {
                $from         =  env('MAIL_FROM_ADDRESS');
          
                if($type == 'birthday')
                { 
                    $subject      =  "Birthday Wishes";
                    $message      =  "Birthday Notification Mail";
                }
                elseif($type == 'work_anniversary')
                {
                    $subject      =  "Work Anniversary Wishes";
                    $message      =  "Work Anniversary Notification Mail";
                }
                $executed_at  =  date('Y-m-d', strtotime($date_of_birth));
                $entity_name  =  "App\\Models\\User";
                $via          =  $option;
          
                /*$array = array('school_id' => $school_id , 'from' => $from , 'mobile_no' => $mobile_no , 'mail' => $mail , 'subject' =>$subject , 'message' =>$message , 'entity_id' =>$entity_id , 'entity_name' =>$entity_name , 'via' =>$via , 'data' =>$data , 'executed_at' => $executed_at );*/
                event(new UserReminderEvent($school_id,$from,$mobile_no,$mail,$subject,$message,$entity_id,$entity_name,$via,$data,$executed_at)); 
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }  
    }

    public function adminBirthdayReminder($school_id,$user_name,$user_email,$entity_id,$date_of_birth,$mobile_no,$mail,$birth_date)
    {
        try
        {
            $options = ['notification','sms','mail'];
            foreach($options as $option)
            {
                $from         =  env('MAIL_FROM_ADDRESS');
                $subject      =  "Birthday Remainder Mail";
                $message      =  "Birthday Notification Mail for <br>
                Name : ".$user_name."<br>
                Email : ".$user_email."<br>
                Birth date : ".$date_of_birth;
                $executed_at  =  date('Y-m-d', strtotime($birth_date));
                $entity_name  =  "App\\Models\\User";
                $via          =  $option;
                $data         =  array('subject'=> $subject, 'message'=>$message, 'type'=>'birthday');
          
                event(new UserReminderEvent($school_id,$from,$mobile_no,$mail,$subject,$message,$entity_id,$entity_name,$via,$data,$executed_at));
            }  
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        } 
    }

    public function sendToAttendanceReminder($school_id,$date,$entity_id,$mobile_no,$email,$student_name)
    {
        try
        {
            $options = ['notification','sms','mail'];
            foreach($options as $option)
            {
                $from         =  env('MAIL_FROM_ADDRESS');
                $entity_name  =  "App\\Models\\User";
                $via          =   $option;
                $subject      =  "Absent Message";
                $message      =  'Dear Parent, Kindly make a note that your child '.$student_name.' is absent for school today.';
                $executed_at  =   $date;
                //$mail         =   NULL;
                $data         =   'absent_message';
                event(new UserReminderEvent($school_id,$from,$mobile_no,$email,$subject,$message,$entity_id,$entity_name,$via,$data,$executed_at));
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }  
    }

  /*  public function sendToAssignmentReminder($school_id,$date,$subject_name,$title,$entity_id,$mail,$mobile_no)
    {
        try
        {
            $from         =  env('MAIL_FROM_ADDRESS');
            $entity_name  =  "App\\Models\\User";
            $via          =  'notification';
            $subject      =  "Assignment Reminder";
            $message      =  'This reminder is about the assignment to be submitted on '.$date.' of subject : '.$subject_name.' , title : '.$title.'.';
            $executed_at  =   date('Y-m-d',strtotime('-2 days',strtotime($date)));
            $data         =   'assignment_reminder';
            event(new UserReminderEvent($school_id,$from,$mobile_no,$mail,$subject,$message,$entity_id,$entity_name,$via,$data,$executed_at));
        }
        catch(Exception $e)
        { 
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }  
    }*/

    public function sendToTaskReminder($school_id,$date,$title,$entity_id,$mail,$mobile_no)
    {
        try
        {
            $options = ['notification','sms','mail'];
            foreach($options as $option)
            {
                $from         = env('MAIL_FROM_ADDRESS');
                $entity_name  = "App\\Models\\Task";
                $via          = $option;
                $subject      = "Task Reminder";
                $message      = 'This reminder is about the task "'.$title.'" assigned to you.';
                $data         = array('subject' => $subject, 'message' => $message, 'type' => 'task');
                event(new UserReminderEvent($school_id,$from,$mobile_no,$mail,$subject,$message,$entity_id,$entity_name,$via,$data,$date));
            } 
        }
        catch(Exception $e)
        { 
            Log::info($e->getMessage());
            dd($e->getMessage());
        }  
    }


    public function sendToAssignmentReminder($school_id,$date,$subject_name,$title,$entity_id,$mail,$mobile_no)
    {
        try
        {
            $from         =  env('MAIL_FROM_ADDRESS');
            $entity_name  =  "App\\Models\\User";
            $via          =  'notification';
            $subject      =  "Assignment Reminder";
            $message      =  'This reminder is about the assignment to be submitted on '.$date.' of subject : '.$subject_name.' , title : '.$title.'.';
            $executed_at  =   date('Y-m-d',strtotime('-2 days',strtotime($date)));
            $data         =   'assignment_reminder';
            event(new UserReminderEvent($school_id,$from,$mobile_no,$mail,$subject,$message,$entity_id,$entity_name,$via,$data,$executed_at));
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }  
    }

    public function sendToSnoozeTask($school_id,$date,$title,$entity_id,$mail,$mobile_no)
    {
        try
        {
            $from         = env('MAIL_FROM_ADDRESS');
            $entity_name  = "App\\Models\\Task";
            $via          = 'notification';
            $subject      = "Task Reminder";
            $message      = trans('notification.task_snooze_msg');
            $data         = array('subject' => $subject, 'message' => $message, 'type' => 'task');
            event(new UserReminderEvent($school_id,$from,$mobile_no,$mail,$subject,$message,$entity_id,$entity_name,$via,$data,$date));
        }
        catch(Exception $e)
        { 
            Log::info($e->getMessage());
            dd($e->getMessage());
        }  
    }

    public function sendToSnoozeTaskWeb($school_id,$date,$title,$entity_id,$mail,$mobile_no)
    {
        try
        {
            $from         = env('MAIL_FROM_ADDRESS');
            $entity_name  = "App\\Models\\Task";
            $via          = 'web_notification';
            $subject      = "Task Reminder";
            $message      = trans('notification.task_snooze_msg');
            $data         = array('subject' => $subject, 'message' => $message, 'type' => 'task');
            event(new UserReminderEvent($school_id,$from,$mobile_no,$mail,$subject,$message,$entity_id,$entity_name,$via,$data,$date)); 
        }
        catch(Exception $e)
        { 
            Log::info($e->getMessage());
            dd($e->getMessage());
        }  
    }

    public function userNotifyGroup($school_id,$entity_id,$mobile_no,$email,$date)
    {
        try
        {
            $from         =  env('MAIL_FROM_ADDRESS');
            $subject      =  "Chat Room Notification Mail";
            $message      =  "You have been added to this chat room.Check with your app to know more<br>";
            $entity_name  =  "App\\Models\\RoomLink";
            $via          =  'sms';
            $data         =  array('message'=>$message);
            $executed_at  =  date('Y-m-d', strtotime($date));
       
            event(new UserReminderEvent($school_id,$from,$mobile_no,$email,$subject,$message,$entity_id,$entity_name,$via,$data,$executed_at));
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        } 
    }
} 