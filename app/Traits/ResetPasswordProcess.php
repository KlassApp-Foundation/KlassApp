<?php

namespace App\Traits;

use Illuminate\Support\Facades\Mail;
use App\Mail\SendPassword;
use App\Mail\ResetPassword;
use App\Traits\SmsProcess;
use Carbon\Carbon;
use Exception;
use Hash;
use Log;
use DB;

trait ResetPasswordProcess
{
    use SmsProcess;

    public function resetPasswordToUser($model)
    {
        $token = str_random(10);
        $password = \DB::table(config('auth.passwords.users.table'))->insert([
            'email' => $model->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()
        ]);

        if($password)
        {
            if(env('SMS_STATUS') == 'on')
            {
                $url = url('/password/reset/'.$token);
                $this->sendUserResetPassword($model->mobile_no,$url);
            }
            if( (env('MAIL_STATUS') == 'on') && ($model->email != '') )
            {
                $message = (new ResetPassword($model,$token))->onQueue('email');
                Mail::to($model->email)->queue($message);

                \Session::put('successmessage','Check your email to reset the password');
            }
            else
            {
                \Session::put('failmessage','You cannot send message'); 
            }
        } 
        else 
        {
            \Session::put('successmessage','Password Reset failed for this User'); 
        }        
    }

    public function resetPasswordSms($user)
    {
        try
        {
            $token = str_random(10);
            $password = \DB::table(config('auth.passwords.users.table'))->insert([
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]);

            if( ($password) && (env('SMS_STATUS') == 'on') )
            {
                $url = url('/password/reset/'.$token);
                $this->sendUserResetPassword($user->mobile_no,$url);
            } 
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }   
    }
}