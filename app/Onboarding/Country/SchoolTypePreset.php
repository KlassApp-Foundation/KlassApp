<?php

namespace App\Onboarding\Country;

use App\Models\AcademicYear;
use App\Models\School;
use App\Services\ClassStructureService;
use App\Services\SchoolCategorySeeder;

/**
 * Recommended classes, streams and subjects for one school type.
 *
 * Reading a preset does not write. It is applied only when the school has no
 * classes or streams yet, or when an admin confirms the preview from
 * ClassStructureService. A type this profile does not know returns null so
 * the existing P1 / Mathematics defaults stay.
 */
final class SchoolTypePreset
{
    public const ACCEPT = 'use_preset';

    public const APPLY = 'apply_preset';

    public const ADJUST = 'adjust';

    /** Usual UACE combinations. Nursery, primary and O-level stay unstreamed. */
    public const A_LEVEL_STREAMS = ['PCM', 'BCM', 'HEG', 'MEG'];

    /**
     * @param  list<array{name: string, streams: list<string>}>  $classes
     * @param  array<string, list<string>>  $subjectsByClass
     */
    public function __construct(
        public string $category,
        public string $label,
        public array $classes,
        public array $subjectsByClass,
    ) {}

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function actions(): array
    {
        return [
            ['value' => 'use_this', 'label' => 'Use this'],
            ['value' => 'adjust', 'label' => 'Adjust'],
        ];
    }

    public static function forSchool(School $school): ?self
    {
        $category = is_string($school->school_category) ? $school->school_category : null;

        return CountryProfiles::forCountry($school->registration_country)->preset($category);
    }

    public static function uganda(?string $category): ?self
    {
        if (! is_string($category) || ! array_key_exists($category, SchoolCategorySeeder::CATEGORIES)) {
            return null;
        }

        $classes = [];
        $subjectsByClass = [];
        foreach (SchoolCategorySeeder::levelTypes($category) as $level) {
            $streams = $level === 'a-level' ? self::A_LEVEL_STREAMS : [];
            $subjects = SchoolCategorySeeder::subjectNamesForLevel($level);
            foreach (SchoolCategorySeeder::sectionNamesForLevel($level) as $name) {
                $classes[] = ['name' => $name, 'streams' => $streams];
                $subjectsByClass[$name] = $subjects;
            }
        }

        if ($classes === []) {
            return null;
        }

        return new self(
            $category,
            SchoolCategorySeeder::CATEGORIES[$category],
            $classes,
            $subjectsByClass,
        );
    }

    /**
     * Term dates from the country profile. A preset does not invent a second calendar.
     *
     * @return list<array{name: string, start: string, end: string}>
     */
    public static function termsFor(?string $country, ?string $category, int $year): array
    {
        $profile = CountryProfiles::forCountry($country);
        $preset = $profile->preset($category);
        if ($preset !== null) {
            return $preset->terms($profile, $year);
        }

        return $profile->defaultTerms($year);
    }

