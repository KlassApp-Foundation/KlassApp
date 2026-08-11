<?php

namespace App\Services;

use App\Helpers\GradingHelper;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;

/**
 * Smart-default seeder for the onboarding wizard's "School category" step.
 *
 * The category the school owner picks on the wizard drives one default set of
 * classes, subjects, and grading scale per standard — mirroring what the admin
 * panel does for the legacy setup. Everything it creates is fully editable
 * afterwards (the wizard's own class/subject steps early-return once these rows
 * exist), so this is a convenience default, never a lock.
 *
 * Only UNEB-curriculum schools reach the school_category step; a school with an
 * empty or unknown category simply gets nothing seeded and falls back to the
 * manual class/subject steps.
 */
class SchoolCategorySeeder
{
    /** Internal value -> human label shown on the wizard step. */
    public const CATEGORIES = [
        'nursery' => 'Nursery only',
        'primary' => 'Primary',
        'primary_nursery' => 'Primary + Nursery',
        'o_level' => 'O-Level',
        'o_a_level' => 'O-Level + A-Level',
    ];

    /**
     * Category -> level types to seed. Standard names must stay in sync with
     * GradingHelper::levelTypeForStandard() phaseMap (nursery/primary/o-level/a-level).
     */
    private const CATEGORY_STANDARDS = [
        'nursery' => ['nursery'],
        'primary' => ['primary'],
        'primary_nursery' => ['nursery', 'primary'],
        'o_level' => ['o-level'],
        'o_a_level' => ['o-level', 'a-level'],
    ];

    /** Sections per level type (K12 Uganda naming; matches AcademicSetupService). */
    private const SECTIONS = [
        'nursery' => ['Baby Class', 'Middle Class', 'Top Class'],
        'primary' => [
            'Primary One', 'Primary Two', 'Primary Three', 'Primary Four',
            'Primary Five', 'Primary Six', 'Primary Seven',
        ],
        'o-level' => ['Senior One', 'Senior Two', 'Senior Three', 'Senior Four'],
        'a-level' => ['Senior Five', 'Senior Six'],
    ];

    /**
     * Subjects per level type, keyed by UNEB subject code where one can be
     * assigned unambiguously (NCDC curricula; see cited UNEB/NCDC docs in the
     * PR). Codes with known collisions are left null rather than guessed:
     * - UACE 220 is shared by Geography and Economics;
     * - UACE 840 is shared by ICT, General Mathematics and Entrepreneurship.
     * Nursery classes carry no subjects (that is the prevailing app behaviour).
     */
    private const SUBJECTS = [
        'nursery' => [],
        'primary' => [
            'English Language' => '013',
            'Mathematics' => '007',
            'Social Studies' => '015',
            'Science' => '010',
        ],
        'o-level' => [
            'English Language' => '112',
            'General Mathematics' => '456',
            'Biology' => '535',
            'Chemistry' => '534',
            'Physics' => '545',
            'Geography' => '273',
            'History and Political Education' => '241',
        ],
        'a-level' => [
            'General Paper' => '800',
            'Biology' => '530',
            'Chemistry' => '540',
            'Physics' => '550',
            'History and Political Education' => '210',
            'Literature in English' => '230',
            'ICT' => null,
            'General Mathematics' => null,
            'Geography' => null,
            'Economics' => null,
            'Entrepreneurship Education' => null,
        ],
    ];

    /**
     * Seed default standards, sections, subjects, and grading for one school.
     *
     * Idempotent: returns silently when the school has no recognised category
     * or when it already has any class link (safe for repeat wizard saves and
     * for schools set up through the legacy/Toshi paths).
     *
     * Requires an academic year to exist — the wizard calls this from
     * saveAcademicYear() right after the year row is created.
     */
    public static function seed(School $school): void
    {
        $category = $school->school_category;

        if (! is_string($category) || ! array_key_exists($category, self::CATEGORIES)) {
            return;
        }

        // Never double-seed a school that already got classes.
        if (StandardLink::where('school_id', $school->id)->exists()) {
            return;
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            return;
        }

        foreach (self::CATEGORY_STANDARDS[$category] as $levelType) {
            $standard = Standard::firstOrCreate(
                ['school_id' => $school->id, 'name' => $levelType],
                ['order' => self::ORDER_FOR_LEVEL[$levelType], 'status' => '1']
            );

            $firstSection = null;

            foreach (self::SECTIONS[$levelType] as $sectionName) {
                $section = Section::firstOrCreate(
                    ['school_id' => $school->id, 'name' => $sectionName],
                    ['status' => '1']
                );

                $firstSection ??= $section;

                StandardLink::firstOrCreate([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standard_id' => $standard->id,
                    'section_id' => $section->id,
                    'status' => '1',
                ]);
            }

            foreach (self::SUBJECTS[$levelType] as $name => $code) {
                Subject::firstOrCreate([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standard_id' => $standard->id,
                    'section_id' => $firstSection?->id,
                    'name' => $name,
                ], [
                    'code' => $code,
                    'type' => 'core',
                    'status' => 1,
                ]);
            }

            // Each standard gets its own default grading scale.
            GradingHelper::seedGradingForSchool($school->id, $levelType);
        }
    }

    private const ORDER_FOR_LEVEL = [
        'nursery' => 1,
        'primary' => 2,
        'o-level' => 3,
        'a-level' => 4,
    ];
}