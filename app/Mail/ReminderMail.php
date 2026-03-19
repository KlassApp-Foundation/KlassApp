<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\MailTemplate;
use App\Models\Reminder;

class ReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $reminder;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Reminder $reminder)
    {
       //dd($reminder);
        $this->reminder = $reminder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        //dd('kjhhsdf');
        $template = MailTemplate::where([['name','event_reminder'],['status','active']])->first();
        //dd($template);
        $subject =  $template->subject;
        //dd($subject);
        $mail_content = $template->mail_content;
       //dd($mail_content);
        $mail_content = str_replace(":school_name",$this->reminder->school->name,$mail_content);
        $mail_content = str_replace(":title",$this->reminder->events->title,$mail_content);
        $mail_content = str_replace(":description",$this->reminder->events->description,$mail_content);
        $mail_content = str_replace(":location",$this->reminder->events->location,$mail_content);
        $mail_content = str_replace(":start_date",$this->reminder->events->start_date,$mail_content);
        $mail_content = str_replace(":end_date",$this->reminder->events->end_date,$mail_content);
             
        return $this->markdown('emails.mailcontent')
                        ->subject($subject)
                        ->with([
                            'content' => $mail_content,
                            ]);
    }
}
