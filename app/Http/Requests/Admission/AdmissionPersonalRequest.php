<?php

namespace App\Http\Requests\Admission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class AdmissionPersonalRequest extends FormRequest
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
        Validator::extend('check_driver_name',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('driver_name')) ;
        });

        $rules= [
            //
            'medical_conditions'    => 'nullable|string|max:2000',
            'special_needs'         => 'nullable|string|max:2000',
        ];

        return $rules;
    }

     public function messages()
    {
        return
        [
            'medical_conditions.max' => 'Please keep this under 2000 characters.',
            'special_needs.max'      => 'Please keep this under 2000 characters.',
        ];
    }
}