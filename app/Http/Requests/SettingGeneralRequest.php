<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingGeneralRequest extends FormRequest
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
        return [

             'sitetitle'=>'required|string|max:255',
             'school_name'=>'required|string|max:255',
             //'sitelogo'=>'required|mimes:jpeg,png|max:2048',
            // 'favicon'=>'required|mimes:jpeg,png|max:2048',


        ];
    }

     public function messages()
     {
        return[

           'sitetitle.required'=> 'Site Title Required',
           'school_name.required'=> 'School Name Required',
           //'sitelogo.required'=>__('sites.sitelogo'),
           //'sitelogo.mimes'=>__('sites.sitelogo_mimes'),
           //'favicon.required'=>__('sites.favicon'),
           //'favicon.mimes'=>__('sites.favicon_mimes'),


        ];
     }
}
