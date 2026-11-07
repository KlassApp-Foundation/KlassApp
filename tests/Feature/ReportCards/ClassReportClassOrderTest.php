<?php

namespace Tests\Feature\ReportCards;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Services\ReportCards\ClassReportScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Report-card class list follows the level order, then the class name. */
class ClassReportClassOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_classes_sort_by_level_then_name_not_by_name_alone(): void
    {
        $school = School::create([
            'name' => 'Class Order School',
            'email' => 'class-order@example.test',
            'phone' => '+256700333210',
            'slug' => 'class-order',
            'status' => 1,
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $nursery = Standard::create([
            'school_id' => $school->id, 'name' => 'nursery', 'order' => 1, 'status' => 1,
        ]);
        $senior = Standard::create([
            'school_id' => $school->id, 'name' => 'senior', 'order' => 8, 'status' => 1,
        ]);
        $this->link($school, $year, $senior, 'Alpha Class');
        $this->link($school, $year, $nursery, 'Zebra Class');

        $labels = array_column(app(ClassReportScreen::class)->classes($school->id), 'label');

        $this->assertSame(['Zebra Class', 'Alpha Class'], $labels);
    }

    private function link(School $school, AcademicYear $year, Standard $standard, string $name): void
    {
        $section = Section::create([
            'school_id' => $school->id,
            'name' => $name,
            'status' => 1,
        ]);
        StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
    }
}
