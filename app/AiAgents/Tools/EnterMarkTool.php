<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Academics\Marks;
use App\Services\EntityResolver;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\DB;

class EnterMarkTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    private ?int $resolvedStudentId = null;
    private ?int $resolvedExamId = null;

    public function description(): string
    {
        return 'Enter an exam mark for a student. Provide the student name and subject name (not IDs), and the score. If the student name is ambiguous (multiple matches), you will receive a list of IDs — reply with the ID number to disambiguate.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'student_name' => $schema->string()->description('Student full name, e.g. "John Smith"'),
            'student_identifier' => $schema->string()->optional()->description('Student ID number, admission number, or klassapp_student_id. Use ONLY when the name was ambiguous and you need to disambiguate — set disambiguation_confirmed=true when the user explicitly replied with this ID after seeing the candidate list.'),
            'disambiguation_confirmed' => $schema->boolean()->optional()->description('Set to true ONLY when the ambiguous-match candidate list was shown to the user AND they replied with a specific ID from that list. Never set this without showing the candidates first.'),
            'subject_name' => $schema->string()->description('Subject name, e.g. "Mathematics" or "English"'),
            'marks' => $schema->number()->min(0)->max(100)->description('Score 0-100'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);

        // Second pass (bypassConfirm): IDs already resolved, use them directly
        $existingStudentId = $request->get('student_id');
        $existingExamId = $request->get('exam_id');

        if ($existingStudentId && $existingExamId) {
            $marks = $request->get('marks');
            $args = [
                'student_id' => $existingStudentId,
                'exam_id' => $existingExamId,
                'marks' => $marks,
            ];
            $confirm = $this->confirmOrExecute('toolEnterMark', $args,
                fn() => "Enter mark: student {$existingStudentId}, exam {$existingExamId}, score {$marks}");
            if ($confirm !== null) return $confirm;

            $user = ToshiActionService::getEffectiveUser($user);
            $result = ToshiActionService::enterMark($user, $args);
            return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
        }

        // Identifier-based resolution: strongest disambiguator, skips name matching entirely
        $identifier = trim($request->get('student_identifier', ''));
        if ($identifier !== '') {
            $idResult = EntityResolver::resolveStudentByIdentifier($schoolId, $identifier);
            if ($idResult['state'] === 'not_found') {
                return "❌ No student found with identifier \"{$identifier}\". Please check the ID and try again, or use the student's full name.";
            }
            $resolvedUser = $idResult['model'];

            // Duplicate-name guard: if this student has a duplicate name at
            // this school, block the write UNLESS the user explicitly
            // disambiguated (disambiguation_confirmed=true). This prevents
            // the LLM from silently resolving an ambiguous name without
            // surfacing the duplicate to the user first.
            $confirmed = (bool) $request->get('disambiguation_confirmed', false);
            if (!$confirmed) {
                $dupError = ToshiActionService::guardDuplicateName($schoolId, $resolvedUser->id, 'student');
                if ($dupError) {
                    $name = $resolvedUser->name;
                    $dupeIds = \App\Models\User::where('school_id', $schoolId)
                        ->where('name', $name)
                        ->pluck('id');
                    $lines = [];
                    foreach ($dupeIds as $id) {
                        $u = \App\Models\User::find($id);
                        $class = $u ? EntityResolver::classLabelForUser($schoolId, $u) : null;
                        $classInfo = $class ? ", {$class}" : '';
                        $lines[] = "ID {$id}: {$name}{$classInfo}";
                    }
                    return "Found **" . count($dupeIds) . "** students named \"{$name}\":\n" . implode("\n", $lines) . "\n\nPlease reply with the ID number to specify which one (e.g. \"124\").";
                }
            }

            // Resolve exam by subject name
            $subjectName = trim($request->get('subject_name', ''));
            if ($subjectName === '') {
                return '❌ Subject name is required.';
            }
            $examResult = EntityResolver::resolveExam($schoolId, $subjectName);
            if ($examResult['state'] === 'not_found') {
                return "❌ Subject \"{$subjectName}\" not found. Available subjects: English, Mathematics, Science, Social Studies.";
            }
            if ($examResult['state'] === 'ambiguous') {
                $list = implode('; ', array_map(fn($c) => $c['name'] . ' (ID: ' . $c['id'] . ')', $examResult['candidates']));
                return "Found **" . count($examResult['candidates']) . "** subjects matching \"{$subjectName}\":\n{$list}\n\nPlease specify which subject.";
            }

            $marks = $request->get('marks');
            $args = [
                'student_id' => $resolvedUser->id,
                'exam_id' => $examResult['model']->id,
                'marks' => $marks,
            ];

            $confirm = $this->confirmOrExecute('toolEnterMark', $args,
                fn() => "Enter mark: {$resolvedUser->name} → {$examResult['model']->subject?->name} ({$examResult['model']->examType?->name}): {$marks}");
            if ($confirm !== null) return $confirm;

            $user = ToshiActionService::getEffectiveUser($user);
            $result = ToshiActionService::enterMark($user, $args);
            return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
        }

        // First pass: resolve names to IDs
        $studentName = trim($request->get('student_name', ''));

        // ... rest of name-based resolution
        $subjectName = trim($request->get('subject_name', ''));
        $marks = $request->get('marks');

        if ($studentName === '' || $subjectName === '') {
            return '❌ Student name and subject are required. Please specify which student and which subject, and the score.';
        }

        // Resolve student name to ID internally
        $studentResult = EntityResolver::resolveStudent($schoolId, $studentName);
        if ($studentResult['state'] === 'not_found') {
            return "❌ Student \"{$studentName}\" not found. Please check the spelling.";
        }
        if ($studentResult['state'] === 'ambiguous') {
            $lines = [];
            foreach ($studentResult['candidates'] as $c) {
                $classInfo = isset($c['class']) ? ", {$c['class']}" : '';
                $lines[] = "ID {$c['id']}: {$c['name']}{$classInfo}";
            }
            $list = implode("\n", $lines);
            return "Found **" . count($studentResult['candidates']) . "** students matching \"{$studentName}\":\n{$list}\n\nPlease reply with the ID number to specify which one (e.g. \"124\").";
        }

        // Resolve exam by subject name internally
        $examResult = EntityResolver::resolveExam($schoolId, $subjectName);
        if ($examResult['state'] === 'not_found') {
            return "❌ Subject \"{$subjectName}\" not found. Available subjects: English, Mathematics, Science, Social Studies.";
        }
        if ($examResult['state'] === 'ambiguous') {
            $list = implode('; ', array_map(fn($c) => $c['name'] . ' (ID: ' . $c['id'] . ')', $examResult['candidates']));
            return "Found **" . count($examResult['candidates']) . "** subjects matching \"{$subjectName}\":\n{$list}\n\nPlease specify which subject.";
        }

        $studentId = $studentResult['model']->id;
        $examId = $examResult['model']->id;

        // Final guard: reject IDs that are obviously wrong
        if ($studentId <= 0 || $examId <= 0) {
            return '❌ Failed to resolve valid student or exam identifiers.';
        }

        $this->resolvedStudentId = $studentId;
        $this->resolvedExamId = $examId;

        $studentName = $studentResult['model']->name;
        $examDisplayName = $examResult['model']->subject?->name ?? 'Exam';
        $examTypeName = $examResult['model']->examType?->name ?? '';

        $args = [
            'student_id' => $studentId,
            'exam_id' => $examId,
            'marks' => $marks,
        ];

        $confirm = $this->confirmOrExecute('toolEnterMark', $args,
            fn() => "Enter mark: {$studentName} → {$examDisplayName} ({$examTypeName}): {$marks}");
        if ($confirm !== null) return $confirm;

        $user = ToshiActionService::getEffectiveUser($user);
        $result = ToshiActionService::enterMark($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $studentId = $request->get('student_id');
        $examId = $request->get('exam_id');
        if (!$studentId || !$examId) {
            return ['verified' => false, 'message' => 'Student ID and exam ID are required for verification.'];
        }

        $exists = Marks::where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Mark record confirmed in database.'
                : 'Mark was not found after writing.',
        ];
    }
}
