<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\AndroidConfig;
use NotificationChannels\Fcm\Resources\AndroidFcmOptions;
use NotificationChannels\Fcm\Resources\AndroidNotification;
use NotificationChannels\Fcm\Resources\ApnsConfig;
use NotificationChannels\Fcm\Resources\ApnsFcmOptions;
use App\Models\User;


class SendDeviceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($data,$token)
    {
        $this->queue = 'notification';
               $this->data = $data; 
               $this->token = $token; 
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
   

    public function toFcm($notifiable)
    {

            $type=$this->data['type'];
            $message=$this->data['message'];

        return FcmMessage::create()
             ->setToken($this->token)
            ->setData(["type" =>$type,"message" =>$message])
             ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                 ->setTitle($type)
                 ->setBody($message))                 
               //  ->setImage(url('/notification/mobile_logo.png')))
            ->setAndroid(
                AndroidConfig::create()
                    ->setFcmOptions(AndroidFcmOptions::create()->setAnalyticsLabel('analytics'))
                    ->setNotification(AndroidNotification::create()->setColor('#0A0A0A'))
            )->setApns(
                ApnsConfig::create()
                    ->setFcmOptions(ApnsFcmOptions::create()->setAnalyticsLabel('analytics_ios')));
    }

    // optional method when using kreait/laravel-firebase:^3.0, this method can be omitted, defaults to the default project
    public function fcmProject($notifiable, $message)
    {
        // $message is what is returned by `toFcm`
        return 'app'; // name of the firebase project to use
    }


    
}
