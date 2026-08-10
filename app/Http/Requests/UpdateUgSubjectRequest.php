<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUgSubjectRequest extends FormRequest
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
                "school_id" => auth()->user()->school_id,
                "academic_year_id" => AcademicYear::where("school_id", auth()->user()->school_id)
                                                    ->pluck("id")->first()
            ]);
        }
    }
    public function rules(): array
    {
        $subjectId = $this->route("subject");
        return [
            'name'  => [
                "sometimes",
                "string",
                "max:255",
                "min:2",
                Rule::unique("subjects")
                      ->ignore($subjectId)
                      ->where( function ($query){
                    return $query->where("school_id", auth()->user()->school_id)
                                 ->where("standard_id", $this->standard_id);
                })
            ],
            'school_id'        => 'sometimes|nullable|exists:schools,id',
            'academic_year_id' => 'sometimes|nullable|exists:academic_years,id',
            'standard_id'      => 'sometimes|nullable|exists:standards,id',
            'section_id'       => 'sometimes|nullable|exists:sections,id',
            // 'name'             => 'required|string|max:255',
            'code'             => 'nullable|string|max:255', // nullable if whole-class exam
            'type'             => 'nullable|in:Sciences,Humanities,Languages,Creative,Other',
            'status'           => 'nullable|boolean',
        ];
    }
}
