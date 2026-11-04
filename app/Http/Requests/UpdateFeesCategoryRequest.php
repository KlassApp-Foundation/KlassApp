<?php

namespace App\Http\Requests;

use App\Models\FeesCategories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeesCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() !== null;
    }

    public function rules(): array
    {
        $feesCategoryId = $this->route('fee');
        $schoolId = auth()->user()->school_id;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('fees_categories')
                    ->ignore($feesCategoryId)
                    ->where(function ($query) use ($schoolId, $feesCategoryId) {
                        $query->where('school_id', $schoolId);
                        $current = FeesCategories::query()
                            ->where('school_id', $schoolId)
                            ->whereKey($feesCategoryId)
                            ->first();
                        // Older whole-school fees are one row per tier and share a name.
                        // Saving one of those rows must not treat its copies as a clash.
                        if ($current && $current->section_id === null && ! $current->applies_school_wide) {
                            $query->where(function ($inner) use ($current) {
                                $inner->where('applies_school_wide', true)
                                    ->orWhereNotNull('section_id')
                                    ->orWhere('name', '!=', $current->name);
                            });
                        }
                        // A class fee may keep, or take, the same name as a whole-school fee.
                        $savingForClasses = $this->input('applies_to') === 'classes'
                            || ($this->input('applies_to') !== 'whole_school' && $current && ! $current->applies_school_wide && $current->section_id !== null);
                        if ($savingForClasses) {
                            $query->where('applies_school_wide', false);
                        }
                    }),
            ],
            'academic_term_id' => [
                'sometimes',
                'nullable',
                Rule::exists('academic_terms', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'amount' => 'sometimes|nullable|numeric|min:0|regex:/^\d*(\.\d{1,2})?$/',
            'fee_type' => ['sometimes', 'string', Rule::in(array_keys(FeesCategories::types()))],
            'duration' => ['sometimes', 'string', Rule::in(array_keys(FeesCategories::durations()))],
            'applies_to' => 'sometimes|in:whole_school,classes',
            'section_ids' => 'required_if:applies_to,classes|array',
            'section_ids.*' => [
                'integer',
                Rule::exists('sections', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
        ];
    }
}
