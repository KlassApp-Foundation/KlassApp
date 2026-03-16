<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\MailTemplate;

class ContactMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
   protected $contact;
    public function __construct($contact)
    {
         $this->contact = $contact;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
         $template = MailTemplate::where([['name','contact'],['status','active']])->first();
        $subject =  $template->subject;
        $mail_content = $template->mail_content;

        $mail_content = str_replace(":contact_no",$this->contact->contact_no,$mail_content);
        $mail_content = str_replace(":email",$this->contact->email,$mail_content);
        $mail_content = str_replace(":select",$this->contact->select,$mail_content);
        $mail_content = str_replace(":fullname",$this->contact->fullname,$mail_content);
        $mail_content = str_replace(":role",$this->contact->role,$mail_content);
        $mail_content = str_replace(":serve_at",$this->contact->serve_at,$mail_content);
     
        return $this->markdown('emails.mailcontent')
                    ->subject($subject)
                    ->with([
                        'content' => $mail_content,
                        ]);
    }
}