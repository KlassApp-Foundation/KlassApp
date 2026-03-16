<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\MailTemplate;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
class RoomInvitationMail extends Mailable implements ShouldQueue
{
   use Queueable, SerializesModels;

   /**
    * The contact instance.
    *
    * @var Contact
    */
   protected $content,$info,$user;

   /**
    * Create a new content instance.
    *
    * @return void
    */
   public function __construct($user, $info)
   {
       $this->user = $user;
       $this->info = $info;
   }
   /**
    * Build the message.
    *
    * @return $this
    */
   public function build()
   {
       $email = $this->user->email;
       $groupID = $this->user->usergroup_id;
       //append conference url
       $url = url('teacher/video-conference/'.$this->info->slug);
       if(Auth::user()->usergroup_id=='5'){
        $url = url('student/video-conference/'.$this->info->slug);
       }

       $mailtemplate = MailTemplate::where([['name','room_invitation'],['status','active']])->first();
                  $message = $mailtemplate->mail_content;
                  $subject = $mailtemplate->subject;
                  $message = str_replace(':name',$this->user->name, $message);
                  $message = str_replace(':title',$this->info->name, $message);
                  $message = str_replace(':description',$this->info->description, $message);
                  $message = str_replace(':message','<a href="'.$url.'">Join Here</a>', $message);
       return $this->markdown('emails.mailcontent')
                   ->subject($subject)
                   ->with([
                       'content' => $message,
                       ])
	                  ->withSwiftMessage(function ($message) use($email) {
	                             $message->getHeaders()
	                  ->addTextHeader('user_email', $email);
	   });
   }
}