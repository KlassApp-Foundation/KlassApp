<?php

namespace App\Onboarding\Steps;

use App\Models\School;
use App\Models\SchoolDetail;
use App\Onboarding\Country\CountryProfiles;
use App\Onboarding\Country\SchoolTypePreset;
use App\Onboarding\Steps\Contracts\OnboardingStep;
use App\Services\ClassStructureService;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Chat loop over StepRegistry for Toshi Completing Setup when toshi.onboarding_v2 is on.
 */
class ToshiOnboardingV2Driver
{
    public const COMING_SOON_HINT = "Toshi's assistant is coming soon; for now, please choose one of the options above.";

    /**
     * Names shown after Apply suggested structure, before Confirm.
     * This is not the classes-step review mark.
     */
    private const STRUCTURE_PREVIEW_META = 'onboarding_v2_structure_preview';

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

        if ($step->key() === 'standards') {
            if (SchoolTypePreset::replyKind($raw) === SchoolTypePreset::ADJUST) {
                $this->clearStructurePreview($school);
            }

            $previewReply = $this->structurePreviewReply($school, $step, $raw);
            if ($previewReply !== null) {
                return $previewReply;
            }

            if ($this->isStructureConfirm($raw) && $this->storedStructurePreview($school) !== null) {
                $pending = $this->storedStructurePreview($school);
                $plan = app(ClassStructureService::class)->suggestedStructurePlan($school);
                if ($plan['items'] !== $pending) {
                    $this->storeStructurePreview($school, $plan['items']);

                    return $this->awaitingStandards(
                        $school,
                        $step,
                        'The suggestion changed. Review the list, then confirm. '
                        .app(ClassStructureService::class)->suggestedStructurePreviewMessage($school),
                        $this->confirmOptions(),
                    );
                }

                $this->clearStructurePreview($school);
                $raw = 'apply';
            }
        }

        try {
            // Preview describes the write. Use this on an empty school applies it.
            // Use this after structure exists stops before the write.
            if ($step->key() === 'standards') {
                $step->preview($school, $raw, $userId);
            }
            $step->save($school, $raw, $userId);
        } catch (OnboardingStepAwaitingInput $e) {
            if ($step->key() === 'standards' && str_contains($e->getMessage(), 'Reply Confirm to add them')) {
                $this->storeStructurePreview(
                    $school,
                    app(ClassStructureService::class)->suggestedStructurePlan($school)['items'],
                );
            }
            return [
                'status' => 'awaiting',
                'key' => $step->key(),
                'question' => $e->getMessage(),
                'options' => str_contains($e->getMessage(), 'Reply Confirm to add them')
                    ? $this->confirmOptions()
                    : $step->options($school),
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
                    $options = $step->key() === 'standards'
                        ? $this->standardsOffer($school)
                        : SchoolTypePreset::actions();
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

    /**
     * Use this only while the school has no classes or streams.
     * After that, the offer is the same preview-then-confirm action as the wizard.
     *
     * @return list<array{value: string, label: string}>
     */
    private function standardsOffer(School $school): array
    {
        if (SchoolTypePreset::forSchool($school) === null) {
            return [];
        }

        if (! app(ClassStructureService::class)->hasStructure($school)) {
            return SchoolTypePreset::actions();
        }

        return [
            ['value' => 'apply_suggested', 'label' => 'Apply suggested structure'],
            ['value' => 'adjust', 'label' => 'Adjust'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function confirmOptions(): array
    {
        return [
            ['value' => 'confirm', 'label' => 'Confirm'],
            ['value' => 'cancel', 'label' => 'Cancel'],
        ];
    }

    /**
     * Apply suggested structure shows the list and writes nothing.
     * Cancel writes nothing. Confirm is handled by the caller.
     *
     * @return array{status: string, key: string, question: string, options: list<array{value: string, label: string}>, input_type: string, hint: null, saved_key: null}|null
     */
    private function structurePreviewReply(School $school, OnboardingStep $step, mixed $raw): ?array
    {
        if (! is_string($raw)) {
            return null;
        }

        $text = strtolower(trim($raw));
        if (in_array($text, ['apply_suggested', 'apply suggested structure'], true)) {
            if (SchoolTypePreset::forSchool($school) === null) {
                return null;
            }

            $plan = app(ClassStructureService::class)->suggestedStructurePlan($school);
            $this->storeStructurePreview($school, $plan['items']);

            return $this->awaitingStandards(
                $school,
                $step,
                app(ClassStructureService::class)->suggestedStructurePreviewMessage($school),
                $this->confirmOptions(),
            );
        }

        if ($text !== 'cancel') {
            return null;
        }

        $this->clearStructurePreview($school);

        return $this->awaitingStandards(
            $school,
            $step,
            'Nothing was added.',
            $this->standardsOffer($school),
        );
    }

    /**
     * @param  list<array{value: string, label: string}>  $options
     * @return array{status: string, key: string, question: string, options: list<array{value: string, label: string}>, input_type: string, hint: null, saved_key: null}
     */
    private function awaitingStandards(School $school, OnboardingStep $step, string $question, array $options): array
    {
        return [
            'status' => 'awaiting',
            'key' => $step->key(),
            'question' => $question,
            'options' => $options,
            'input_type' => $step->inputType(),
            'hint' => null,
            'saved_key' => null,
        ];
    }

    private function isStructureConfirm(mixed $raw): bool
    {
        return is_string($raw) && in_array(strtolower(trim($raw)), ['confirm', 'yes', 'y'], true);
    }

    /**
     * @return list<string>|null
     */
    private function storedStructurePreview(School $school): ?array
    {
        $value = SchoolDetail::query()
            ->where('school_id', $school->id)
            ->where('meta_key', self::STRUCTURE_PREVIEW_META)
            ->value('meta_value');
        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values($decoded) : null;
    }

    /**
     * @param  list<string>  $items
     */
    private function storeStructurePreview(School $school, array $items): void
    {
        SchoolDetail::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'meta_key' => self::STRUCTURE_PREVIEW_META,
            ],
            ['meta_value' => json_encode(array_values($items))],
        );
    }

    private function clearStructurePreview(School $school): void
    {
        SchoolDetail::query()
            ->where('school_id', $school->id)
            ->where('meta_key', self::STRUCTURE_PREVIEW_META)
            ->delete();
    }
}
