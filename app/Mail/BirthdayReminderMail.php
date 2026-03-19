<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\MailTemplate;
use App\Models\Reminder;

class BirthdayReminderMail extends Mailable implements ShouldQueue
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
        //
        $this->reminder = $reminder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $template = MailTemplate::where([['name','birthday_reminder'],['status','active']])->first();

        $mail_content = $template->mail_content;

        $subject =  $this->reminder->data['subject'];
        
        $mail_content = str_replace(":message",$this->reminder->data['message'],$mail_content);
          
        return $this->markdown('emails.mailcontent')
                        ->subject($subject)
                        ->with([
                            'content' => $mail_content,
                            ]);
    }
}