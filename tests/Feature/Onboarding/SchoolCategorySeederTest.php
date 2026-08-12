<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Services\SchoolCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolCategorySeederTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Seeder Test School',
            'email' => 'seeder@test.sch.ug',
            'phone' => '0700000011',
            'slug' => 'seeder-test-school',
            'status' => 1,
            'curriculum' => 'uneb',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'Current Academic Year',
        ]);
    }

    /** @test */
    public function primary_category_seeds_classes_subjects_and_primary_grading(): void
    {
        $this->school->school_category = 'primary';
        $this->school->save();

        SchoolCategorySeeder::seed($this->school->fresh());

        // One phase standard + seven linked Primary One..Seven sections.
        $this->assertSame(1, Standard::where('school_id', $this->school->id)->count());
        $this->assertSame(7, Section::where('school_id', $this->school->id)->count());
        $this->assertSame(7, StandardLink::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Primary One',
        ]);
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Primary Seven',
        ]);

        $this->assertSame(4, Subject::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('subjects', [
            'school_id' => $this->school->id,
            'standard_id' => Standard::where('school_id', $this->school->id)->value('id'),
            'name' => 'English Language',
            'code' => '013',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->assertSame(9, SchoolGradingSystem::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('school_grading_systems', [
            'school_id' => $this->school->id,
            'grade' => '1',
            'min_score' => 95,
            'max_score' => 100,
        ]);
        $this->assertDatabaseHas('school_grading_systems', [
            'school_id' => $this->school->id,
            'grade' => '9',
            'min_score' => 0,
            'max_score' => 39,
        ]);
    }

    /** @test */
    public function nursery_category_seeds_sections_and_grading_but_no_subjects(): void
    {
        $this->school->school_category = 'nursery';
        $this->school->save();

        SchoolCategorySeeder::seed($this->school->fresh());

        $this->assertSame(1, Standard::where('school_id', $this->school->id)->count());
        $this->assertSame(3, Section::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Baby Class',
        ]);
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Top Class',
        ]);
        $this->assertSame(0, Subject::where('school_id', $this->school->id)->count());
        $this->assertSame(4, SchoolGradingSystem::where('school_id', $this->school->id)->count());
    }

    /** @test */
    public function primary_nursery_category_seeds_both_levels(): void
    {
        $this->school->school_category = 'primary_nursery';
        $this->school->save();

        SchoolCategorySeeder::seed($this->school->fresh());

        $this->assertSame(2, Standard::where('school_id', $this->school->id)->count());
        $this->assertSame(10, Section::where('school_id', $this->school->id)->count());
        $this->assertSame(10, StandardLink::where('school_id', $this->school->id)->count());
        $this->assertSame(4, Subject::where('school_id', $this->school->id)->count());
        // Nursery (4) + primary (9) grading rows.
        $this->assertSame(13, SchoolGradingSystem::where('school_id', $this->school->id)->count());
    }

    /** @test */
    public function o_level_category_seeds_senior_one_to_four_and_letter_grading(): void
    {
        $this->school->school_category = 'o_level';
        $this->school->save();

        SchoolCategorySeeder::seed($this->school->fresh());

        $this->assertSame(1, Standard::where('school_id', $this->school->id)->count());
        $this->assertSame(4, Section::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Senior One',
        ]);
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Senior Four',
        ]);
        $this->assertSame(7, Subject::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('subjects', [
            'school_id' => $this->school->id,
            'name' => 'Chemistry',
            'code' => '534',
            'type' => 'core',
        ]);

        // Five-band A-E grading.
        $this->assertSame(5, SchoolGradingSystem::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('school_grading_systems', [
            'school_id' => $this->school->id,
            'grade' => 'A',
            'min_score' => 80,
            'max_score' => 100,
        ]);
        $this->assertDatabaseHas('school_grading_systems', [
            'school_id' => $this->school->id,
            'grade' => 'E',
            'min_score' => 0,
            'max_score' => 49,
        ]);
    }

    /** @test */
    public function o_a_level_category_seeds_senior_five_and_six_with_uace_points(): void
    {
        $this->school->school_category = 'o_a_level';
        $this->school->save();

        SchoolCategorySeeder::seed($this->school->fresh());

        $this->assertSame(2, Standard::where('school_id', $this->school->id)->count());
        $this->assertSame(6, Section::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Senior Five',
        ]);
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id,
            'name' => 'Senior Six',
        ]);
        // 7 o-level + 11 a-level subjects.
        $this->assertSame(18, Subject::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('subjects', [
            'school_id' => $this->school->id,
            'name' => 'General Paper',
            'code' => '800',
        ]);

        // 5 o-level + 7 a-level grading rows; UACE points on A-level.
        $this->assertSame(12, SchoolGradingSystem::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('school_grading_systems', [
            'school_id' => $this->school->id,
            'grade' => 'A',
            'points' => 6,
        ]);
        $this->assertDatabaseHas('school_grading_systems', [
            'school_id' => $this->school->id,
            'grade' => 'F',
            'points' => 0,
        ]);
    }

    /** @test */
    public function seed_is_idempotent_and_never_duplicates_rows(): void
    {
        $this->school->school_category = 'primary';
        $this->school->save();

        SchoolCategorySeeder::seed($this->school->fresh());
        SchoolCategorySeeder::seed($this->school->fresh());
        SchoolCategorySeeder::seed($this->school->fresh());

        $this->assertSame(1, Standard::where('school_id', $this->school->id)->count());
        $this->assertSame(7, Section::where('school_id', $this->school->id)->count());
        $this->assertSame(7, StandardLink::where('school_id', $this->school->id)->count());
        $this->assertSame(4, Subject::where('school_id', $this->school->id)->count());
        $this->assertSame(9, SchoolGradingSystem::where('school_id', $this->school->id)->count());
    }

    /** @test */
    public function seed_is_noop_when_school_has_no_category_or_unknown_category(): void
    {
        SchoolCategorySeeder::seed($this->school->fresh());
        $this->assertSame(0, StandardLink::where('school_id', $this->school->id)->count());
        $this->assertSame(0, Subject::where('school_id', $this->school->id)->count());

        $this->school->school_category = 'bogus-category';
        $this->school->save();

        SchoolCategorySeeder::seed($this->school->fresh());
        $this->assertSame(0, StandardLink::where('school_id', $this->school->id)->count());
        $this->assertSame(0, SchoolGradingSystem::where('school_id', $this->school->id)->count());
    }

    /** @test */
    public function seed_is_noop_without_an_academic_year(): void
    {
        $this->school->school_category = 'primary';
        $this->school->save();
        $this->year->delete();

        SchoolCategorySeeder::seed($this->school->fresh());

        $this->assertSame(0, StandardLink::where('school_id', $this->school->id)->count());
        $this->assertSame(0, Subject::where('school_id', $this->school->id)->count());
        $this->assertSame(0, SchoolGradingSystem::where('school_id', $this->school->id)->count());
    }

    /** @test */
    public function seed_never_clobbers_existing_manual_classes(): void
    {
        // A school that already built its own classes (e.g. via the manual
        // standards step or bulk upload) must not be re-seeded or modified.
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => '1',
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary One',
            'status' => '1',
        ]);
        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => '1',
        ]);

        $this->school->school_category = 'primary';
        $this->school->save();

        SchoolCategorySeeder::seed($this->school->fresh());

        $this->assertSame(1, StandardLink::where('school_id', $this->school->id)->count());
        $this->assertSame(1, Section::where('school_id', $this->school->id)->count());
        $this->assertSame(0, Subject::where('school_id', $this->school->id)->count());
        $this->assertSame(0, SchoolGradingSystem::where('school_id', $this->school->id)->count());
    }
}