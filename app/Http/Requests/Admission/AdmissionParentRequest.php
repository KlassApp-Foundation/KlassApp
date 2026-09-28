<?php

namespace App\Http\Requests\Admission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class AdmissionParentRequest extends FormRequest
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
        Validator::extend('check_father_name',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('father_name')) ;
        });

      /*  Validator::extend('check_father_qualification',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('father_qualification_id')) ;
        });*/

        Validator::extend('check_father_designation',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('father_designation')) ;
        });

        Validator::extend('check_father_occupation',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('father_occupation')) ;
        });

        Validator::extend('check_father_organisation',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('father_organisation')) ;
        });

        Validator::extend('check_mother_name',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('mother_name')) ;
        });

       /* Validator::extend('check_mother_qualification',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('mother_qualification_id')) ;
        });*/

        Validator::extend('check_mother_designation',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('mother_designation')) ;
        });

        Validator::extend('check_mother_occupation',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('mother_occupation')) ;
        });

        Validator::extend('check_mother_organisation',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('mother_organisation')) ;
        });

        Validator::extend('check_relation',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('relation_with_student_1')) ;
        });

        Validator::extend('check_relation_two',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('relation_with_student_2')) ;
        });

        Validator::extend('check_mother_annual_income',function($attribute,$value,$parameters,$validator)
        {
            if( strlen(request('mother_income')) < 10 )
            {
                return true;
            }
            return false;
        });

        Validator::extend('check_mother_annual_income_value',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[0-9]+$/', request('mother_income'));
        });

        Validator::extend('check_father_annual_income',function($attribute,$value,$parameters,$validator)
        {
            if( strlen(request('father_income')) < 10 )
            {
                return true;
            }
            return false;
        });

        Validator::extend('check_father_annual_income_value',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[0-9]+$/', request('father_income'));
        });

        return [
            //
            // Primary parent or guardian - required.
            'father_name'               => 'required|string|max:120',
            'father_relationship'       => 'required|string|max:60',
            'father_mobile_no'          => 'required|string|min:9|max:20',
            'father_on_whatsapp'        => 'nullable|boolean',
            'father_alt_phone'          => 'nullable|string|max:20',
            'father_email'              => 'nullable|email',
            'father_occupation'         => 'nullable|string|max:120',
            'father_district'           => 'required|string|max:120',

            // Second parent or guardian - all optional.
            'mother_name'               => 'nullable|string|max:120',
            'mother_relationship'       => 'nullable|string|max:60',
            'mother_mobile_no'          => 'nullable|string|max:20',
            'mother_on_whatsapp'        => 'nullable|boolean',
            'mother_alt_phone'          => 'nullable|string|max:20',
            'mother_email'              => 'nullable|email',
            'mother_occupation'         => 'nullable|string|max:120',
            'mother_district'           => 'nullable|string|max:120',

            // Emergency contact, if different - optional.
            'emergency_contact_name_1'  => 'nullable|string|max:120',
            'relation_with_student_1'   => 'nullable|string|max:60',
            'emergency_contact_1'       => 'nullable|string|max:20',
        ];
    }

    public function messages()
    {
        return
        [
            'father_name.required'              => 'Parent or guardian name is required',
            'father_relationship.required'      => 'Relationship to the child is required',
            'father_mobile_no.required'         => 'Phone number is required',
            'father_mobile_no.min'              => 'Enter a valid phone number',
            'father_district.required'          => 'District of residence is required',
            'father_email.email'                => 'Enter a valid email address',
            'mother_email.email'                => 'Enter a valid email address',
        ];
    }
}