<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Services\OnboardingEngine;
use App\Services\SchoolCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentStepsTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────

    private function createSchool(array $overrides = []): School
    {
        return School::create(array_merge([
            'name' => 'Test School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ], $overrides));
    }

    private function createYear(School $school): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);
    }

    // ── saveStandards ─────────────────────────────────────────────────

    public function test_save_standards_creates_sections_and_links_for_year(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P1'],
            ['name' => 'P2'],
            ['name' => 'P3'],
        ]);

        $this->assertEquals(3, Section::where('school_id', $school->id)->count());
        $this->assertEquals(3, StandardLink::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)->count());

        // Each section name should match
        $sectionNames = Section::where('school_id', $school->id)
            ->pluck('name')->sort()->values()->toArray();
        $this->assertEquals(['P1', 'P2', 'P3'], $sectionNames);
    }

    public function test_save_standards_with_streams_creates_multiple_sections_per_class(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P1', 'streams' => ['A', 'B']],
            ['name' => 'P2'],
        ]);

        // P1 has 2 stream sections, P2 has 1 section = 3 total
        $this->assertEquals(3, Section::where('school_id', $school->id)->count());
        $this->assertEquals(3, StandardLink::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)->count());

        $sectionNames = Section::where('school_id', $school->id)
            ->pluck('name')->sort()->values()->toArray();
        $this->assertTrue(in_array('P1 A', $sectionNames) || in_array('P1A', $sectionNames)
            || in_array('P1 - A', $sectionNames),
            'Stream section name should combine class and stream');
    }

    public function test_save_standards_is_idempotent_firstOrCreate(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P1'],
        ]);

        // Call again — should not duplicate
        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P1'],
        ]);

        $this->assertEquals(1, Section::where('school_id', $school->id)->where('name', 'P1')->count());
        $this->assertEquals(1, StandardLink::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)->count());
    }

    public function test_save_standards_rejects_empty_classes_array(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveStandards($school, $year, []);
            $this->fail('Expected ValidationException for empty classes array.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('classes', $e->errors());
        }
    }

    public function test_save_standards_rejects_duplicate_class_names(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveStandards($school, $year, [
                ['name' => 'P1'],
                ['name' => 'P1'],
            ]);
            $this->fail('Expected ValidationException for duplicate class names.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('classes', $e->errors());
        }
    }

    public function test_save_standards_rejects_empty_class_name(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveStandards($school, $year, [
                ['name' => ''],
            ]);
            $this->fail('Expected ValidationException for empty class name.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('classes', $e->errors());
        }
    }

    public function test_save_standards_creates_standard_from_category_when_set(): void
    {
        $school = $this->createSchool(['school_category' => 'primary']);
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'Extra Class'],
        ]);

        // Should have a 'primary' standard from category, or a default standard
        $this->assertTrue(
            Standard::where('school_id', $school->id)->exists(),
            'At least one standard (grading tier) should exist for the school.'
        );

        // The link should connect the section to a standard and the year
        $link = StandardLink::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->first();
        $this->assertNotNull($link);
        $this->assertNotNull($link->standard_id);
        $this->assertNotNull($link->section_id);
    }

    public function test_save_standards_creates_default_standard_when_no_category(): void
    {
        $school = $this->createSchool(); // no school_category
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P1'],
        ]);

        // Should create a 'primary' standard as default
        $standard = Standard::where('school_id', $school->id)->first();
        $this->assertNotNull($standard);
        $this->assertEquals('primary', $standard->name);
    }

    public function test_save_standards_with_nursery_names_creates_nursery_standard(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'Baby Class'],
        ]);

        $standard = Standard::where('school_id', $school->id)->first();
        $this->assertNotNull($standard);
        // Baby Class → nursery standard
        $this->assertEquals('nursery', $standard->name);
    }

    public function test_save_standards_with_senior_names_creates_o_level_standard(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'Senior One'],
        ]);

        $standard = Standard::where('school_id', $school->id)->first();
        $this->assertNotNull($standard);
        $this->assertEquals('o-level', $standard->name);
    }

    // ── saveSubjects ───────────────────────────────────────────────────

    /** Helper: set up standards + links so saveSubjects has something to attach to. */
    private function seedStandards(School $school, AcademicYear $year, array $classes = [['name' => 'P1']]): void
    {
        app(OnboardingEngine::class)->saveStandards($school, $year, $classes);
    }

    public function test_save_subjects_creates_subjects_for_class(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);
        $this->seedStandards($school, $year, [['name' => 'P1']]);

        app(OnboardingEngine::class)->saveSubjects($school, $year, [
            'P1' => ['Mathematics', 'English Language'],
        ]);

        $this->assertEquals(2, Subject::where('school_id', $school->id)->count());
        // Subject model's getNameAttribute uppercases — compare raw DB values
        $subjectNames = \DB::table('subjects')
            ->where('school_id', $school->id)
            ->pluck('name')->sort()->values()->toArray();
        $this->assertEquals(['English Language', 'Mathematics'], $subjectNames);
    }

    public function test_save_subjects_is_idempotent_firstOrCreate(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);
        $this->seedStandards($school, $year, [['name' => 'P1']]);

        app(OnboardingEngine::class)->saveSubjects($school, $year, [
            'P1' => ['Mathematics'],
        ]);

        // Call again — should not duplicate
        app(OnboardingEngine::class)->saveSubjects($school, $year, [
            'P1' => ['Mathematics'],
        ]);

        $this->assertEquals(1, Subject::where('school_id', $school->id)
            ->where('name', 'Mathematics')->count());
    }

    public function test_save_subjects_creates_subjects_per_stream_section(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);
        $this->seedStandards($school, $year, [
            ['name' => 'P1', 'streams' => ['A', 'B']],
        ]);

        app(OnboardingEngine::class)->saveSubjects($school, $year, [
            'P1' => ['Mathematics'],
        ]);

        // With 2 streams, Mathematics should be created for both sections
        $mathSubjects = \DB::table('subjects')
            ->where('school_id', $school->id)
            ->where('name', 'Mathematics')
            ->count();
        $this->assertEquals(2, $mathSubjects);
    }

    public function test_save_subjects_rejects_empty_subjects_array(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveSubjects($school, $year, []);
            $this->fail('Expected ValidationException for empty subjects array.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('subjects', $e->errors());
        }
    }

    public function test_save_subjects_rejects_empty_class_key(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveSubjects($school, $year, [
                '' => ['Mathematics'],
            ]);
            $this->fail('Expected ValidationException for empty class key.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('subjects', $e->errors());
        }
    }

    public function test_save_subjects_rejects_empty_subject_name(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);
        $this->seedStandards($school, $year, [['name' => 'P1']]);

        try {
            app(OnboardingEngine::class)->saveSubjects($school, $year, [
                'P1' => [''],
            ]);
            $this->fail('Expected ValidationException for empty subject name.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('subjects', $e->errors());
        }
    }

    public function test_save_subjects_rejects_class_without_existing_link(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);
        // No standards seeded — no StandardLink exists for 'P1'

        try {
            app(OnboardingEngine::class)->saveSubjects($school, $year, [
                'P1' => ['Mathematics'],
            ]);
            $this->fail('Expected ValidationException for class without StandardLink.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('subjects', $e->errors());
        }
    }

    public function test_save_subjects_with_category_seeder_baseline_does_not_duplicate(): void
    {
        $school = $this->createSchool(['school_category' => 'primary']);
        $year = $this->createYear($school);
        // Category seeder creates "Primary One" etc. sections with core subjects (Mathematics, English)
        // saveStandards creates an additional "P1" section alongside the seeder's "Primary One"
        $this->seedStandards($school, $year, [['name' => 'P1']]);

        // Adding Mathematics to "P1" — the seeder already created it on "Primary One" section,
        // so this should create only 1 additional row (on the "P1" section), not duplicate
        app(OnboardingEngine::class)->saveSubjects($school, $year, [
            'P1' => ['Mathematics'],
        ]);

        // Mathematics on the P1 section specifically should be exactly 1
        $p1Section = Section::where('school_id', $school->id)->where('name', 'P1')->first();
        $this->assertNotNull($p1Section);
        $mathOnP1 = \DB::table('subjects')
            ->where('school_id', $school->id)
            ->where('section_id', $p1Section->id)
            ->where('name', 'Mathematics')
            ->count();
        $this->assertEquals(1, $mathOnP1, 'firstOrCreate should not duplicate on same section');
    }

    public function test_save_subjects_sets_core_type_by_default(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);
        $this->seedStandards($school, $year, [['name' => 'P1']]);

        app(OnboardingEngine::class)->saveSubjects($school, $year, [
            'P1' => ['Mathematics'],
        ]);

        // Subject model accessor uppercases; query raw DB
        $subjectRow = \DB::table('subjects')
            ->where('school_id', $school->id)
            ->where('name', 'Mathematics')
            ->first();
        $this->assertNotNull($subjectRow);
        $this->assertEquals('core', $subjectRow->type);
    }
}
