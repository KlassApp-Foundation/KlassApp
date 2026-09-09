<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hardcoded First/Second/Third → 1/2/3 maps assumed every school has 3 UNEB-style
 * terms. Schools may configure 2 (or any N) — ordinals must come from real rows.
 */
class AcademicTermPositionLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_term_year_shows_ordinal_of_configured_count_not_hardcoded_three(): void
    {
        DB::table('schools')->insert([
            'id' => 1,
            'name' => 'Two Term School',
            'slug' => 'two-term-school',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $year = AcademicYear::create([
            'school_id' => 1,
            'name' => '2026',
            'description' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $first = AcademicTerm::create([
            'school_id' => 1,
            'academic_year_id' => $year->id,
            'name' => 'Semester A',
            'starts_on' => '2026-01-15',
            'ends_on' => '2026-05-15',
            'status' => 'current',
        ]);

        $second = AcademicTerm::create([
            'school_id' => 1,
            'academic_year_id' => $year->id,
            'name' => 'Semester B',
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-11-30',
            'status' => 'next',
        ]);

        $meta = AcademicTerm::ordinalsForYear(1, $year->id);

        $this->assertSame(2, $meta['count']);
        $this->assertSame(1, $meta['ordinals'][$first->id]);
        $this->assertSame(2, $meta['ordinals'][$second->id]);
        $this->assertSame('1 of 2', $first->positionLabel());
        $this->assertSame('2 of 2', $second->positionLabel());

        // Blade views no longer hardcode a Third Term → 3 map.
        $adminBlade = file_get_contents(resource_path('views/admin/marks/student.blade.php'));
        $teacherBlade = file_get_contents(resource_path('views/teacher/marks/teacher-exam-list.blade.php'));

        $this->assertStringNotContainsString('Third Term', $adminBlade);
        $this->assertStringNotContainsString('Third Term', $teacherBlade);
        $this->assertStringContainsString('positionLabel()', $adminBlade);
        $this->assertStringContainsString('positionLabel()', $teacherBlade);
    }

    public function test_three_term_year_still_numbers_sequentially(): void
    {
        School::create([
            'name' => 'Three Term School',
            'email' => 'three.term@test.sch.ug',
            'slug' => 'three-term-school',
            'status' => 1,
        ]);
        $schoolId = School::where('slug', 'three-term-school')->value('id');

        $year = AcademicYear::create([
            'school_id' => $schoolId,
            'name' => '2026',
            'description' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        foreach ([
            ['First Term', '2026-02-01', 'last'],
            ['Second Term', '2026-05-01', 'current'],
            ['Third Term', '2026-09-01', 'next'],
        ] as [$name, $start, $status]) {
            AcademicTerm::create([
                'school_id' => $schoolId,
                'academic_year_id' => $year->id,
                'name' => $name,
                'starts_on' => $start,
                'ends_on' => date('Y-m-d', strtotime($start.' +80 days')),
                'status' => $status,
            ]);
        }

        $third = AcademicTerm::where('school_id', $schoolId)->where('name', 'Third Term')->first();
        $this->assertSame('3 of 3', $third->positionLabel());
    }
}
