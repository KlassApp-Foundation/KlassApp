<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\StandardLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StandardDetailUpdateRequest extends FormRequest
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
        Validator::extend('check_unique_class_teacher',function($attribute,$value,$parameters,$validator)
        {
            $standardLink = StandardLink::where('school_id', Auth::user()->school_id)
                ->where('id', '!=', request('id'))
                ->where('class_teacher_id', request('class_teacher_id'))
                ->exists();
            if($standardLink)
            {
                return false;
            }
            return true;
        });

        Validator::extend('check_other_stream',function($attribute,$value,$parameters,$validator)
        {
            return preg_match('/^[A-Za-z\s]+$/', request('other_stream')) ;
        });

        $teacherRule = Rule::exists('users', 'id')->where(function ($query): void {
            $query->where('school_id', Auth::user()->school_id)
                ->where('usergroup_id', 5);
        });

        $rules = [];
        
        $rules = 
        [
            //
            //'no_of_students'    =>  'required|numeric',
            'class_teacher_id'  =>  ['nullable', $teacherRule, 'check_unique_class_teacher'],
            'section_class_teacher_id' => ['nullable', $teacherRule],
        ];

        if( (request('standard') == '11') || (request('standard') == '12') )
        {
            $rules['stream'] = 'required';
            if(request('stream') == 'others')
            {
                $rules['other_stream'] = 'required|check_other_stream|max:25';
            }
        }

        for($i=0;$i<Request('count');$i++)
        {
            $rules['subject_id'.$i] = 'required';
            $rules['teacher_id'.$i] = 'required';
           
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [];

        $messages = 
        [
            'no_of_students.required'                       =>  'No Of Students is required',
            'no_of_students.numeric'                        =>  'No Of Students Should be Number',
            'class_teacher_id.required'                     =>  'Class Teacher is required',
            'class_teacher_id.check_unique_class_teacher'   =>  'Class Teacher already selected',
            'stream.required'                               =>  'Select Stream',
            'other_stream.required'                         =>  'Enter Stream Name',
            'other_stream.check_other_stream'               =>  'Enter Valid Stream Name',
            'other_stream.max'                              =>  'Stream Name cannot be more than 25 characters',
        ];

        for($i=0 ; $i < Request('count') ; $i++)
        {
            $messages['subject_id'.$i.'.required']  = "Subject is required";
            $messages['teacher_id'.$i.'.required']  = "Teacher is required";
         
        }

        return $messages;
    }
}
