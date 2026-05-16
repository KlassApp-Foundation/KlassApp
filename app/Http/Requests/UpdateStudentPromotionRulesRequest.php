<?php

namespace App\Http\Requests;

use App\Models\StandardLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateStudentPromotionRulesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $schoolId = Auth::user()->school_id;
        $sectionId = $this->section_id;
        $standardId = StandardLink::where('section_id', $sectionId)->value('standard_id');
        if (!$standardId) {
        throw ValidationException::withMessages([
            'section_id' => 'Invalid section or missing standard mapping.',
        ]);
    }

        $this->merge([
            'school_id' => $schoolId,
            "standard_id" => $standardId
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_id' => [
                'required',
                'exists:schools,id',
            ],

            'standard_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:standards,id',
            ],

            'section_id' => [
                'nullable',
                'integer',
                'exists:sections,id',
            ],

            'min_average' => [
                'nullable',
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
            'min_aggregate' => [
                'nullable',
                'string',
            ],
            'min_points' => [
                'nullable',
                'string',
            ],
        ];
    }
}