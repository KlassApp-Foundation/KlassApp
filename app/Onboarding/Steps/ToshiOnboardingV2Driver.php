<?php

namespace App\Onboarding\Steps;

use App\Models\School;
use App\Onboarding\Steps\Contracts\OnboardingStep;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Chat loop over StepRegistry for Toshi Completing Setup when toshi.onboarding_v2 is on.
 */
class ToshiOnboardingV2Driver
{
    public const COMING_SOON_HINT = "Toshi's assistant is coming soon; for now, please choose one of the options above.";

    public function __construct(private StepRegistry $registry)
    {
    }

    /**
     * @return array{status: 'prompt'|'done', key: ?string, question: ?string, options: list<array{value: string, label: string}>, input_type: ?string}
     */
    public function promptNext(School $school, ?int $userId = null): array
    {
        $step = $this->registry->nextUnfinished($school, $userId);
        if ($step === null) {
            return [
                'status' => 'done',
                'key' => null,
                'question' => null,
                'options' => [],
                'input_type' => null,
            ];
        }

        return $this->promptPayload($step, $school);
    }

    /**
     * @return array{status: 'advanced'|'rejected'|'done', key: ?string, question: ?string, options: list<array{value: string, label: string}>, input_type: ?string, hint: ?string, saved_key: ?string}
     */
    public function handleReply(School $school, mixed $raw, ?int $userId = null): array
    {
        $step = $this->registry->nextUnfinished($school, $userId);
        if ($step === null) {
            return [
                'status' => 'done',
                'key' => null,
                'question' => null,
                'options' => [],
                'input_type' => null,
                'hint' => null,
                'saved_key' => null,
            ];
        }

        $savedKey = $step->key();

        try {
            $step->save($school, $raw, $userId);
        } catch (InvalidArgumentException|ValidationException) {
            $payload = $this->promptPayload($step, $school->fresh());

            return [
                'status' => 'rejected',
                'key' => $payload['key'],
                'question' => $payload['question'],
                'options' => $payload['options'],
                'input_type' => $payload['input_type'],
                'hint' => self::COMING_SOON_HINT,
                'saved_key' => null,
            ];
        }

        $school = $school->fresh();
        $next = $this->promptNext($school, $userId);
        if ($next['status'] === 'done') {
            return [
                'status' => 'done',
                'key' => null,
                'question' => null,
                'options' => [],
                'input_type' => null,
                'hint' => null,
                'saved_key' => $savedKey,
            ];
        }

        return [
            'status' => 'advanced',
            'key' => $next['key'],
            'question' => $next['question'],
            'options' => $next['options'],
            'input_type' => $next['input_type'],
            'hint' => null,
            'saved_key' => $savedKey,
        ];
    }

    /**
     * @return array{status: 'prompt', key: string, question: string, options: list<array{value: string, label: string}>, input_type: string}
     */
    private function promptPayload(OnboardingStep $step, School $school): array
    {
        return [
            'status' => 'prompt',
            'key' => $step->key(),
            'question' => $step->question(),
            'options' => $step->options($school),
            'input_type' => $step->inputType(),
        ];
    }
}
