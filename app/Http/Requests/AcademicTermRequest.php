<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicTermRequest extends FormRequest
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
            "academic_year_id" => AcademicYear::where("school_id", auth()->user()->school_id)->value("id")
          ]);
       }
    }

    public function rules(): array
    {
        return [
            "name" => [
                "required",
                "string",
                "max:255",
                Rule::unique("academic_terms")->where(function ($query){
                    return $query->where("school_id", auth()->user()->school_id);
                })
            ],
            "school_id" => "required|exists:schools,id",
            "academic_year_id" => "required|exists:academic_years,id",
            "starts_on"  =>"nullable|date",
            "ends_on"    =>"nullable|date|after_or_equal:starts_on"
        ];
    }
}