    public static function replyKind(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $text = strtolower(trim($raw));
        if (in_array($text, ['use_this', 'use this', 'preset', 'use_preset'], true)) {
            return self::ACCEPT;
        }
        if (in_array($text, ['apply', 'apply_suggested', 'apply suggested structure', 'apply_preset', 'add these'], true)) {
            return self::APPLY;
        }
        if ($text === 'adjust') {
            return self::ADJUST;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function classNames(): array
    {
        return array_column($this->classes, 'name');
    }

    /**
     * @return list<string>
     */
    public function streamsFor(string $className): array
    {
        foreach ($this->classes as $class) {
            if (strcasecmp($class['name'], $className) === 0) {
                return $class['streams'];
            }
        }

        return [];
    }

    public function streamDraft(string $className): string
    {
        return implode(', ', $this->streamsFor($className));
    }

    /**
     * Classes and streams the preview would add. This does not write.
     *
     * @return list<array{name: string, streams: list<string>}>
     */
    public function standardsAnswerFor(School $school): array
    {
        return app(ClassStructureService::class)->suggestedStructurePlan($school)['classes'];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function existingStreamLabels(School $school): array
    {
        $year = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('status', 1)
            ->first()
            ?? AcademicYear::query()->where('school_id', $school->id)->first();

        if ($year === null) {
            return [];
        }

        $labels = [];
        foreach (app(ClassStructureService::class)->structureSnapshot($school, $year) as $row) {
            $name = strtolower(trim((string) ($row['name'] ?? '')));
            $streams = [];
            foreach ($row['streams'] ?? [] as $stream) {
                $label = trim((string) ($stream['label'] ?? ''));
                if ($label !== '') {
                    $streams[] = $label;
                }
            }
            $labels[$name] = $streams;
        }

        return $labels;
    }

    /**
     * One line for the schema default. Blank when this type has no suggested streams.
     */
    public function streamDefaultText(): string
    {
        $parts = [];
        foreach ($this->classes as $class) {
            if ($class['streams'] === []) {
                continue;
            }
            $parts[] = $class['name'].': '.implode(', ', $class['streams']);
        }

        return implode('; ', $parts);
    }

    /**
     * @return list<array{name: string, streams: list<string>}>
     */
    public function standardsAnswer(): array
    {
        return $this->classes;
    }

    /**
     * @return array<string, list<string>>
     */
    public function subjectsAnswer(): array
    {
        return $this->subjectsByClass;
    }

    /**
     * @return list<array{name: string, start: string, end: string}>
     */
    public function terms(CountryOnboardingProfile $profile, int $year): array
    {
        return $profile->defaultTerms($year);
    }

    public function suggestion(string $step, CountryOnboardingProfile $profile, int $year): string
    {
        $intro = "Here's the usual setup for {$this->article()} {$this->label} school.";

        return match ($step) {
            'standards' => $intro.' Classes: '.implode(', ', $this->classNames()).'. '.$this->streamSentence(),
            'subjects' => $intro.' Subjects: '.$this->subjectSentence(),
            'terms' => $intro.' Terms: '.$this->termSentence($profile, $year).' The current term is Term 1.',
            default => $intro,
        };
    }

    public static function suggestionYear(School $school): int
    {
        $saved = AcademicYear::query()
            ->where('school_id', $school->id)
            ->orderByDesc('id')
            ->first();

        return $saved ? $saved->calendarYear() : (int) now()->year;
    }

    private function article(): string
    {
        return preg_match('/^[aeiou]/i', $this->label) === 1 ? 'an' : 'a';
    }

    private function streamSentence(): string
    {
        $groups = $this->grouped(array_map(
            fn (array $class): array => ['name' => $class['name'], 'detail' => implode(', ', $class['streams'])],
            array_values(array_filter($this->classes, fn (array $class): bool => $class['streams'] !== [])),
        ));
        if ($groups === []) {
            return 'Suggested streams: none.';
        }

        $parts = [];
        foreach ($groups as $detail => $classes) {
            $parts[] = $this->span($classes).': '.$detail;
        }

        return 'Suggested streams: '.implode('; ', $parts).'.';
    }

    private function subjectSentence(): string
    {
        $rows = [];
        foreach ($this->subjectsByClass as $class => $names) {
            $rows[] = ['name' => $class, 'detail' => implode(', ', $names)];
        }
        $groups = $this->grouped($rows);
        if (count($groups) === 1) {
            return (string) array_key_first($groups);
        }

        $parts = [];
        foreach ($groups as $detail => $classes) {
            $parts[] = $this->span($classes).': '.$detail;
        }

        return implode('; ', $parts);
    }

    private function termSentence(CountryOnboardingProfile $profile, int $year): string
    {
        $bits = [];
        foreach ($this->terms($profile, $year) as $term) {
            $bits[] = $term['name'].' ('.$term['start'].' to '.$term['end'].')';
        }

        return implode(', ', $bits).'.';
    }

    /**
     * @param  list<array{name: string, detail: string}>  $rows
     * @return array<string, list<string>>
     */
    private function grouped(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['detail']][] = $row['name'];
        }

        return $groups;
    }

    /**
     * @param  list<string>  $classes
     */
    private function span(array $classes): string
    {
        if (count($classes) === 1) {
            return $classes[0];
        }

        return $classes[0].' to '.$classes[array_key_last($classes)];
    }
}
