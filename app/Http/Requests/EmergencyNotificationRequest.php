<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class EmergencyNotificationRequest extends FormRequest
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
        $rules=[];

        $rules=[
              'message_type'=>'required',
              'message'=>'required|max:150',
        ];
        if(request('message_type')=='specific')
        {
            $rules['standardLink_id']='required';
        }

        return $rules;

    }
}
