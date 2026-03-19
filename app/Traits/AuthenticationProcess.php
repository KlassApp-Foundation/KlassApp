<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\Authentication;
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
            if( $user->mobile_no !=null ) 
            { 
                $otp = rand(100000, 999999);
                $expiry = Carbon::now()->addMinutes(5);
                //$expiry = Carbon::now()->addDay(1); //for test

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
                                    
                $this->getOTP($otp,$user->mobile_no);

                \Session::put('successmessage',trans('messages.otp_success_msg'));
            }  
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            dd($e->getMessage());
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