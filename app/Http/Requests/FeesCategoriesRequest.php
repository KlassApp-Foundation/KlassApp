<?php

namespace App\Http\Requests;

use App\Models\FeesCategories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeesCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (auth()->check()) {
            $this->merge([
                'school_id' => auth()->user()->school_id,
            ]);
        }
    }

    public function rules(): array
    {
        $schoolId = auth()->user()->school_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fees_categories')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'school_id' => 'required|exists:schools,id',
            'amount' => 'nullable|numeric|min:0|regex:/^\d*(\.\d{1,2})?$/',
            'fee_type' => ['nullable', 'string', Rule::in(array_keys(FeesCategories::types()))],
            'duration' => ['nullable', 'string', Rule::in(array_keys(FeesCategories::durations()))],
            'academic_term_id' => [
                'nullable',
                Rule::exists('academic_terms', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'applies_to' => 'nullable|in:whole_school,classes',
            'section_ids' => 'required_if:applies_to,classes|array',
            'section_ids.*' => [
                'integer',
                Rule::exists('sections', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
        ];
    }
}
