<?php

namespace App\AiAgents\Tools\Marks;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Missing and unapproved marks for one class and exam set.
 * The words come from the marks overview. This tool does not calculate a mark.
 */
class MissingMarksTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Read which subjects and learners are missing or not approved for one class and one exam set. '
            .'Uses the marks overview only. Do not calculate, round, or restate a mark, total, aggregate, or division.';
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

        $snapshot = $support->overview(
            $user,
            (string) $request->get('class', ''),
            (string) $request->get('exam_set', ''),
            $request->get('term'),
        );
        if (is_string($snapshot)) {
            return $snapshot;
        }

        return $support->encode($snapshot);
    }
}
