<?php

namespace App\Http\Requests;

use App\Models\Academics\SchoolGradingSystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateManySchoolGradingSystemRequest extends FormRequest
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
    public function withValidator($validator)
{
    $validator->after(function ($validator) {

        if (!$this->grades || !is_array($this->grades)) {
            return;
        }

        foreach ($this->grades as $i => $row) {

            $exists = SchoolGradingSystem::where('school_id', $this->school_id)
                ->where('standard_id', $this->standard_id)
                ->where(function ($q) use ($row) {

                    $q->whereBetween('min_score', [$row['min_score'], $row['max_score']])
                      ->orWhereBetween('max_score', [$row['min_score'], $row['max_score']])
                      ->orWhere(function ($q2) use ($row) {
                          $q2->where('min_score', '<=', $row['min_score'])
                             ->where('max_score', '>=', $row['max_score']);
                      });
                })
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    "grades.$i.range",
                    "Grade range overlaps with existing record."
                );
            }
        }
    });
}
}