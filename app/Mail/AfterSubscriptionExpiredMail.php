<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Subscription;
use App\Models\MailTemplate;

class AfterSubscriptionExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subscription;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Subscription $subscription)
    {
        //
        $this->subscription = $subscription;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $template = MailTemplate::where([['name','site_expired_mail'],['status','active']])->first();
        $subject =  $template->subject;
        $mail_content = $template->mail_content;
      
        $mail_content = str_replace(":school_name",$this->subscription->school->name,$mail_content);
        $mail_content = str_replace(":name",$this->subscription->user->name,$mail_content);
        $mail_content = str_replace(":end_date",$this->subscription->end_date,$mail_content);
      
        return $this->markdown('emails.mailcontent')
                        ->subject($subject)
                        ->with([
                            'content' => $mail_content,
                            ]);
    }
}