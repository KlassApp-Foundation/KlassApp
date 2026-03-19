<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SiteHelper;

class HolidayAddRequest extends FormRequest
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

        for($i=0 ; $i<Request('count') ; $i++)
        {  
            Validator::extend('check_date',function($attribute,$value,$parameters,$validator)
            { 
                $school_id = Auth::user()->school_id;
                $academic_year = SiteHelper::getAcademicYear($school_id);

                if( $value >= date('Y-m-d',strtotime($academic_year->start_date)) && ( $value <= date('Y-m-d',strtotime($academic_year->end_date)) ) )
                {
                    return true;
                }
                return false;
            });

            Validator::extend('check_title',function($attribute,$value,$parameters,$validator)
            {
                return preg_match('/^[A-Za-z_~\-!@#\$%\^&*.,:(\)\s]+$/', $value);
            });

            $rules['date'.$i]   =   'required|check_date';
            $rules['title'.$i]  =   'required|check_title';
        }

        return $rules;
    }

    public function messages()
    {
        for($i=0 ; $i < Request('count') ; $i++)
        {
            $messages['date'.$i.'.required']     =   'Date is required';
            $messages['date'.$i.'.check_date']   =   'Enter Valid Date';

            $messages['title'.$i.'.required']    =   'Title is required';
            $messages['title'.$i.'.check_title'] =   'Enter Valid Title';
        }

        return $messages;
    }
}
