<?php

namespace App\Http\Requests\Teacher;

use App\Models\Subject;
use App\Services\ExamAuthorization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || (int) $user->usergroup_id !== 5) {
            return false;
        }

        $schoolId = (int) $user->school_id;
        $yearId = (int) $this->input('academic_year_id');
        $sectionId = (int) $this->input('section_id');

        if ($yearId < 1 || $sectionId < 1) {
            return false;
        }

        return app(ExamAuthorization::class)->canCreateExamForSection($user, $schoolId, $yearId, $sectionId);
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()) {
            $this->merge(['school_id' => $this->user()->school_id]);
        }

        if ($this->filled('subject_id')) {
            $subject = Subject::query()
                ->whereKey($this->input('subject_id'))
                ->where('school_id', $this->user()?->school_id)
                ->first();

            if ($subject) {
                $this->merge([
                    'standard_id' => $subject->standard_id,
                    'section_id' => $subject->section_id,
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $schoolId = (int) $this->user()->school_id;

        return [
            'school_id' => ['required', 'exists:schools,id'],
            'standard_id' => ['required', 'exists:standards,id'],
            'section_id' => [
                'required',
                Rule::exists('sections', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'academic_year_id' => [
                'required',
                Rule::exists('academic_years', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'academic_term_id' => [
                'required',
                Rule::exists('academic_terms', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'subject_id' => [
                'required',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'teacher_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('school_id', $schoolId)
                    ->where('usergroup_id', 5)
                    ->where('status', 'active')),
            ],
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'scheduled_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ];
    }
}
