<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Teacherlink;
use App\Models\Subject;
use App\Models\User;

class AssignTeacherTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return 'Assign a teacher to a subject and class. Provide teacher email, subject name, and class name.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'teacher_email' => $schema->string()->description('Teacher email address'),
            'subject_name' => $schema->string()->description('Subject name'),
            'class_name' => $schema->string()->description('Class name'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = [
            'teacher_email' => $request->get('teacher_email'),
            'subject_name' => $request->get('subject_name'),
            'class_name' => $request->get('class_name'),
        ];

        $confirm = $this->confirmOrExecute('toolAssignTeacher', $args,
            fn() => "Assign teacher {$args['teacher_email']} to {$args['subject_name']} for {$args['class_name']}");
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::assignTeacher($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $teacherEmail = trim($request->get('teacher_email', ''));
        $subjectName = trim($request->get('subject_name', ''));
        $className = trim($request->get('class_name', ''));
        if ($teacherEmail === '' || $subjectName === '' || $className === '') {
            return ['verified' => false, 'message' => 'Teacher email, subject, and class name are required for verification.'];
        }

        $teacher = User::where('school_id', $schoolId)
            ->where('email', $teacherEmail)
            ->where('usergroup_id', 5)
            ->first();
        if (!$teacher) {
            return ['verified' => false, 'message' => 'Teacher not found during verification.'];
        }

        $subject = Subject::where('school_id', $schoolId)
            ->where('name', $subjectName)
            ->first();
        if (!$subject) {
            return ['verified' => false, 'message' => 'Subject not found during verification.'];
        }

        $standardLink = \App\Models\StandardLink::where('school_id', $schoolId)
            ->whereHas('section', function ($q) use ($className) {
                $q->where('name', 'LIKE', '%' . $className . '%');
            })
            ->first();
        if (!$standardLink) {
            return ['verified' => false, 'message' => 'Class not found during verification.'];
        }

        $exists = Teacherlink::where('school_id', $schoolId)
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subject->id)
            ->where('standardLink_id', $standardLink->id)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Teacher assignment confirmed in database.'
                : 'Teacher assignment was not found after writing.',
        ];
    }
}
