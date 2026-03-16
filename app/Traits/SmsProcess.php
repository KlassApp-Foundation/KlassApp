<?php
/**
 * Trait for processing common
 */
namespace App\Traits;

use App\Models\Smstemplate;
use App\Traits\MSG91;
use Exception;
use Log;

/**
 *
 * @class trait
 * Trait for Common Processes
 */
trait SmsProcess
{

    use MSG91;

    public function sendSmsNotification($to,$start_date,$location)
    {
        try
        {
            $template = Smstemplate::where([['name','Event'],['status','1']])->first();
            $content  = $template->content;
      
            $content = str_replace(":date",$start_date,$content);
            $content = str_replace(":location",$location,$content);
            
            $sms = env('SMS_GATEWAY');
            if($sms)
            {
                $this->sendSMS($content, $to);
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function sendUserResetPassword($to,$url)
    {
        try
        {
            $template = Smstemplate::where([['name','reset_password'],['status','1']])->first();
            $content  = $template->content;
        
            $content = str_replace(":url",$url,$content);
            $sms = env('SMS_GATEWAY');
          
            if($sms)
            {
                $this->sendSMS($content, $to);
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function sendAbsentRecord($to,$message,$school_name)
    { 
        try
        {
            $template = Smstemplate::where([['name','absent_message'],['status','1']])->first();
            $content  = $template->content;
        
            $content = str_replace(":message",$message,$content);
            $content = str_replace(":school_name",$school_name,$content);
            $sms = env('SMS_GATEWAY');
          
            if($sms)
            {
                $this->sendSMS($content, $to);
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function sendBirthday($to,$message,$school_name)
    { 
        try
        {
            $template = Smstemplate::where([['name','birthday'],['status','1']])->first();
            $content  = $template->content;
        
            $content = str_replace(":message",$message,$content);
            $content = str_replace(":school_name",$school_name,$content);
            $sms = env('SMS_GATEWAY');
          
            if($sms)
            {
                $this->sendSMS($content, $to);
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function sendAdmissionApproval($data)
    { 
        try
        {
            $template = Smstemplate::where([['name','admission_confirmation'],['status','1']])->first();
            $content  = $template->content;
        
            $content = str_replace(":application_no",$data['application_no'],$content);
            $content = str_replace(":school_name",$data['school_name'],$content);
            $sms = env('SMS_GATEWAY');
          
            if($sms)
            {
                $this->sendSMS($content, $data['mobile_no']);
            }
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }
}