<?php
namespace App\Traits;

use App\Models\Reminder;
use Exception;
use Log;

/**
 * class MSG91 to send SMS on Mobile Numbers.
 * 
 */
trait MSG91
{
    /* private $API_KEY = '296511APtvTe5ChJGR5d91ace4';
    //private $mobileNumber = "9042781117";
    private $SENDER_ID = "Gegogs";
    private $ROUTE_NO = 4;*/
    private $RESPONSE_TYPE = 'json';

    public function sendSMS($content, $to)
    {
        try
        {
            $isError = 0;
            $errorMessage = true;

            $API_KEY = env('REMINDER_API_KEY');
            $SENDER_ID = env('REMINDER_SENDER_ID');
            $ROUTE_NO = env('REMINDER_ROUTE_NO');

            //Your message to send, Adding URL encoding.
     
            $message = urlencode($content);

            //Preparing post parameters
            $postData = array(
                'authkey'  => $API_KEY,
                'mobiles'  => $to,
                'message'  => $message,
                'sender'   => $SENDER_ID,
                'route'    => $ROUTE_NO,
                'response' => $this->RESPONSE_TYPE
            );

            //$url="https://api.msg91.com/api/v2/sendsms?country=91";
            // $array = json_encode($postData);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.msg91.com/api/sendhttp.php?&mobiles=".$postData['mobiles']."&authkey=".$postData['authkey']."&route=".$postData['route']."&sender=".$postData['sender']."&message=".$postData['message']."&country=91",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "GET",
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            ));
        
            $response = curl_exec($curl); 
            $err = curl_error($curl);

            if ($err) 
            {
                return "cURL Error #:" . $err;
            }
        
            curl_close($curl);
        
            $now=date('Y-m-d');
            $queuelist=Reminder::where('via','=','sms')->where('executed_at','=',$now)->get();
            foreach($queuelist as $queue)
            {                    
               $update['sms_response']=$response;
               Reminder::where('id',$queue->id)->update($update);
            }      
            return $response;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function getOTP($OTP, $mobileNumber)
    {  
        try
        { 
            $isError = 0;
            $errorMessage = true;

            $API_KEY = env('REMINDER_API_KEY');
            $SENDER_ID = env('REMINDER_SENDER_ID');
            $ROUTE_NO = env('REMINDER_ROUTE_NO');

            //Your message to send, Adding URL encoding.
            $message = urlencode("Welcome to GegoK12. Your OTP is : $OTP");
     
            //Preparing post parameters
            $postData = array(
                'authkey'  => $API_KEY,
                'mobiles'  => $mobileNumber,
                'message'  => $message,
                'sender'   => $SENDER_ID,
                'route'    => $ROUTE_NO,
                'response' => $this->RESPONSE_TYPE
            );
     
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.msg91.com/api/sendhttp.php?&mobiles=".$postData['mobiles']."&authkey=".$postData['authkey']."&route=".$postData['route']."&sender=".$postData['sender']."&message=".$postData['message']."&country=91",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "GET",
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            if ($err) 
            {
                return "cURL Error #:" . $err;
            }
            curl_close($curl);
            return $response;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            dd($e->getMessage());
        }
    }

     public function emergencySMS($message_content, $mobileNumber)
    {  
       // dump($mobileNumber);
        try
        { 
            $isError = 0;
            $errorMessage = true;

            $API_KEY = env('REMINDER_API_KEY');
            $SENDER_ID = env('REMINDER_SENDER_ID');
            $ROUTE_NO = env('REMINDER_ROUTE_NO');

            //Your message to send, Adding URL encoding.
            $message = urlencode("GegoK12.  Message : $message_content");
     
            //Preparing post parameters
            $postData = array(
                'authkey'  => $API_KEY,
                'mobiles'  => $mobileNumber,
                'message'  => $message,
                'sender'   => $SENDER_ID,
                'route'    => $ROUTE_NO,
                'response' => $this->RESPONSE_TYPE
            );
     
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.msg91.com/api/sendhttp.php?&mobiles=".$postData['mobiles']."&authkey=".$postData['authkey']."&route=".$postData['route']."&sender=".$postData['sender']."&message=".$postData['message']."&country=91",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "GET",
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            if ($err) 
            {
                return "cURL Error #:" . $err;
            }
            curl_close($curl);
            return $response;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            dd($e->getMessage());
        }
    }
}