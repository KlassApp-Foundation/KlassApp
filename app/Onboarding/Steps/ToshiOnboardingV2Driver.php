<?php

namespace App\Onboarding\Steps;

use App\Models\School;
use App\Onboarding\Country\CountryProfiles;
use App\Onboarding\Country\SchoolTypePreset;
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
        $step = $this->currentStep($school, $userId);
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
     * @return array{status: 'advanced'|'rejected'|'awaiting'|'done', key: ?string, question: ?string, options: list<array{value: string, label: string}>, input_type: ?string, hint: ?string, saved_key: ?string}
     */
    public function handleReply(School $school, mixed $raw, ?int $userId = null): array
    {
        $step = $this->currentStep($school, $userId);
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
            // Preview describes the write. Use this stops before it; Apply confirms.
            if ($step->key() === 'standards') {
                $step->preview($school, $raw, $userId);
            }
            $step->save($school, $raw, $userId);
        } catch (OnboardingStepAwaitingInput $e) {
            return [
                'status' => 'awaiting',
                'key' => $step->key(),
                'question' => $e->getMessage(),
                'options' => $step->options($school),
                'input_type' => $step->inputType(),
                'hint' => null,
                'saved_key' => null,
            ];
        } catch (InvalidArgumentException|ValidationException $e) {
            $payload = $this->promptPayload($step, $school->fresh());

            return [
                'status' => 'rejected',
                'key' => $payload['key'],
                'question' => $payload['question'],
                'options' => $payload['options'],
                'input_type' => $payload['input_type'],
                'hint' => $this->rejectionHint($step, $e),
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
    /**
     * Seeded classes and subjects are already "complete" after the academic year
     * is saved. Ask for them once anyway, until the school confirms or edits them.
     */
    private function currentStep(School $school, ?int $userId): ?OnboardingStep
    {
        foreach ($this->registry->forSchool($school) as $step) {
            if (! $step->isComplete($school, $userId)) {
                return $step;
            }

            if (in_array($step->key(), ['standards', 'subjects'], true)
                && ! OnboardingStepReview::reviewed($school, $step->key())) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Engine validation messages (a duplicate or overlong EMIS code) and the
     * academic-year and fee checks tell the admin what to fix. Other rejections
     * keep the generic hint.
     */
    private function rejectionHint(OnboardingStep $step, InvalidArgumentException|ValidationException $e): string
    {
        if ($e instanceof ValidationException) {
            $message = collect($e->errors())->flatten()->first();

            return is_string($message) && trim($message) !== ''
                ? trim($message)
                : self::COMING_SOON_HINT;
        }

        $message = trim($e->getMessage());
        if ($message !== '' && in_array($step->key(), ['academic_year', 'fees', 'standards'], true)) {
            return $message;
        }

        return self::COMING_SOON_HINT;
    }

    private function promptPayload(OnboardingStep $step, School $school): array
    {
        $options = $step->options($school);
        $question = $step->question();
        if (in_array($step->key(), ['standards', 'subjects', 'terms'], true)) {
            $preset = SchoolTypePreset::forSchool($school);
            if ($preset !== null) {
                $profile = CountryProfiles::forCountry($school->registration_country);
                $question .= ' '.$preset->suggestion($step->key(), $profile, SchoolTypePreset::suggestionYear($school));
                if ($options === []) {
                    $options = SchoolTypePreset::actions();
                }
            }
        }

        return [
            'status' => 'prompt',
            'key' => $step->key(),
            'question' => $question,
            'options' => $options,
            'input_type' => $step->inputType(),
        ];
    }
}
