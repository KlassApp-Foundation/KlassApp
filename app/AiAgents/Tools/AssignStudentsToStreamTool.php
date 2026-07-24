<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\StudentIdGeneratorService;
use App\Services\ToshiActionService;

/**
 * SKETCH — companion to CreateStreamTool, designed alongside it rather
 * than deferred: CreateStreamTool's guardAgainstMixedMode() explicitly
 * allows streaming a previously-direct class BECAUSE this tool exists to
 * finish the job. If this tool is ever dropped, that guard needs
 * revisiting too.
 *
 * Moves ALREADY-EXISTING students (StudentAcademic rows) into a named
 * stream's StandardLink. Does not create new students — that's
 * AddStudentTool's job. This is purely a reassignment of existing
 * roster entries, which is the actual work of "splitting a class into
 * streams" once the stream slots exist (CreateStreamTool).
 *
 * Matching strategy: registration_number first if uniquely matched
 * (checked via count(), not first() — the column has no DB uniqueness
 * constraint yet), then case-insensitive name match, also requiring a
 * unique hit. Anything ambiguous or unmatched is reported back rather
 * than guessed — a wrong match here silently reassigns a real student's
 * class, which is worse than doing nothing.
 *
 * registration_number is now in User::$fillable (Step 0 fix) but still
 * rarely populated in practice. Name-matching carries most of the real
 * traffic for now.
 *
 * Filters all StudentAcademic lookups by the target stream's
 * academic_year_id (not just user_id) — a student enrolled across
 * multiple years otherwise risks matching a prior year's record instead
 * of the current one.
 *
 * Confirm before wiring in:
 *  - Whether a student can legitimately have more than one StudentAcademic
 *    row per academic_year_id (this sketch assumes one active row per
 *    student per year and updates it in place; if multiple are possible,
 *    this needs a "which one" disambiguation step)
 */
class AssignStudentsToStreamTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): string
    {
        return "Sort a list of already-enrolled students into a specific stream, e.g. 'Put James, Grace, and Peter in East'. The class and stream must already exist. Only reassigns existing students — does not create new ones.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'className' => $schema->string()->description('The class the stream belongs to, e.g. "Primary 1".'),
            'streamName' => $schema->string()->description('The stream to assign students into, e.g. "East".'),
            'students' => $schema->array()
                ->items($schema->string())
                ->description('Student names or registration numbers, one per entry. Registration numbers are matched exactly; names are matched case-insensitively and must be unambiguous within the school.'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (!$schoolId) {
            return '❌ You are not assigned to a school.';
        }

        $className = trim((string) $request->get('className'));
        $streamName = trim((string) $request->get('streamName'));
        $students = $this->normalizeNames($request->get('students'));

        if ($className === '' || $streamName === '' || empty($students)) {
            return '❌ Please provide a class, a stream, and at least one student.';
        }

        $section = Section::where('school_id', $schoolId)->where('name', $className)->first();
        if (!$section) {
            return "❌ Class \"{$className}\" doesn't exist.";
        }

        $targetLink = StandardLink::where('school_id', $schoolId)
            ->where('section_id', $section->id)
            ->where('stream', $streamName)
            ->first();
        if (!$targetLink) {
            return "❌ Stream \"{$streamName}\" doesn't exist on {$className} yet. Create it first.";
        }

        // Resolve matches BEFORE confirmation so the confirm preview shows
        // exactly who will move, and unmatched/ambiguous names surface
        // before any write happens.
        [$matched, $unmatched, $ambiguous] = $this->resolveStudents($schoolId, $students);

        if (empty($matched)) {
            return '❌ None of the given students could be matched. Unmatched: ' . implode(', ', $unmatched)
                . (empty($ambiguous) ? '' : ' | Ambiguous (matched multiple students, need registration number): ' . implode(', ', $ambiguous));
        }

        $matchedNames = array_map(fn($m) => $m['label'], $matched);
        $args = ['className' => $className, 'streamName' => $streamName, 'students' => $students];

        $previewNote = '';
        if (!empty($unmatched)) $previewNote .= ' Unmatched (skipped): ' . implode(', ', $unmatched) . '.';
        if (!empty($ambiguous)) $previewNote .= ' Ambiguous (skipped, use registration number): ' . implode(', ', $ambiguous) . '.';

        $confirm = $this->confirmOrExecute('toolAssignStudentsToStream', $args,
            fn() => "Move " . count($matched) . " student(s) into {$className} / {$streamName}: " . implode(', ', $matchedNames) . '.' . $previewNote);
        if ($confirm !== null) return $confirm;

        $moved = [];
        foreach ($matched as $m) {
            $studentAcademic = StudentAcademic::where('school_id', $schoolId)
                ->where('user_id', $m['user']->id)
                ->where('academic_year_id', $targetLink->academic_year_id)
                ->first();

            if ($studentAcademic) {
                $studentAcademic->update(['standardLink_id' => $targetLink->id]);
            } else {
                // Student exists as a User but has no StudentAcademic row
                // yet (e.g. imported without class assignment). Create one
                // rather than silently skipping — mirrors commitAll()'s
                // StudentAcademic::create shape.
                StudentAcademic::create([
                    'school_id' => $schoolId,
                    'academic_year_id' => $targetLink->academic_year_id,
                    'user_id' => $m['user']->id,
                    'standardLink_id' => $targetLink->id,
                    'klassapp_student_id' => StudentIdGeneratorService::next($schoolId),
                ]);
            }

            $moved[] = $m['label'];
        }

        $message = "✅ Moved **" . count($moved) . "** student(s) into {$className} / {$streamName}: " . implode(', ', $moved) . '.';
        if (!empty($unmatched)) $message .= "\n⚠️ Unmatched (not moved): " . implode(', ', $unmatched) . '.';
        if (!empty($ambiguous)) $message .= "\n⚠️ Ambiguous (not moved, give registration number instead): " . implode(', ', $ambiguous) . '.';

        return $message;
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school ID for verification.'];
        }

        $className = trim((string) $request->get('className'));
        $streamName = trim((string) $request->get('streamName'));
        $students = $this->normalizeNames($request->get('students'));

        $section = Section::where('school_id', $schoolId)->where('name', $className)->first();
        $targetLink = $section
            ? StandardLink::where('school_id', $schoolId)->where('section_id', $section->id)->where('stream', $streamName)->first()
            : null;

        if (!$targetLink) {
            return ['verified' => false, 'message' => "Stream \"{$streamName}\" not found on {$className}."];
        }

        [$matched, , ] = $this->resolveStudents($schoolId, $students);
        $stillWrong = [];

        foreach ($matched as $m) {
            $studentAcademic = StudentAcademic::where('school_id', $schoolId)
                ->where('user_id', $m['user']->id)
                ->where('academic_year_id', $targetLink->academic_year_id)
                ->first();
            if (!$studentAcademic || (int) $studentAcademic->standardLink_id !== (int) $targetLink->id) {
                $stillWrong[] = $m['label'];
            }
        }

        return [
            'verified' => empty($stillWrong),
            'message' => empty($stillWrong)
                ? 'All matched students confirmed on the target stream link.'
                : 'Not confirmed on target stream: ' . implode(', ', $stillWrong),
        ];
    }

    /**
     * @return array{0: array<int, array{user: User, label: string}>, 1: string[], 2: string[]}
     *   [matched, unmatched, ambiguous]
     */
    private function resolveStudents(int $schoolId, array $students): array
    {
        $matched = [];
        $unmatched = [];
        $ambiguous = [];

        foreach ($students as $identifier) {
            // Registration number: exact match, but NOT guaranteed unique in
            // the DB (no constraint currently enforces it, and legacy rows
            // were seeded with date('YmdHis'), which can collide). Check
            // count like the name-match branch below — don't silently take
            // the first row.
            $byRegNo = User::where('school_id', $schoolId)
                ->where('usergroup_id', 6)
                ->where('registration_number', $identifier)
                ->get();

            if ($byRegNo->count() === 1) {
                $matched[] = ['user' => $byRegNo->first(), 'label' => $identifier];
                continue;
            }
            if ($byRegNo->count() > 1) {
                $ambiguous[] = $identifier;
                continue;
            }

            // Name match — must be unambiguous within the school.
            $byName = User::where('school_id', $schoolId)
                ->where('usergroup_id', 6)
                ->whereRaw('LOWER(name) = ?', [strtolower($identifier)])
                ->get();

            if ($byName->count() === 1) {
                $matched[] = ['user' => $byName->first(), 'label' => $identifier];
            } elseif ($byName->count() > 1) {
                $ambiguous[] = $identifier;
            } else {
                $unmatched[] = $identifier;
            }
        }

        return [$matched, $unmatched, $ambiguous];
    }

    private function normalizeNames($raw): array
    {
        if (!is_array($raw)) return [];
        $names = array_map(fn($n) => trim((string) $n), $raw);
        $names = array_filter($names, fn($n) => $n !== '');
        return array_values(array_unique($names));
    }
}