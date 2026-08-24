<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Services\OnboardingEngine;
use App\Services\SchoolCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
