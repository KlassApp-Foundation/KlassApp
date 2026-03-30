<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeesCategoryRequest extends FormRequest
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
    public function rules(): array
    {
        $feesCategoryId = $this->route("fees_category"); //id from rout
        return [
            "name" => [
                "sometimes",
                "string",
                Rule::unique("fees_categories")
                ->ignore($feesCategoryId)
                ->where(fn($query) => $query->where("school_id", auth()->user()->school_id))
            ],

             "school_id" => "sometimes|exists:schools,id",
            "standard_id" => "sometimes|exists:standards,id",
            "section_id" => "sometimes|exists:sections,id",
            "academic_term_id" => "sometimes|exists:academic_terms,id",
            "amount"  =>"sometimes|numeric|min:0|regex:/^\d*(\.\d{1,2})?$/",
        ];
    }
}
