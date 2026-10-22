<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\FeesCategories;
use App\Models\School;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FeeSameNameAcrossTermsTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_fee_name_is_kept_across_three_terms(): void
    {
        [$school, $year] = $this->schoolWithClass('P3');
        $this->terms($school, $year);

        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 250000, 'class' => 'P3', 'term' => 'Term 1', 'row' => 2],
            ['name' => 'Tuition', 'amount' => 250000, 'class' => 'P3', 'term' => 'Term 2', 'row' => 3],
            ['name' => 'Tuition', 'amount' => 250000, 'class' => 'P3', 'term' => 'Term 3', 'row' => 4],
        ]);

        $fees = FeesCategories::with('term')
            ->where('school_id', $school->id)
            ->where('name', 'Tuition')
            ->get();

        $this->assertCount(3, $fees);
        $this->assertSame(
            ['Term 1', 'Term 2', 'Term 3'],
            $fees->map(fn (FeesCategories $fee) => $fee->term?->name)->sort()->values()->all()
        );
        $this->assertCount(1, $fees->pluck('section_id')->unique());

        app(OnboardingEngine::class)->saveFees($school, [
            ['name' => 'Tuition', 'amount' => 250000, 'class' => 'P3', 'term' => 'Term 1'],
            ['name' => 'Tuition', 'amount' => 250000, 'class' => 'P3', 'term' => 'Term 2'],
            ['name' => 'Tuition', 'amount' => 250000, 'class' => 'P3', 'term' => 'Term 3'],
        ]);

        $this->assertSame(3, FeesCategories::where('school_id', $school->id)->where('name', 'Tuition')->count());
    }

    public function test_true_duplicate_fee_row_is_rejected_with_cause_and_fix(): void
    {
        [$school, $year] = $this->schoolWithClass('P3');
        $this->terms($school, $year);

        try {
            app(OnboardingEngine::class)->saveFees($school, [
                ['name' => 'Tuition', 'amount' => 250000, 'class' => 'P3', 'term' => 'Term 1', 'row' => 2],
                ['name' => 'Tuition', 'amount' => 260000, 'class' => 'P3', 'term' => 'Term 1', 'row' => 12],
            ]);
            $this->fail('A fee listed twice for the same class and term must be rejected.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Row 12: "Tuition" for P3 is already listed for Term 1. Remove the duplicate row or change the term.',
                collect($e->errors())->flatten()->first()
            );
        }

        $this->assertSame(0, FeesCategories::where('school_id', $school->id)->where('name', 'Tuition')->count());
    }

    public function test_fee_unique_index_includes_term_and_drops_name_only_index(): void
    {
        $unique = collect(Schema::getIndexes('fees_categories'))
            ->filter(fn (array $index) => ($index['unique'] ?? false) && ! ($index['primary'] ?? false))
            ->map(fn (array $index) => array_map(
                static fn ($column): string => strtolower((string) $column),
                $index['columns'] ?? []
            ))
            ->values();

        $this->assertTrue(
            $unique->contains(fn (array $columns) => $columns === ['school_id', 'standard_id', 'section_id', 'academic_term_id', 'name']),
            'fees_categories uniqueness must include academic_term_id'
        );
        $this->assertFalse(
            $unique->contains(fn (array $columns) => $columns === ['school_id', 'standard_id', 'section_id', 'name']),
            'the name-only fees_categories unique index must be dropped after the term index exists'
        );
    }

    /**
     * @return array{0: School, 1: AcademicYear}
     */
    private function schoolWithClass(string $className): array
    {
        $school = School::create([
            'name' => 'Fee Term School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2027',
            'description' => 'Current Academic Year',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 1,
        ]);

        app(OnboardingEngine::class)->saveStandards($school, $year, [
            ['name' => $className],
        ]);

        return [$school, $year];
    }

    private function terms(School $school, AcademicYear $year): void
    {
        app(OnboardingEngine::class)->saveTerms($school, $year, [
            ['name' => 'Term 1', 'status' => 'current'],
            ['name' => 'Term 2', 'status' => 'next'],
            ['name' => 'Term 3', 'status' => 'next'],
        ]);
    }
}
