<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\SendMail;
use App\Models\MailTemplate;

class SendMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $sendMail;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(SendMail $sendMail)
    {
        //
        $this->sendMail = $sendMail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $template = MailTemplate::where([['name','send_mail'],['status','active']])->first();
        $subject = $template->subject;
        $mail_content = $template->mail_content;
        
        $subject = str_replace(":subject",$this->sendMail->subject,$subject);
        $mail_content = str_replace(":message",$this->sendMail->message,$mail_content);
        $mail_content = str_replace(":name",$this->sendMail->user->name,$mail_content);
        if($this->sendMail->attachments != '')
        {
            $mail_content = str_replace(":attachments",url($this->sendMail->attachments),$mail_content);
        }
        else
        {
            $mail_content = str_replace(":attachments","",$mail_content);
        }
        
        return $this->markdown('emails.mailcontent')
                    ->subject($subject)
                    ->with([
                        'content' => $mail_content,
                        ]);
    }
}