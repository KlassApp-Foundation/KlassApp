<?php

namespace App\Http\Requests;

use App\Models\Academics\SchoolGradingSystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateSchoolGradingSystem extends FormRequest
{
    /**
     * Authorization
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Inject school_id from logged-in user
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'school_id' => auth()->user()->school_id,
        ]);
    }

    /**
     * Rules
     */
    public function rules(): array
    {
        // get current model ID from route
        $grade = $this->route('grade');
        // dd($grade);

        return [
            'school_id' => 'required|exists:schools,id',
            'standard_id' => 'required|exists:standards,id',

            'grade' => [
                'required',
                'string',
                'max:2',
                Rule::unique('school_grading_systems')
                    ->where(fn ($q) => $q
                        ->where('school_id', $this->school_id)
                        ->where('standard_id', $this->standard_id)
                    )
                    ->ignore($grade?->id),
            ],

            'min_score' => 'required|integer|min:0|max:100',
            'max_score' => 'required|integer|min:0|max:100|gte:min_score',
            'rank' => 'required|integer|min:1|distinct',

            'remark' => 'nullable|string|max:255',
            
        ];
    }

    /**
     * Prevent overlapping ranges (ignore current record)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {

            $grade = $this->route('grade');

            $exists = SchoolGradingSystem::where('school_id', $this->school_id)
                ->where('standard_id', $this->standard_id)
                ->when($grade, function ($q) use ($grade) {
                    $q->where('id', '!=', $grade->id);
                })
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
                    'This grading range overlaps with another existing one.'
                );
            }
        });
    }
}