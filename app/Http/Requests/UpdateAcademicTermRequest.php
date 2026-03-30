<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicTermRequest extends FormRequest
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
    }
    public function rules(): array
    {
        return [
            "name" => [
                "sometimes",
                "string",
                "max:255",
                Rule::unique("academic_terms")->where(function ($query){
                    return $query->where("school_id", auth()->user()->school_id);
                })
            ],
            "school_id" => "sometimes|exists:schools,id",
            "academic_year_id" => "sometimes|exists:academic_years,id",
            "starts_on"  =>"sometimes|date",
            "ends_on"    =>"sometimes|date|after_or_equal:starts_on"
        ];
    }
}
