<?php
/**
 * Trait for processing ReminderProcess
 */
namespace App\Traits;

use App\Models\Reminder;
use Exception;
use Log;

/**
 *
 * @class trait
 * Trait for ReminderProcess Processes
 */
trait ReminderProcess
{
  
    public function createReminder($school_id,$from,$to,$subject,$message,$entity_id,$entity_name,$via,$data,$executed_at)
    {
        try
        {
            $reminder              = new Reminder;

            $reminder->school_id   = $school_id;
            $reminder->from        = $from;
            $reminder->to          = $to;
            $reminder->subject     = $subject;
            $reminder->message     = $message;
            $reminder->entity_id   = $entity_id;
            $reminder->entity_name = $entity_name;
            $reminder->via         = $via;
            //$reminder->queue_status= $queue_status;
            $reminder->executed_at = $executed_at;
            $reminder->data        = $data;
            //$reminder->template_id = $template_id;

            $reminder->save();
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }    
	}
}