<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ResetPasswordRequest extends FormRequest
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
      $this->user = User::findBySubmittedPhone((string) $this->input('mobile_no'), 'Uganda', (int) $this->input('usergroup'));

        Validator::extend('check_mobile_no',function($attribute,$value,$parameters,$validator)
        {
            //$user = User::where('mobile_no','=',request('mobile_no'))->exists();
            $user=$this->user;
            if($user)
            {
                return true;
            }
            return false;
        });

        Validator::extend('check_active',function($attribute,$value,$parameters,$validator)
        {
           // $user = User::where('mobile_no','=',request('mobile_no'))->first();
            $user=$this->user;
            if ($user === null || $user->userprofile === null) {
                return false;
            }
            if( ($user->userprofile->status == 'active') && ($user->status == 'active') )
            {
                return true;
            }
            return false;
        });

        return [
            'mobile_no' =>  'bail|required|max:20|international_phone|check_mobile_no|check_active',
            'usergroup' =>  'required',
        ];
    }

    public function messages()
    {
        //$user = User::where('mobile_no','=',request('mobile_no'))->first();
        $user=$this->user;
        $status = 'Mobile Number Is Not Registered';
        if ($user !== null && $user->userprofile !== null && ($user->userprofile->status == 'inactive') && ($user->status == 'inactive') )
        {
            $status = 'User Account Suspended. Please Contact School';
        }
        elseif ($user !== null && $user->userprofile !== null && ($user->userprofile->status == 'exit') && ($user->status == 'exit') ) 
        {
            $status = 'User Account Exited. Please Contact School';
        }

        return[
            'mobile_no.required'        =>  'Mobile Number Is Required',
            'mobile_no.numeric'         =>  'Mobile Number Should Be Numeric',
            'mobile_no.digits:10'       =>  'Mobile Number Should Be 10 Digits',
            'mobile_no.check_mobile_no' =>  'Mobile Number Is Not Registered',
            'mobile_no.check_active'    =>  $status,
        ];
    }
}