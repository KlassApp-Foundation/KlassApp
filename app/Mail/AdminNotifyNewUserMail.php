<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\MailTemplate;
use App\Models\User;

class AdminNotifyNewUserMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        //
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    { 
        $template       =   MailTemplate::where([['name','new_user_register'],['status','active']])->first();
        $subject        =   $template->subject;
        $mail_content   =   $template->mail_content;
        $mail_content   =   str_replace(":mail",$this->user->email,$mail_content);
     
            return $this->markdown('emails.mailcontent')
                        ->subject($subject)
                        ->with([
                            'content' => $mail_content,
                            ]);
    }
}