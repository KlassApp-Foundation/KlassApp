<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUgSubjectRequest extends FormRequest
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
            // $section = Section::find($this->section_id);
            $this->merge([
                "school_id" => auth()->user()->school_id,
                "academic_year_id" => AcademicYear::where("school_id", auth()->user()->school_id)
                                     ->pluck("id")->first(),
                // "standard_id" =>  $section->standard_id                 
            ]);
        }
    }
    public function rules(): array
    {
        return [
            'name'  => [
                "required",
                "string",
                "max:255",
                "min:2",
                Rule::unique("subjects")->where( function ($query){
                    return $query->where("school_id", auth()->user()->school_id)
                                 ->where("section_id", $this->section_id);
                })
            ],
            'school_id'        => 'required|exists:schools,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'standard_id'      => 'required|exists:standards,id',
            'section_id'       => 'required|exists:sections,id',
            // 'name'             => 'required|string|max:255',
            'code'             => 'nullable|string|max:255', // nullable if whole-class exam
            'type'             => 'nullable|in:core,elective',
            'status'           => 'nullable|boolean',
        ];
    }
}
