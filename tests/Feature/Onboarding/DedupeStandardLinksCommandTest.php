<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DedupeStandardLinksCommandTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private Standard $standard;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'Dedup Test School', 'email' => 'dedup@test.sch.ug']);

        $ay = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->standard = Standard::create(['school_id' => $this->school->id, 'name' => 'Primary', 'order' => 1]);
        $this->section = Section::create(['school_id' => $this->school->id, 'name' => 'P1']);
    }

    public function test_dry_run_reports_duplicates_without_writing()
    {
        // Create duplicate standards_link rows using raw insert
        // Note: stream IS NULL, and SQLite/MySQL UNIQUE indexes allow
        // multiple NULLs, so this bypasses the unique constraint.
        DB::table('standards_link')->insert([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => 1,
            'status' => '1',
        ]);
        DB::table('standards_link')->insert([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => 1,
            'status' => '1',
        ]);
        DB::table('standards_link')->insert([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => 1,
            'status' => '1',
        ]);

        $exitCode = Artisan::call('standards-link:dedupe', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('3 rows', $output);
        $this->assertStringContainsString('Would delete', $output);

        // No rows deleted
        $this->assertSame(3, DB::table('standards_link')->where('school_id', $this->school->id)->count());
    }

    public function test_execute_removes_duplicates_keeps_survivor()
    {
        DB::table('standards_link')->insert([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => 1,
            'status' => '1',
        ]);
        DB::table('standards_link')->insert([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => 1,
            'status' => '1',
        ]);

        Artisan::call('standards-link:dedupe');

        $rows = DB::table('standards_link')
            ->where('school_id', $this->school->id)
            ->where('section_id', $this->section->id)
            ->get();
        $this->assertCount(1, $rows);
    }

    public function test_child_records_repointed_on_non_survivor()
    {
        // Insert first duplicate
        DB::table('standards_link')->insertGetId([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => 1,
            'status' => '1',
        ]);
        $nonSurvivor = DB::table('standards_link')->insertGetId([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => 1,
            'status' => '1',
        ]);

        // Attach a StudentAcademic to the non-survivor row
        $student = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => 1,
            'user_id' => $student->id,
            'standardLink_id' => $nonSurvivor,
        ]);

        Artisan::call('standards-link:dedupe');

        // The StudentAcademic should now point at the survivor, not the deleted row
        $sa = StudentAcademic::first();
        $survivor = DB::table('standards_link')
            ->where('school_id', $this->school->id)
            ->where('section_id', $this->section->id)
            ->first();
        $this->assertNotNull($sa);
        $this->assertSame($survivor->id, $sa->standardLink_id);
    }
}
