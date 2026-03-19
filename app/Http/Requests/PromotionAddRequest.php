<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromotionAddRequest extends FormRequest
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
        return 
        [
            //
            'curr_academic_year_id' =>  'required',
            'curr_standardlink_id'  =>  'required',
            'exam_id'               =>  'required',
        ];
    }

    public function messages()
    {
        return 
        [
            //
            'curr_academic_year_id.required'    =>  'Current Academic Year is required',
            'curr_standardlink_id.required'     =>  'Class is required',
            'exam_id.required'                  =>  'Exam is required',
        ];
    }
}
