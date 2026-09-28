<?php

namespace App\Http\Requests\Admission;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Standard;
use App\Models\School;
use App\Services\OnboardingEngine;

class AdmissionAcademicRequest extends FormRequest
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
            'english'               =>  'nullable|numeric|max:100',
            'maths'                 =>  'nullable|numeric|max:100',
            'science'               =>  'nullable|numeric|max:100',
            'social'                =>  'nullable|numeric|max:100',
            'group_selection'       =>  'nullable',
            'board_of_education'    =>  'required',
            'choice_of_language'    =>  'required',
        ];

        $school = School::where('slug', request()->route('slug') ?? request('slug'))->first();
        $standard = Standard::where([['school_id',$school->id],['id',request('standard_id')]])->first();

        if( OnboardingEngine::isCandidateClass($standard->name ?? '') )
        {
            $rules['group_selection']           = 'required';
            $rules['board_registration_number'] = 'nullable|string|max:50';
        }

        // Ugandan basic set: previous school / last class completed are optional for
        // nursery and P.1, required otherwise. PLE is required for S.1 entry and
        // UCE for S.5 entry.
        $class = strtoupper(trim(preg_replace('/\s+/', ' ', (string) ($standard->name ?? ''))));
        $isNurseryOrP1 = in_array($class, ['BABY CLASS', 'MIDDLE CLASS', 'TOP CLASS', 'NURSERY'], true)
            || (bool) preg_match('/^P\.?\s?1$/', $class);
        $isS1 = (bool) preg_match('/^(S\.?\s?1|SENIOR\s?1|SENIOR ONE)$/', $class);
        $isS5 = (bool) preg_match('/^(S\.?\s?5|SENIOR\s?5|SENIOR FIVE)$/', $class);

        $rules['school_last_studied']    = $isNurseryOrP1 ? 'nullable|string|max:255' : 'required|string|max:255';
        $rules['last_class_completed']   = $isNurseryOrP1 ? 'nullable|string|max:255' : 'required|string|max:255';
        $rules['ple_index_number']       = $isS1 ? 'required|string|max:50' : 'nullable|string|max:50';
        $rules['ple_aggregate']          = $isS1 ? 'required|string|max:10' : 'nullable|string|max:10';
        $rules['uce_index_number']       = $isS5 ? 'required|string|max:50' : 'nullable|string|max:50';
        $rules['uce_results_summary']    = $isS5 ? 'required|string|max:255' : 'nullable|string|max:255';

        return $rules;
    }

     public function messages()
    {
        return
        [
            'english.numeric'                       => 'Enter Valid English Marks',
            'english.max'                           => 'Enter Valid English Marks Cannot Be Greater Than 100',


            'maths.numeric'                         => 'Enter Valid Maths Marks',
            'maths.max'                             => 'Enter Valid Maths Marks Cannot Be Greater Than 100',

            'science.numeric'                       => 'Enter Valid Science Marks',
            'science.max'                           => 'Enter Valid Science Marks Cannot Be Greater Than 100',

            'social.numeric'                        => 'Enter Valid Social Marks',
            'social.max'                            => 'Enter Valid Social Marks Cannot Be Greater Than 100',

            'board_of_education.required'           => 'Board of Study Is Required',

            'choice_of_language.required'           => 'Choice of Language Is Required',

            'group_selection.required'              => 'Group Selection Is Required',

            'board_registration_number.required'    => 'Board Registration Number Is Required For Candidate Classes',
            'board_registration_number.max'          => 'Board Registration Number Must Not Exceed 50 Characters',
        ];
    }
}
