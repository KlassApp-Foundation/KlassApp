<?php

namespace Tests\Feature\Demo;

use App\Models\Academics\Exam;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DemoSeniorSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoSeniorSchoolSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): School
    {
        $this->seed(DemoSeniorSchoolSeeder::class);

        return School::where('email', 'demo-senior@klassapp.xyz')->firstOrFail();
    }

    public function test_demo_senior_seeder_creates_o_and_a_level_forms(): void
    {
        $school = $this->seedDemo();

        $this->assertSame(1, (int) $school->is_demo);
        $this->assertSame(1, (int) $school->is_test);
        $this->assertSame('demo-senior-school', $school->slug);
        $this->assertSame('Demo Senior School', $school->name);
        $this->assertSame('o_a_level', $school->school_category);

        foreach (['S.1', 'S.2', 'S.3', 'S.4', 'S.5', 'S.6'] as $form) {
            $this->assertDatabaseHas('sections', ['school_id' => $school->id, 'name' => $form]);
        }
        $this->assertDatabaseMissing('sections', ['school_id' => $school->id, 'name' => 'P.1']);
        $this->assertDatabaseMissing('sections', ['school_id' => $school->id, 'name' => 'Baby Class']);

        $this->assertSame(8, StandardLink::where('school_id', $school->id)->count(), 'S.1/S.2 streams + S.3–S.6');
        $this->assertDatabaseHas('subjects', ['school_id' => $school->id, 'name' => 'Physics']);
        $this->assertTrue(Subject::where('school_id', $school->id)->where('name', 'Economics')->exists(), 'A-level Economics present');

        $this->assertGreaterThan(40, StudentAcademic::where('school_id', $school->id)->count());
        $this->assertNotNull(User::where('school_id', $school->id)->where('usergroup_id', 3)->first());
        $this->assertNotNull(User::where('school_id', $school->id)->where('usergroup_id', 5)->first());

        $this->assertGreaterThan(0, FeePayment::where('school_id', $school->id)->count());
        $this->assertGreaterThan(0, Attendance::where('school_id', $school->id)->count());
        $this->assertGreaterThan(0, Exam::where('school_id', $school->id)->count());
        $this->assertGreaterThan(0, DB::table('marks')->where('school_id', $school->id)->count());
        $this->assertDemoGenderMix($school->id);
        $this->assertFemaleTeacherIsFemale($school->id);
    }

    public function test_demo_senior_seeder_is_idempotent(): void
    {
        $school = $this->seedDemo();
        $students = StudentAcademic::where('school_id', $school->id)->count();
        $fees = FeePayment::where('school_id', $school->id)->count();
        $marks = DB::table('marks')->where('school_id', $school->id)->count();

        $this->seed(DemoSeniorSchoolSeeder::class);

        $this->assertSame($students, StudentAcademic::where('school_id', $school->id)->count());
        $this->assertSame($fees, FeePayment::where('school_id', $school->id)->count());
        $this->assertSame($marks, DB::table('marks')->where('school_id', $school->id)->count());
    }

    private function assertDemoGenderMix(int $schoolId): void
    {
        $rows = DB::table('student_academics as sa')
            ->join('standards_link as sl', 'sl.id', '=', 'sa.standardLink_id')
            ->join('sections as sec', 'sec.id', '=', 'sl.section_id')
            ->join('userprofiles as up', 'up.user_id', '=', 'sa.user_id')
            ->where('sa.school_id', $schoolId)
            ->get(['sec.name as class_name', 'up.gender']);

        $byClass = [];
        $girls = 0;
        $total = 0;
        foreach ($rows as $row) {
            $byClass[$row->class_name][$row->gender] = ($byClass[$row->class_name][$row->gender] ?? 0) + 1;
            $total++;
            if ($row->gender === 'female') {
                $girls++;
            }
        }

        $this->assertGreaterThan(0, $total);
        $this->assertGreaterThan(0.45, $girls / $total);
        $this->assertLessThan(0.55, $girls / $total);

        $ratios = [];
        foreach ($byClass as $counts) {
            $classTotal = array_sum($counts);
            $ratios[] = ($counts['female'] ?? 0) / max(1, $classTotal);
        }
        $this->assertGreaterThanOrEqual(0.2, max($ratios) - min($ratios));
    }

    private function assertFemaleTeacherIsFemale(int $schoolId): void
    {
        $gender = DB::table('users')
            ->join('userprofiles', 'userprofiles.user_id', '=', 'users.id')
            ->where('users.school_id', $schoolId)
            ->where('users.usergroup_id', 5)
            ->where('users.name', 'Diana Kembabazi')
            ->value('userprofiles.gender');

        $this->assertSame('female', $gender);

        $male = DB::table('users')
            ->join('userprofiles', 'userprofiles.user_id', '=', 'users.id')
            ->where('users.school_id', $schoolId)
            ->where('users.usergroup_id', 5)
            ->where('users.name', 'Peter Okello')
            ->value('userprofiles.gender');

        $this->assertSame('male', $male);
    }
}
