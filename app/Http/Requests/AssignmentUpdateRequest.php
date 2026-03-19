<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class AssignmentUpdateRequest extends FormRequest
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
        Validator::extend('check_title', function ($attribute, $value,$parameters,$validator) 
        {
            //return preg_match('/^[\pL\s]+$/u', request('title')); 
            return preg_match('/\pL\pM*|./u', request('title')); 
        });

        Validator::extend('check_assigned_date', function ($attribute, $value, $parameters, $validator) 
        {
            if( date('Y-m-d',strtotime(request('assigned_date'))) >date('Y-m-d',strtotime('-1 days',strtotime(date('Y-m-d')))))
            {
                return true;
            }
            return false;
        });

        Validator::extend('check_marks', function ($attribute, $value, $parameters, $validator) 
        {
            if( request('marks') < 100)
            {
                return true;
            }
            return false;
        });

        Validator::extend('check_valid_marks', function ($attribute, $value, $parameters, $validator) 
        {
            return preg_match('/^[0-9]+$/', request('marks')) ;
        });

        $rules= [
            //
            'title'             =>  'required|max:50|check_title',
            'description'       =>  'required|max:255',
            'marks'             =>  'nullable|numeric|check_marks|check_valid_marks',    
            'assigned_date'     =>  'required|check_assigned_date|before_or_equal:submission_date',
            'submission_date'   =>  'required|after_or_equal:assigned_date',
        ];

        if(\Request('attachment')!= '')
        { 
            //$rules['attachment']='nullable|mimes:pdf|max:8092';
            $rules['attachment']='nullable|mimes:jpeg,png,jpg,pdf';
        }

        return $rules;
    }

    public function messages()
    {
        return
        [   
            'title.required'                    =>  'Title is required',
            'title.max'                         =>  'Title cannot be more than 50 characters',
            'title.check_title'                 =>  'Enter Valid Title',

            'description.required'              =>  'Description is required',
            'description.max'                   =>  'Description cannot be more than 255 characters',

            'attachment.mimes'                  =>  'Choose a Valid file', 
            'attachment.max'                    =>  'Maximum file size to upload is 8MB',

            'marks.numeric'                     =>  'Enter Valid Mark',
            'marks.check_marks'                 =>  'Mark cannot be more than 100',
            'marks.check_valid_marks'           =>  'Enter Valid Mark',

            'assigned_date.required'            =>  'Assigned Date Required',
            'assigned_date.check_assigned_date' =>  'Choose Valid Assigned Date',

            'submission_date.required'          =>  'Submission Date Required',
        ];
    }
}