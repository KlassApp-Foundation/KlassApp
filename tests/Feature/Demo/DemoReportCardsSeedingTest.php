<?php

namespace Tests\Feature\Demo;

use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\ReportGeneration;
use App\Models\School;
use App\Models\StandardLink;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Database\Seeders\DemoSeniorSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoReportCardsSeedingTest extends TestCase
{
    use RefreshDatabase;

    private function eotExamTypeId(): int
    {
        return (int) DB::table('exam_types')->where('code', 'EOT')->value('id');
    }

    private function term(School $school, string $name): AcademicTerm
    {
        return AcademicTerm::where('school_id', $school->id)->where('name', $name)->firstOrFail();
    }

    private function eotExamIds(School $school, int $academicTermId): Collection
    {
        return Exam::where('school_id', $school->id)
            ->where('academic_term_id', $academicTermId)
            ->where('exam_type_id', $this->eotExamTypeId())
            ->pluck('id');
    }

    private function assertCurrencySeeded(School $school): void
    {
        $this->assertSame(
            'UGX',
            DB::table('school_details')
                ->where('school_id', $school->id)
                ->where('meta_key', 'currency')
                ->value('meta_value')
        );
    }

    private function assertPreviousTermGenerated(School $school, int $previousTermId): void
    {
        $prevExamIds = $this->eotExamIds($school, $previousTermId);
        $this->assertGreaterThan(0, $prevExamIds->count(), 'Previous term should have EOT exams.');
        $this->assertGreaterThan(0, DB::table('marks')->whereIn('exam_id', $prevExamIds)->count(), 'Previous term EOT exams should carry marks.');

        $prevLinks = StandardLink::where('school_id', $school->id)
            ->whereIn('section_id', Exam::whereIn('id', $prevExamIds)->pluck('section_id')->unique())
            ->pluck('id');

        $generations = ReportGeneration::where('school_id', $school->id)->get();
        $this->assertGreaterThan(0, $generations->count(), 'Previous term report cards should be generated.');

        foreach ($generations as $generation) {
            $this->assertSame('merged', $generation->mode);
            $this->assertSame('completed', $generation->status, "Generation for link {$generation->standard_link_id} should be completed.");
            $this->assertNotNull($generation->file_path, 'Generation should have a merged PDF path.');
            $this->assertFileExists(storage_path('app/' . $generation->file_path), 'Merged PDF should exist on disk.');
            $this->assertContains($generation->standard_link_id, $prevLinks->all(), 'Generated report card should belong to a class with previous-term marks.');
        }
    }

    private function assertCurrentTermLiveButUngenerated(School $school, int $currentTermId): void
    {
        $curExamIds = $this->eotExamIds($school, $currentTermId);
        $this->assertGreaterThan(0, $curExamIds->count(), 'Current term should have EOT exams for the live generate flow.');
        $this->assertGreaterThan(0, DB::table('marks')->whereIn('exam_id', $curExamIds)->count(), 'Current term EOT exams should carry marks so Generate has data.');

        $generatedClassesWithCurrentMarks = ReportGeneration::where('school_id', $school->id)
            ->whereIn('standard_link_id', StandardLink::where('school_id', $school->id)
                ->whereIn('section_id', Exam::whereIn('id', $curExamIds)->pluck('section_id')->unique())
                ->pluck('id'))
            ->count();
        $this->assertGreaterThan(0, $generatedClassesWithCurrentMarks, 'Seeded classes should also carry current-term marks so live Generate report cards stays available.');
    }

    public function test_demo_junior_generates_previous_term_report_cards_and_leaves_current_term_live(): void
    {
        $this->seed(DemoJuniorSchoolSeeder::class);
        $school = School::where('slug', 'demo-junior-school')->firstOrFail();

        $this->assertCurrencySeeded($school);
        $this->assertPreviousTermGenerated($school, $this->term($school, 'Term II')->id);
        $this->assertCurrentTermLiveButUngenerated($school, $this->term($school, 'Term III')->id);
    }

    public function test_demo_senior_generates_previous_term_report_cards_and_leaves_current_term_live(): void
    {
        $this->seed(DemoSeniorSchoolSeeder::class);
        $school = School::where('slug', 'demo-senior-school')->firstOrFail();

        $this->assertCurrencySeeded($school);
        $this->assertPreviousTermGenerated($school, $this->term($school, 'Term II')->id);
        $this->assertCurrentTermLiveButUngenerated($school, $this->term($school, 'Term III')->id);
    }
}
