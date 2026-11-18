<?php

namespace App\AiAgents\Tools\Marks;

use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Models\Section;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * A draft reminder for the class teacher. Confirmation shows the draft.
 * There is no send path: confirming does not message anyone.
 */
class PrepareClassTeacherReminderTool implements Tool
{
    use ConfirmsBeforeWrite;

    public const CONFIRM_KEY = 'toolPrepareClassTeacherReminder';

    public function description(): Stringable|string
    {
        return 'Prepare a draft reminder for the class teacher about missing or unapproved marks. '
            .'Shows a confirmation card. Nothing is sent, even after confirmation. '
            .'Do not calculate, round, or restate a mark, total, aggregate, or division.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'class' => $schema->string()->description('Class name, for example P.5')->required(),
            'exam_set' => $schema->string()->description('Exam set name, for example Mid term')->required(),
            'term' => $schema->string()->description('Term name. Omit to use the current term.')->optional(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $support = new MarksToolSupport;
        $user = $support->actor();
        $error = $support->refusal($user);
        if ($error !== null) {
            return $error;
        }

        $className = (string) $request->get('class', '');
        $examSet = (string) $request->get('exam_set', '');
        $term = $request->get('term');
        $snapshot = $support->overview($user, $className, $examSet, $term);
        if (is_string($snapshot)) {
            return $snapshot;
        }

        $section = Section::query()
            ->where('school_id', $user->school_id)
            ->where('status', 1)
            ->whereKey($snapshot['section_id'])
            ->first();
        $names = $section === null ? [] : $support->classTeacherNames((int) $user->school_id, $section);
        $draft = $support->draft($snapshot, $names);

        $args = [
            'class' => $className,
            'exam_set' => $examSet,
            'term' => $term,
        ];
        $confirm = $this->confirmOrExecute(self::CONFIRM_KEY, $args, fn () => $draft);
        if ($confirm !== null) {
            return $confirm;
        }

        return $draft."\n\n"
            .'There is no confirmation-gated send path for a reminder to a class teacher, so this stays a draft. Nothing was sent.';
    }
}
