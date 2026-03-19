<?php

namespace App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\MailTemplate;

class AdmissionApprovalMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $template = MailTemplate::where([['name','admission_confirmation'],['status','active']])->first();

        $mail_content = $template->mail_content;
        
        $mail_content = str_replace(":application_no",$this->data['application_no'],$mail_content);
        $mail_content = str_replace(":school_name",$this->data['school_name'],$mail_content);
          
        return $this->markdown('emails.mailcontent')->subject($template->subject)->with(['content' => $mail_content]);
    }
}