<?php
namespace App\Traits;
use FCM;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use Exception;
use Log;
trait SendPushNotification

{
   /**
    * Store a newly created for user details in storage.
    * @param array $data
    * @return array
    */

   public function sendNotification($array,$usertoken)
   {
    try
    {
   
         config(['fcm.http.server_key' => env('FCM_SERVER_KEY')]);
         config(['fcm.http.sender_id' => env('FCM_SENDER_ID')]);  
        //  dump(config('fcm.http.server_key'));
    //  dump(config('fcm.http.sender_id'));
      $optionBuilder       = new OptionsBuilder();
      $optionBuilder       ->setTimeToLive(60 * 20);
      $notificationBuilder = new PayloadNotificationBuilder($array['type']);
      $notificationBuilder ->setBody($array['message'])
           ->setTitle($array['type'])
           ->setSound('default');
      $dataBuilder          = new PayloadDataBuilder();
       $dataBuilder->addData(['message' => $array['message'],'type'=>$array['type']]);
      $option               = $optionBuilder->build();
      $notification         = $notificationBuilder->build();
      $data                 = $dataBuilder->build();
      $token                = $usertoken;
      $downstreamResponse   = FCM::sendTo($token, $option, $notification, $data);
      $downstreamResponse   -> numberSuccess();
      $downstreamResponse   ->numberFailure();
    //  dump($downstreamResponse);
      if($downstreamResponse->numberSuccess())
      {
        return $downstreamResponse;
      }
      else 
      {
        return $downstreamResponse;
      }
    }
    catch(Exception $e)
    {
       Log::info($e->getMessage());
    }
  }

     public function sendTeacherNotification($array,$usertoken)
   {
    try
    {
      config(['fcm.http.server_key' => env('FCM_TEACHER_SERVER_KEY')]);
      config(['fcm.http.sender_id' => env('FCM_TEACHER_SENDER_ID')]);
      $optionBuilder       = new OptionsBuilder();
      $optionBuilder       ->setTimeToLive(60 * 20);
      $notificationBuilder = new PayloadNotificationBuilder($array['type']);
      $notificationBuilder ->setBody($array['message'])
           ->setTitle($array['type'])
           ->setSound('default');
      $dataBuilder          = new PayloadDataBuilder();
       $dataBuilder->addData(['message' => $array['message'],'type'=>$array['type']]);
      $option               = $optionBuilder->build();
      $notification         = $notificationBuilder->build();
      $data                 = $dataBuilder->build();
      $token                = $usertoken;
      $downstreamResponse   = FCM::sendTo($token, $option, $notification, $data);
      $downstreamResponse   -> numberSuccess();
      $downstreamResponse   ->numberFailure();
      if($downstreamResponse->numberSuccess())
      {
        return $downstreamResponse;
      }
      else 
      {
        return $downstreamResponse;
      }
    }
    catch(Exception $e)
    {
       Log::info($e->getMessage());
    }
  }
}