<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Onboarding\Country\SchoolTypePreset;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Onboarding\Steps\OnboardingStepAwaitingInput;
use App\Onboarding\Steps\OnboardingStepReview;
use App\Onboarding\Steps\StepIssueCatalog;
use App\Services\ClassStructureService;
use App\Services\OnboardingStepsService;

class StandardsStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'standards';
    }

    public function question(): string
    {
        return 'Confirm your classes and streams.';
    }

    public function inputType(): string
    {
        return 'structure';
    }

    public function normalize(mixed $raw): mixed
    {
        $reply = SchoolTypePreset::replyKind($raw);
        if ($reply !== null) {
            return $reply;
        }

        if (is_string($raw) && in_array(strtolower(trim($raw)), ['skip', 'later', 'none', 'n/a'], true)
            && in_array('standards', OnboardingStepsService::OPTIONAL_STEPS, true)) {
            return 'skip';
        }

        if (is_array($raw)) {
            return $raw;
        }

        // Already-seeded structure: "done"/"yes" is a no-op confirm (empty list → engine no-op path rejected; use skip marker).
        if (is_string($raw) && in_array(strtolower(trim($raw)), ['done', 'yes', 'y', 'confirm', 'ok'], true)) {
            return 'confirm_seeded';
        }

        return [];
    }

    public function issues(School $school, mixed $normalized): array
    {
        if ($normalized === SchoolTypePreset::ADJUST) {
            return [];
        }

        $normalized = $this->expandPreset($school, $normalized);
        if ($normalized === SchoolTypePreset::ACCEPT) {
            return [StepIssueCatalog::fromMessage($this->key(), 'Add or confirm your classes first.')];
        }

        $issues = parent::issues($school, $normalized);
        if ($issues !== [] || ! is_array($normalized)) {
            return $issues;
        }

        return app(ClassStructureService::class)->issues($school, $normalized);
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->prepare($school, $normalized);
        $this->validate($school, $normalized);
        if ($normalized === 'skip') {
            OnboardingStepsService::markStepSkipped($school, 'standards');
            OnboardingStepReview::mark($school, 'standards');

            return;
        }
        if ($normalized === 'confirm_seeded') {
            OnboardingStepReview::mark($school, 'standards');

            return;
        }
        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year || (int) $year->school_id !== (int) $school->id) {
            $this->reject('Create an academic year before this step.');
        }
        $this->engineCall(fn () => app(ClassStructureService::class)->saveClasses($school, $year, $normalized));
        OnboardingStepReview::mark($school, 'standards');
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->prepare($school, $normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            return ['action' => 'skip', 'summary' => 'The classes step will be marked as skipped.', 'rows' => []];
        }

        if ($normalized === 'confirm_seeded') {
            $count = StandardLink::where('school_id', $school->id)->count();

            return [
                'action' => 'noop',
                'summary' => "Keeping the existing classes ({$count} class links) — nothing will change.",
                'rows' => [['label' => 'Existing classes', 'status' => 'already_present', 'detail' => "{$count} class links"]],
            ];
        }

        return app(ClassStructureService::class)->preview($school, $normalized);
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->prepare($school, $normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            OnboardingStepsService::markStepSkipped($school, 'standards');
            OnboardingStepReview::mark($school, 'standards');

            return ['created' => [], 'skipped' => [['label' => 'classes', 'reason' => 'skipped']]];
        }

        if ($normalized === 'confirm_seeded') {
            OnboardingStepReview::mark($school, 'standards');

            return ['created' => [], 'skipped' => [['label' => 'classes', 'reason' => 'already present']]];
        }

        $before = [
            'standards' => Standard::where('school_id', $school->id)->count(),
            'sections' => Section::where('school_id', $school->id)->count(),
            'links' => StandardLink::where('school_id', $school->id)->count(),
            'subjects' => \App\Models\Subject::where('school_id', $school->id)->count(),
        ];
        $this->save($school, $normalized, $userId);
        $after = [
            'standards' => Standard::where('school_id', $school->id)->count(),
            'sections' => Section::where('school_id', $school->id)->count(),
            'links' => StandardLink::where('school_id', $school->id)->count(),
            'subjects' => \App\Models\Subject::where('school_id', $school->id)->count(),
        ];

        $diff = array_sum(array_map(fn ($b, $a) => max(0, $a - $b), $before, $after));
        if ($diff === 0) {
            return ['created' => [], 'skipped' => [['label' => 'classes', 'reason' => 'already present']]];
        }

        return ['created' => [['class_rows_created' => $diff]], 'skipped' => []];
    }

    /**
     * Use this becomes the preset list. Adjust asks again and writes nothing.
     * A school type with no preset keeps the existing confirm-what-is-saved path.
     */
    private function prepare(School $school, mixed $raw): mixed
    {
        $normalized = $this->normalize($raw);
        if ($normalized === SchoolTypePreset::ADJUST) {
            throw new OnboardingStepAwaitingInput(
                'Send the classes and streams you want, or reply Use this. Nothing is saved yet.'
            );
        }

        return $this->expandPreset($school, $normalized);
    }

    private function expandPreset(School $school, mixed $normalized): mixed
    {
        if ($normalized !== SchoolTypePreset::ACCEPT) {
            return $normalized;
        }

        $preset = SchoolTypePreset::forSchool($school);
        if ($preset === null) {
            return StandardLink::where('school_id', $school->id)->exists()
                ? 'confirm_seeded'
                : SchoolTypePreset::ACCEPT;
        }

        $answer = $preset->standardsAnswerFor($school);
        $addingStreams = false;
        foreach ($answer as $class) {
            if (($class['streams'] ?? []) !== []) {
                $addingStreams = true;
                break;
            }
        }

        // Nothing new to add: keep the saved classes, including a cleared suggestion.
        if (! $addingStreams && StandardLink::where('school_id', $school->id)->exists()) {
            return 'confirm_seeded';
        }

        return $answer;
    }
}
