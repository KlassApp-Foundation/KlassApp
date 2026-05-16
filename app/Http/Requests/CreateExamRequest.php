<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;

class CreateExamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
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

        if($this->subject_id){
            $subject = Subject::find($this->subject_id);
            if($subject){
                $this->merge([
                    "standard_id" => $subject->standard_id,
                    "section_id" => $subject->section_id
                ]);
            }
        }
    }
    public function rules(): array
    {
        return [
            'standard_id'      => 'required|exists:standards,id',
            'school_id'      => 'required|exists:standards,id',
            'section_id'       => 'required|exists:sections,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'academic_term_id' => 'required|exists:academic_terms,id', // or string if named "Term I", etc.
            'subject_id'       => 'required|exists:subjects,id', // nullable if whole-class exam
            'teacher_id'       => 'nullable|exists:users,id',
            'exam_type_id'      => 'required|exists:exam_types,id', 
            'scheduled_at'     => 'nullable|date_format:Y-m-d\TH:i',
            'status'             => 'nullable|boolean'
        ];
    }
}
