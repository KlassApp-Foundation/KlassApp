<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function prepareForValidation():void
    {
       if(auth()->check()){
          $this->merge([
            "school_id" => auth()->user()->school_id
          ]);
       }
    }

    public function rules(): array
    {

        return [
            'school_id'        => 'sometimes|nullable|exists:schools,id',
            'standard_id'      => 'sometimes|nullable|exists:standards,id',
            'section_id'       => 'sometimes|nullable|exists:sections,id',
            'academic_year_id' => 'sometimes|nullable|exists:academic_years,id',
            'term'             => 'sometimes|nullable|in:1,2,3', // or string if named "Term I", etc.
            'subject_id'       => 'sometimes|nullable|exists:subjects,id', // sometimes|nullable if whole-class exam
            'teacher_id'       => 'sometimes|nullable|exists:users,id',
            'exam_type_id'      => 'sometimes|nullable|exists:exam_types,id', 
            'scheduled_at'     => 'sometimes|nullable|date_format:Y-m-d\TH:i',
            'status'             => 'sometimes|nullable|in:done,postponed,undone'
        ];
    }
}
