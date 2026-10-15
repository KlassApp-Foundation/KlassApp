<?php

namespace Tests\Feature\Demo;

use App\Models\Academics\Exam;
use App\Models\AcademicTerm;
use App\Models\ReportGeneration;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Services\Demo\DemoFollowAlong;
use App\Services\Demo\LatestExamMarks;
use App\Services\DemoSchoolCommsGuard;
use Carbon\Carbon;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Task 9 demo data: every demo exam class has an unreleased exam with marks
 * for Mathematics, Science, English and Social Studies, and demo:provision
 * and demo:refresh keep it. Foreign keys on, non-UTC timezone (rules 35, 42).
 */
class DemoFollowAlongDataTest extends TestCase
{
    use RefreshDatabase;

    private const SUBJECTS = ['Mathematics', 'Science', 'English', 'Social Studies'];

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Africa/Kampala', 'services.whatsapp.demo_parent_user_id' => 0]);
        date_default_timezone_set('Africa/Kampala');
        Carbon::setTestNow(Carbon::parse('2026-10-15 09:00:00', 'Africa/Kampala'));
        DemoSchoolCommsGuard::flushCache();
    }

    protected function tearDown(): void
    {
        putenv('DEMO_PROVISIONING_ENABLED');
        unset($_ENV['DEMO_PROVISIONING_ENABLED']);
        Carbon::setTestNow();
        date_default_timezone_set('UTC');
        parent::tearDown();
    }

    public function test_task9_demo_seed_gives_every_exam_class_an_unreleased_four_subject_exam(): void
    {
        $this->seed(DemoJuniorSchoolSeeder::class);
        $school = School::where('email', 'demo-junior@klassapp.xyz')->firstOrFail();

        $this->assertFollowAlongExam($school);
        $roster = DemoFollowAlong::roster($school);
        $this->assertGreaterThanOrEqual(20, $roster->count());
        $this->assertLessThanOrEqual(30, $roster->count());
    }

    public function test_task9_demo_refresh_puts_the_follow_along_exam_back(): void
    {
        $this->seed(DemoJuniorSchoolSeeder::class);
        $school = School::where('email', 'demo-junior@klassapp.xyz')->firstOrFail();
        $exams = $this->followAlongExams($school);

        // A demo visitor releases the exam, edits a mark and deletes another.
        Exam::whereIn('id', $exams->pluck('id'))->update(['status' => 'submitted']);
        DB::table('marks')->whereIn('exam_id', $exams->pluck('id'))->orderBy('id')->limit(1)->update(['marks' => 1, 'grade' => 'F9']);
        DB::table('marks')->whereIn('exam_id', $exams->pluck('id'))->orderByDesc('id')->limit(1)->delete();

        $this->artisan('demo:refresh', ['school' => $school->id])->assertExitCode(0);

        $this->assertFollowAlongExam($school);
        $this->assertSame(0, DB::table('marks')->whereIn('exam_id', $exams->pluck('id'))->where('marks', 1)->count());
    }

    public function test_task9_demo_provision_gives_the_new_school_the_follow_along_exam(): void
    {
        putenv('DEMO_PROVISIONING_ENABLED=true');
        $_ENV['DEMO_PROVISIONING_ENABLED'] = 'true';

        $this->assertSame(0, Artisan::call('demo:provision', [
            'password' => 'provision-test-secret',
            '--name' => 'Riverside Primary',
            '--city' => 'Entebbe',
            '--admin-name' => 'Ada Nakato',
            '--admin-email' => 'ada.riverside@example.com',
            '--terms' => '2026-02-02,2026-04-30,2026-05-18,2026-08-21,2026-08-24,2026-11-28',
            '--confirm' => true,
        ]), Artisan::output());

        $this->assertFollowAlongExam(School::where('is_demo', 1)->where('name', 'Riverside Primary')->firstOrFail());
    }

    private function examSections(School $school)
    {
        $names = (new \ReflectionMethod(DemoJuniorSchoolSeeder::class, 'richConfig'))->invoke(new DemoJuniorSchoolSeeder())['exam_section_names'];
        $sections = Section::where('school_id', $school->id)->whereIn('name', $names)->get();
        $this->assertNotEmpty($sections);

        return $sections;
    }

    private function followAlongExams(School $school)
    {
        $current = AcademicTerm::where('school_id', $school->id)->get()->firstWhere('status', 'current');
        $this->assertNotNull($current);

        return Exam::where('school_id', $school->id)
            ->where('academic_term_id', $current->id)
            ->whereIn('section_id', $this->examSections($school)->pluck('id'))
            ->whereHas('examType', fn ($q) => $q->where('code', 'EOT'))
            ->get();
    }

    private function assertFollowAlongExam(School $school): void
    {
        $current = AcademicTerm::where('school_id', $school->id)->get()->firstWhere('status', 'current');

        foreach ($this->examSections($school) as $section) {
            $latest = LatestExamMarks::forSection((int) $school->id, (int) $section->id);
            $this->assertNotNull($latest, "{$section->name} has no latest exam");
            $this->assertSame('End of Term Examination · ' . $current->name, $latest['label'], $section->name);
            $this->assertSame(self::SUBJECTS, array_column($latest['subjects'], 'name'), $section->name);
            foreach ($latest['exams'] as $exam) {
                $this->assertSame('undone', $exam->status, "{$section->name} exam is released");
            }

            $students = StudentAcademic::where('school_id', $school->id)
                ->whereIn('standardLink_id', StandardLink::where('school_id', $school->id)->where('section_id', $section->id)->pluck('id'))
                ->where('academic_year_id', $current->academic_year_id ?? StudentAcademic::where('school_id', $school->id)->max('academic_year_id'))
                ->pluck('user_id');
            $this->assertNotEmpty($students);
            foreach ($students as $studentId) {
                $this->assertSame(4, $latest['marks']->where('student_id', $studentId)->count(), "{$section->name} student {$studentId}");
            }
        }

        // Unreleased: no report cards for the current term.
        $this->assertSame(0, ReportGeneration::where('school_id', $school->id)->where('academic_term_id', $current->id)->count());
    }
}
