<?php

namespace Tests\Feature\Demo;

use App\Models\Academics\Exam;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use Database\Seeders\DemoAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoAcademySeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): School
    {
        $this->seed(DemoAcademySeeder::class);

        return School::where('email', 'demo-academy@klassapp.xyz')->firstOrFail();
    }

    public function test_demo_academy_seeder_creates_the_full_ugandan_school(): void
    {
        $school = $this->seedDemo();

        $this->assertSame(1, (int) $school->is_demo, 'Demo Academy must be marked is_demo (comms guard)');
        $this->assertSame(1, (int) $school->is_test);
        $this->assertSame('demo-academy-uganda', $school->slug);

        $this->assertSame(14, Section::where('school_id', $school->id)->count(), 'nursery + primary + S.1-S.4');
        $this->assertSame(17, StandardLink::where('school_id', $school->id)->count());
        $this->assertSame(6, StandardLink::where('school_id', $school->id)->whereNotNull('stream')->count(), 'Upper primary carries A/B streams');

        $this->assertSame(84, StudentAcademic::where('school_id', $school->id)->count());
        $this->assertSame(15, User::where('school_id', $school->id)->whereNotIn('usergroup_id', [6])->count());

        $this->assertSame(105, FeePayment::where('school_id', $school->id)->count());
        // 84 students × 40 school days (8 weeks) × 2 sessions = 6720 attendance rows.
        $this->assertSame(6720, Attendance::where('school_id', $school->id)->count());
        $this->assertSame(56, Exam::where('school_id', $school->id)->count());
        $this->assertSame(342, DB::table('marks')->where('school_id', $school->id)->count());
        $this->assertSame(18, SchoolGradingSystem::where('school_id', $school->id)->count(), 'grading scales for primary and o-level');

        // Secondary section: S.1-S.4 with O-level subjects and PLE entry records.
        $this->assertDatabaseHas('sections', ['school_id' => $school->id, 'name' => 'Senior Four']);
        $this->assertDatabaseHas('subjects', ['school_id' => $school->id, 'name' => 'Physics']);
        $this->assertSame(3, DB::table('admissions')->where('school_id', $school->id)->count(), 'PLE entry records');
        $ple = DB::table('admissions')->where('school_id', $school->id)->first();
        $this->assertNotNull($ple->ple_index_number);
        $this->assertNotNull($ple->ple_aggregate);

        $head = User::where('school_id', $school->id)->where('usergroup_id', 4)->first();
        $this->assertNotNull($head, 'Head teacher (SchoolSubadmin) must exist');
        $this->assertNotNull(User::where('school_id', $school->id)->where('usergroup_id', 11)->first(), 'Bursar (Accountant) must exist');
    }

    public function test_demo_academy_seeder_is_safe_to_run_twice(): void
    {
        $school = $this->seedDemo();
        $this->seed(DemoAcademySeeder::class);

        $this->assertSame(84, StudentAcademic::where('school_id', $school->id)->count());
        $this->assertSame(105, FeePayment::where('school_id', $school->id)->count());
        $this->assertSame(6720, Attendance::where('school_id', $school->id)->count());
        $this->assertSame(56, Exam::where('school_id', $school->id)->count());
        $this->assertSame(342, DB::table('marks')->where('school_id', $school->id)->count());
    }
}
