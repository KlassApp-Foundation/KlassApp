<?php

namespace App\AiAgents\Tools\Marks;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Whether a learner's report is released, which sets are included, and what is not released yet.
 * Values come from the release gate and the report services. This tool does not calculate a mark.
 */
class LearnerReportStatusTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Read one learner\'s report status: whether the current term is released, which exam sets the report includes, '
            .'and which sets are not released yet. If several learners match the name, ask which learner. '
            .'Do not calculate, round, or restate a mark, total, aggregate, or division.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'learner' => $schema->string()->description('Learner name, for example Daphine')->required(),
            'learner_id' => $schema->integer()->description('Use only after the person picks one learner from the list you showed.')->optional(),
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

        $learnerId = $request->get('learner_id');
        $status = $support->reportStatus(
            $user,
            (string) $request->get('learner', ''),
            $learnerId === null || $learnerId === '' ? null : (int) $learnerId,
        );
        if (is_string($status)) {
            return $status;
        }

        return $support->encode($status);
    }
}
