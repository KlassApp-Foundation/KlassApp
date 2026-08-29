<?php

namespace App\Http\Requests\Teacher;

use App\Models\Academics\Exam;
use App\Services\ExamAuthorization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $exam = $this->route('exam');

        if (! $user || ! $exam instanceof Exam) {
            return false;
        }

        return app(ExamAuthorization::class)->canActOnExam($user, $exam);
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()) {
            $this->merge(['school_id' => $this->user()->school_id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $schoolId = (int) $this->user()->school_id;

        return [
            'school_id' => ['sometimes', 'exists:schools,id'],
            'academic_year_id' => [
                'sometimes',
                Rule::exists('academic_years', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'academic_term_id' => [
                'sometimes',
                Rule::exists('academic_terms', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'subject_id' => [
                'sometimes',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'teacher_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('school_id', $schoolId)
                    ->where('usergroup_id', 5)
                    ->where('status', 'active')),
            ],
            'exam_type_id' => ['sometimes', 'exists:exam_types,id'],
            'scheduled_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'status' => ['sometimes', 'in:done,postponed,undone,submitted'],
        ];
    }
}
