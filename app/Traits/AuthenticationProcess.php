<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Authentication;
use App\Mail\RegistrationOtpMail;
use App\Traits\MSG91;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Log;

trait AuthenticationProcess
{
    use MSG91;

    public function createAuthentication($user,$request,$type)
    {
        try
        {
            $otp = rand(100000, 999999);
            $expiry = Carbon::now()->addMinutes(5);

            $authentication             =   new Authentication;

            $authentication->user_id    =   $user->id;
            $authentication->type       =   $type;
            $authentication->token      =   $otp;
            $authentication->status     =   0;
            $authentication->expires_on =   $expiry;
            if($request != '')
            {
                $authentication->ip_address = $request->ip();
            }
            else
            {
                $authentication->ip_address = \Request::ip();
            }
            $authentication->save();

            if ($type === 'register' && !empty($user->email)) {
                Mail::to($user->email)->send(new RegistrationOtpMail($user, $otp));
            } elseif ($user->mobile_no != null) {
                $this->getOTP($otp, $user->mobile_no);
            }

            \Session::put('successmessage',trans('messages.otp_success_msg'));
            return true;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            \Session::put('failmessage', 'Failed to send verification code. Please try again.');
            return false;
        }
    }

    public  function checkAuthentication($user_id)
    {
        $authentication = Authentication::where([['user_id',$user_id],['type','register']])->orderBy('id','DESC')->get();
        if(count($authentication)>0)
        {
            return $authentication[0]->status;  
        }
        else
        {
            return 0;
        }
    }
}