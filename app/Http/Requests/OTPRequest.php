<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Authentication;
use Carbon\Carbon;

class OTPRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        Validator::extend('checkotp', function ($attribute, $value, $parameters, $validator) 
        {
            $otp = Authentication::where([
                ['user_id',Auth::id()],
                ['status',0],
                ['type','register'],
            ])->orderBy('id','DESC')->get();

            if($otp[0]['token'] != request('otp'))
            {
                return FALSE;
            }
            return TRUE;            
        });

        Validator::extend('checkotp_ip', function ($attribute, $value, $parameters, $validator) 
        {
            $otp = Authentication::where([
                ['user_id',Auth::id()],
                ['status',0],
                ['type','register'],
            ])->orderBy('id','DESC')->get(); 

            if($otp[0]['ip_address'] != $_SERVER['REMOTE_ADDR'])
            {
                return FALSE;
            }
            return TRUE;            
        });

        Validator::extend('checkotp_expiry', function ($attribute, $value, $parameters, $validator) 
        {
            $otp = Authentication::where([
                ['user_id',Auth::id()],
                ['status',0],
                ['type','register'],
            ])->orderBy('id','DESC')->get();
            
            if($otp[0]['expires_on'] < Carbon::now())
            {
                return FALSE;
            }
            return TRUE;            
        });

        return [
            'otp' => 'required|checkotp|checkotp_ip|checkotp_expiry',
        ];
    }

    public function messages()
    {
        return [
            'otp.checkotp'          => 'Invalid OTP',
            'otp.checkotp_ip'       => 'Invalid IPAddress',
            'otp.checkotp_expiry'   => 'OTP Expired',
        ];
    }
}