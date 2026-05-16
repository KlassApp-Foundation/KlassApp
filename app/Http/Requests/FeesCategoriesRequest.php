<?php

namespace App\Http\Requests;

use App\Models\Section;
use App\Models\Standard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeesCategoriesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() !== null;
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
            // "standard_id" => $section->standard_id,
          ]);
       }
    }
    public function rules(): array
    {
        return [
            "name" => [
                "required",
                "string",
                Rule::unique("fees_categories")->where(function ($query){
                    return $query->where("school_id", auth()->user()->school_id);
                })
            ],
            "school_id" => "required|exists:schools,id",
            "standard_id" => "required|exists:standards,id",
            "section_id" => "nullable|exists:sections,id",
            "academic_term_id" => "nullable|exists:academic_terms,id",
            "amount"  =>"nullable|numeric|min:0|regex:/^\d*(\.\d{1,2})?$/",
        ];
    }
}
