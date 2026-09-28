<?php

namespace App\Http\Requests\Admission;

use Illuminate\Foundation\Http\FormRequest;

class AdmissionStandardRequest extends FormRequest
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
        $rules = [
            //
            'standard_id'   => 'required',
            'entry_term'    => 'required|in:1,2,3',
            'entry_year'    => 'required|digits:4',
        ];

        $school = \App\Models\School::where('slug', request()->route('slug') ?? request('slug'))->first();
        $offersBoarding = false;
        if ($school) {
            $offersBoarding = \App\Models\SchoolDetail::where('school_id', $school->id)
                ->where('meta_key', 'boarding_available')
                ->value('meta_value') === '1';
        }

        $rules['boarding_type'] = $offersBoarding
            ? 'required|in:day,boarding'
            : 'nullable|in:day,boarding';

        return $rules;
    }

     public function messages()
    {
        return
        [
            'standard_id.required' => 'Class Is Required',
        ];
    }
}