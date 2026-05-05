<?php

namespace App\Http\Requests;

use App\Models\Academics\SchoolGradingSystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateSchoolGradingSystem extends FormRequest
{
    /**
     * Authorization
     */
    public function authorize(): bool
    {
        // allow logged-in users (you can tighten later)
        return auth()->check();
    }

    /**
     * Prepare data before validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'school_id' => auth()->user()->school_id,
        ]);
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'school_id' => 'required|exists:schools,id',
            'standard_id' => 'required|exists:standards,id',

            'grade' => 'required|string|max:2',

            'min_score' => 'required|integer|min:0|max:100',
            'max_score' => 'required|integer|min:0|max:100|gte:min_score',

            'remark' => 'nullable|string|max:255',
        ];
    }

    /**
     * Custom validation (NO OVERLAPPING RANGES)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {

            $exists = SchoolGradingSystem::where('school_id', $this->school_id)
                ->where('standard_id', $this->standard_id)
                ->where(function ($q) {
                    $q->whereBetween('min_score', [$this->min_score, $this->max_score])
                      ->orWhereBetween('max_score', [$this->min_score, $this->max_score])
                      ->orWhere(function ($q2) {
                          $q2->where('min_score', '<=', $this->min_score)
                             ->where('max_score', '>=', $this->max_score);
                      });
                })
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'range',
                    'This grading range overlaps with an existing one for this standard.'
                );
            }
        });
    }
}