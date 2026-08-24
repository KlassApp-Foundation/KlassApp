<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FeesCategories;
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

    // ── saveTerms ─────────────────────────────────────────────────────

    public function test_save_terms_creates_term_for_year(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveTerms($school, $year, [
            ['name' => 'Term 1', 'start' => '2025-02-03', 'end' => '2025-05-02'],
        ]);

        $term = AcademicTerm::where('school_id', $school->id)->first();
        $this->assertNotNull($term);
        $this->assertEquals('Term 1', $term->name);
        $this->assertEquals($year->id, $term->academic_year_id);
        $this->assertEquals('current', $term->status);
    }

    public function test_save_terms_persists_dates(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveTerms($school, $year, [
            ['name' => 'Term 1', 'start' => '2025-02-03', 'end' => '2025-05-02'],
        ]);

        $term = AcademicTerm::where('school_id', $school->id)->first();
        $this->assertEquals('2025-02-03', $term->starts_on->format('Y-m-d'));
        $this->assertEquals('2025-05-02', $term->ends_on->format('Y-m-d'));
    }

    public function test_save_terms_creates_multiple_terms(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveTerms($school, $year, [
            ['name' => 'Term 1', 'start' => '2025-02-03', 'end' => '2025-05-02'],
            ['name' => 'Term 2', 'start' => '2025-05-26', 'end' => '2025-08-29'],
            ['name' => 'Term 3', 'start' => '2025-09-22', 'end' => '2025-12-19'],
        ]);

        $this->assertEquals(3, AcademicTerm::where('school_id', $school->id)->count());
        $names = AcademicTerm::where('school_id', $school->id)
            ->pluck('name')->sort()->values()->toArray();
        $this->assertEquals(['Term 1', 'Term 2', 'Term 3'], $names);
    }

    public function test_save_terms_is_idempotent_firstOrCreate(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveTerms($school, $year, [
            ['name' => 'Term 1', 'start' => '2025-02-03', 'end' => '2025-05-02'],
        ]);

        // Call again — should not duplicate
        app(OnboardingEngine::class)->saveTerms($school, $year, [
            ['name' => 'Term 1', 'start' => '2025-02-03', 'end' => '2025-05-02'],
        ]);

        $this->assertEquals(1, AcademicTerm::where('school_id', $school->id)
            ->where('name', 'Term 1')->count());
    }

    public function test_save_terms_additive_adds_new_term_to_existing(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        // First call adds Term 1
        app(OnboardingEngine::class)->saveTerms($school, $year, [
            ['name' => 'Term 1', 'start' => '2025-02-03', 'end' => '2025-05-02'],
        ]);

        // Second call adds Term 2 alongside existing Term 1
        app(OnboardingEngine::class)->saveTerms($school, $year, [
            ['name' => 'Term 2', 'start' => '2025-05-26', 'end' => '2025-08-29'],
        ]);

        $this->assertEquals(2, AcademicTerm::where('school_id', $school->id)->count());
    }

    public function test_save_terms_rejects_empty_terms_array(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveTerms($school, $year, []);
            $this->fail('Expected ValidationException for empty terms array.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('terms', $e->errors());
        }
    }

    public function test_save_terms_rejects_empty_term_name(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveTerms($school, $year, [
                ['name' => '', 'start' => '2025-02-03', 'end' => '2025-05-02'],
            ]);
            $this->fail('Expected ValidationException for empty term name.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('terms', $e->errors());
        }
    }

    public function test_save_terms_rejects_end_before_start(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveTerms($school, $year, [
                ['name' => 'Term 1', 'start' => '2025-06-01', 'end' => '2025-02-01'],
            ]);
            $this->fail('Expected ValidationException for end before start.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('terms', $e->errors());
        }
    }

    public function test_save_terms_rejects_start_without_end(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveTerms($school, $year, [
                ['name' => 'Term 1', 'start' => '2025-02-03'],
            ]);
            $this->fail('Expected ValidationException for start without end.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('terms', $e->errors());
        }
    }

    public function test_save_terms_allows_terms_without_dates(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        // Terms without start/end dates are valid — dates can be filled in later
        app(OnboardingEngine::class)->saveTerms($school, $year, [
            ['name' => 'Term 1'],
        ]);

        $term = AcademicTerm::where('school_id', $school->id)->first();
        $this->assertNotNull($term);
        $this->assertEquals('Term 1', $term->name);
        $this->assertNull($term->starts_on);
        $this->assertNull($term->ends_on);
    }

    // ── saveFees ──────────────────────────────────────────────────────

    public function test_save_fees_creates_fee_with_defaults(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        // Need a Standard for fees to attach to
        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);

        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition'],
        ]);

        $fee = FeesCategories::where('school_id', $school->id)->first();
        $this->assertNotNull($fee);
        $this->assertEquals('Tuition', $fee->name);
        $this->assertEquals('0.00', number_format($fee->amount, 2, '.', ''));
        $this->assertNotNull($fee->standard_id);
        $this->assertNull($fee->section_id);
        $this->assertNull($fee->academic_term_id);
    }

    public function test_save_fees_persists_amount(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);

        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 500000],
        ]);

        $fee = FeesCategories::where('school_id', $school->id)->first();
        $this->assertEquals('500000.00', number_format($fee->amount, 2, '.', ''));
    }

    public function test_save_fees_resolves_class_to_standard_and_section(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);

        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 500000, 'class' => 'P.1'],
        ]);

        $fee = FeesCategories::where('school_id', $school->id)->first();
        $this->assertNotNull($fee->standard_id);
        $this->assertNotNull($fee->section_id);
    }

    public function test_save_fees_resolves_term(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);
        app(OnboardingEngine::class)->saveTerms($school, $year, [
            ['name' => 'Term 1'],
        ]);

        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 500000, 'class' => 'P.1', 'term' => 'Term 1'],
        ]);

        $fee = FeesCategories::where('school_id', $school->id)->first();
        $this->assertNotNull($fee->academic_term_id);

        $term = AcademicTerm::find($fee->academic_term_id);
        $this->assertEquals('Term 1', $term->name);
    }

    public function test_save_fees_is_idempotent_firstOrCreate(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);

        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 500000],
        ]);

        // Call again — should not duplicate
        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 500000],
        ]);

        $this->assertEquals(1, FeesCategories::where('school_id', $school->id)
            ->where('name', 'Tuition')
            ->count());
    }

    public function test_save_fees_additive_adds_new_fee_to_existing(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);

        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 500000],
        ]);

        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Lab Fee', 'amount' => 100000],
        ]);

        $this->assertEquals(2, FeesCategories::where('school_id', $school->id)->count());
    }

    public function test_save_fees_rejects_empty_fees_array(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveFees($school, []);
            $this->fail('Expected ValidationException for empty fees array.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fees', $e->errors());
        }
    }

    public function test_save_fees_rejects_empty_fee_name(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);

        try {
            app(OnboardingEngine::class)->saveFees($school, [
                ['name' => '', 'amount' => 500000],
            ]);
            $this->fail('Expected ValidationException for empty fee name.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fees', $e->errors());
        }
    }

    public function test_save_fees_rejects_negative_amount(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);

        try {
            app(OnboardingEngine::class)->saveFees($school, [
                ['name' => 'Tuition', 'amount' => -100],
            ]);
            $this->fail('Expected ValidationException for negative amount.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fees', $e->errors());
        }
    }

    public function test_save_fees_rejects_non_numeric_amount(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);

        try {
            app(OnboardingEngine::class)->saveFees($school, [
                ['name' => 'Tuition', 'amount' => 'abc'],
            ]);
            $this->fail('Expected ValidationException for non-numeric amount.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fees', $e->errors());
        }
    }

    public function test_save_fees_allows_zero_amount(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
        ]);

        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Free Activity', 'amount' => 0],
        ]);

        $fee = FeesCategories::where('school_id', $school->id)->first();
        $this->assertEquals('0.00', number_format($fee->amount, 2, '.', ''));
    }

    public function test_save_fees_rejects_without_existing_standard(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        try {
            app(OnboardingEngine::class)->saveFees($school, [
                ['name' => 'Tuition', 'amount' => 500000],
            ]);
            $this->fail('Expected ValidationException — no Standard exists.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fees', $e->errors());
        }
    }

    public function test_save_fees_general_fee_is_school_wide_not_scoped_to_first_class(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        // Create classes across two grading tiers: primary + o-level
        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
            ['name' => 'S.1'],
        ]);

        $standardCount = Standard::where('school_id', $school->id)->count();
        $this->assertEquals(2, $standardCount, 'Precondition: two Standards exist.');

        // Without a class, the fee must be school-wide — one row per Standard,
        // each with section_id = NULL (matches StudentReportHelperService::fees()
        // which reads school-wide fees as whereNull("section_id")).
        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'General Fund', 'amount' => 200000],
        ]);

        $fees = FeesCategories::where('school_id', $school->id)
            ->where('name', 'General Fund')
            ->get();

        $this->assertEquals($standardCount, $fees->count(), 'One row per Standard.');
        $this->assertEquals(2, $fees->count());

        foreach ($fees as $fee) {
            $this->assertNull($fee->section_id, 'School-wide fee must have section_id NULL.');
            $this->assertNotNull($fee->standard_id, 'standard_id is NOT NULL by schema.');
            $this->assertEquals(200000, (float) $fee->amount);
        }

        $scopedStandardIds = $fees->pluck('standard_id')->unique()->values();
        $allStandardIds = Standard::where('school_id', $school->id)->pluck('id')->sort()->values();
        $this->assertEquals(
            $allStandardIds->all(),
            $scopedStandardIds->sort()->values()->all(),
            'Fee covers every Standard, not just the first one.'
        );
    }

    public function test_save_fees_same_name_different_class_creates_distinct_rows(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
            ['name' => 'P.2'],
        ]);

        // Same fee name on different classes — unique constraint allows it
        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 500000, 'class' => 'P.1'],
        ]);
        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 600000, 'class' => 'P.2'],
        ]);

        $this->assertEquals(2, FeesCategories::where('school_id', $school->id)
            ->where('name', 'Tuition')
            ->count());
    }

    public function test_save_fees_with_class_creates_single_scoped_row_not_per_standard(): void
    {
        $school = $this->createSchool();
        $year = $this->createYear($school);

        // Two classes across different grading tiers
        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => 'P.1'],
            ['name' => 'S.1'],
        ]);

        $standardCount = Standard::where('school_id', $school->id)->count();
        $this->assertEquals(2, $standardCount, 'Precondition: two Standards.');

        // Class-specific fee for P.1 only
        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 500000, 'class' => 'P.1'],
        ]);

        $fees = FeesCategories::where('school_id', $school->id)
            ->where('name', 'Tuition')
            ->get();

        // Exactly one row — not duplicated across both Standards
        $this->assertCount(1, $fees);
        $this->assertNotNull($fees[0]->section_id, 'Class-specific fee has section_id set.');
        $this->assertEquals(500000, (float) $fees[0]->amount);
    }
}
