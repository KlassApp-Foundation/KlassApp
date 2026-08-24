<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StandardLink;
use App\Services\OnboardingEngine;
use App\Services\SchoolCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SchoolCategoryStepTest extends TestCase
{
    use RefreshDatabase;

    private function freshSchoolWithYear(): School
    {
        $school = School::create([
            'name' => "Test School",
            'email' => 'test@example.com',
            'phone' => '+256701111111',
            'slug' => 'test-school',
            'status' => 1,
            'toshi_enabled' => 1,
            'curriculum' => 'uneb',
        ]);

        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        return $school;
    }

    /**
     * @test
     * @dataProvider validCategoryProvider
     */
    public function it_persists_valid_category_and_seeds_defaults(string $category, int $expectedLinks): void
    {
        $school = $this->freshSchoolWithYear();

        app(OnboardingEngine::class)->saveSchoolCategory($school, $category);

        $school->refresh();
        $this->assertSame($category, $school->school_category);
        $this->assertSame($expectedLinks, StandardLink::where('school_id', $school->id)->count());
    }

    public static function validCategoryProvider(): array
    {
        return [
            'nursery' => ['nursery', 3],
            'primary' => ['primary', 7],
            'primary_nursery' => ['primary_nursery', 10],
            'o_level' => ['o_level', 4],
            'o_a_level' => ['o_a_level', 6],
        ];
    }

    /** @test */
    public function it_rejects_unknown_category(): void
    {
        $school = $this->freshSchoolWithYear();

        $this->expectException(ValidationException::class);

        app(OnboardingEngine::class)->saveSchoolCategory($school, 'hogwarts');
    }

    /** @test */
    public function it_rejects_empty_category(): void
    {
        $school = $this->freshSchoolWithYear();

        $this->expectException(ValidationException::class);

        app(OnboardingEngine::class)->saveSchoolCategory($school, '  ');
    }

    /** @test */
    public function it_is_idempotent_across_repeated_calls(): void
    {
        $school = $this->freshSchoolWithYear();

        app(OnboardingEngine::class)->saveSchoolCategory($school, 'primary');
        $firstCount = StandardLink::where('school_id', $school->id)->count();

        app(OnboardingEngine::class)->saveSchoolCategory($school, 'primary');
        $secondCount = StandardLink::where('school_id', $school->id)->count();

        $this->assertSame($firstCount, $secondCount);
        $this->assertSame(7, $secondCount);
    }
}
