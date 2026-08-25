<?php

namespace Tests\Feature\Grading;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackfillKabaleGradingStyleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Kabale Junior School',
            'email' => 'kabale@test.sch.ug',
            'phone' => '+256700111222',
            'slug' => Str::random(10),
            'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);
    }

    private function makeStandard(string $name): Standard
    {
        return Standard::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'order' => match ($name) {
                'nursery' => 1,
                'primary_lower' => 2,
                'primary' => 3,
                'primary_upper' => 4,
                'o-level' => 5,
                'a-level' => 6,
                default => 7,
            },
            'status' => '1',
        ]);
    }

    private function makeLink(Standard $standard, string $sectionName): StandardLink
    {
        $section = Section::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => $sectionName],
            ['status' => '1']
        );

        return StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => '1',
        ]);
    }

    /** @test */
    public function dry_run_does_not_write_grading_style(): void
    {
        $nursery = $this->makeStandard('nursery');
        $this->assertNull($nursery->grading_style);

        $this->artisan('kabale:backfill-grading-style', [
            '--school' => $this->school->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertNull($nursery->fresh()->grading_style);
    }

    /** @test */
    public function backfills_nursery_standard_with_total_marks(): void
    {
        $nursery = $this->makeStandard('nursery');
        $this->assertNull($nursery->grading_style);

        $this->artisan('kabale:backfill-grading-style', [
            '--school' => $this->school->id,
        ])->assertSuccessful();

        $this->assertSame('total_marks', $nursery->fresh()->grading_style);
    }

    /** @test */
    public function backfills_primary_lower_with_total_marks(): void
    {
        $primaryLower = $this->makeStandard('primary_lower');
        $this->assertNull($primaryLower->grading_style);

        $this->artisan('kabale:backfill-grading-style', [
            '--school' => $this->school->id,
        ])->assertSuccessful();

        $this->assertSame('total_marks', $primaryLower->fresh()->grading_style);
    }

    /** @test */
    public function backfills_primary_upper_with_aggregate(): void
    {
        $primaryUpper = $this->makeStandard('primary_upper');
        $this->assertNull($primaryUpper->grading_style);

        $this->artisan('kabale:backfill-grading-style', [
            '--school' => $this->school->id,
        ])->assertSuccessful();

        $this->assertSame('aggregate', $primaryUpper->fresh()->grading_style);
    }

    /** @test */
    public function backfills_primary_with_aggregate(): void
    {
        $primary = $this->makeStandard('primary');
        $this->assertNull($primary->grading_style);

        $this->artisan('kabale:backfill-grading-style', [
            '--school' => $this->school->id,
        ])->assertSuccessful();

        $this->assertSame('aggregate', $primary->fresh()->grading_style);
    }

    /** @test */
    public function backfills_sub_group_on_primary_lower_links(): void
    {
        $primaryLower = $this->makeStandard('primary_lower');
        $link = $this->makeLink($primaryLower, 'P.1');
        $this->assertNull($link->sub_group);

        $this->artisan('kabale:backfill-grading-style', [
            '--school' => $this->school->id,
        ])->assertSuccessful();

        $this->assertSame('lower', $link->fresh()->sub_group);
    }

    /** @test */
    public function backfills_sub_group_on_primary_upper_links(): void
    {
        $primaryUpper = $this->makeStandard('primary_upper');
        $link = $this->makeLink($primaryUpper, 'P.4');
        $this->assertNull($link->sub_group);

        $this->artisan('kabale:backfill-grading-style', [
            '--school' => $this->school->id,
        ])->assertSuccessful();

        $this->assertSame('upper', $link->fresh()->sub_group);
    }

    /** @test */
    public function does_not_set_sub_group_on_nursery_links(): void
    {
        $nursery = $this->makeStandard('nursery');
        $link = $this->makeLink($nursery, 'Baby Class');
        $this->assertNull($link->sub_group);

        $this->artisan('kabale:backfill-grading-style', [
            '--school' => $this->school->id,
        ])->assertSuccessful();

        // Nursery links get no sub_group
        $this->assertNull($link->fresh()->sub_group);
    }

    /** @test */
    public function idempotent_rerunning_does_not_change_already_set_values(): void
    {
        $primaryLower = $this->makeStandard('primary_lower');
        $link = $this->makeLink($primaryLower, 'P.2');

        // First run
        $this->artisan('kabale:backfill-grading-style', [
            '--school' => $this->school->id,
        ])->assertSuccessful();

        $this->assertSame('total_marks', $primaryLower->fresh()->grading_style);
        $this->assertSame('lower', $link->fresh()->sub_group);

        // Second run — should not fail, values remain the same
        $this->artisan('kabale:backfill-grading-style', [
            '--school' => $this->school->id,
        ])->assertSuccessful();

        $this->assertSame('total_marks', $primaryLower->fresh()->grading_style);
        $this->assertSame('lower', $link->fresh()->sub_group);
    }

    /** @test */
    public function fails_gracefully_for_nonexistent_school(): void
    {
        $this->artisan('kabale:backfill-grading-style', [
            '--school' => 99999,
        ])->assertFailed();
    }
}
