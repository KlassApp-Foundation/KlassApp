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

        $this->assertSame(10, Section::where('school_id', $school->id)->count());
        $this->assertSame(13, StandardLink::where('school_id', $school->id)->count());
        $this->assertSame(6, StandardLink::where('school_id', $school->id)->whereNotNull('stream')->count(), 'Upper primary carries A/B streams');

        $this->assertSame(66, StudentAcademic::where('school_id', $school->id)->count());
        $this->assertSame(13, User::where('school_id', $school->id)->whereNotIn('usergroup_id', [6])->count());

        $this->assertSame(84, FeePayment::where('school_id', $school->id)->count());
        $this->assertSame(1320, Attendance::where('school_id', $school->id)->count());
        $this->assertSame(28, Exam::where('school_id', $school->id)->count());
        $this->assertSame(216, DB::table('marks')->where('school_id', $school->id)->count());
        $this->assertSame(9, SchoolGradingSystem::where('school_id', $school->id)->count());

        $head = User::where('school_id', $school->id)->where('usergroup_id', 4)->first();
        $this->assertNotNull($head, 'Head teacher (SchoolSubadmin) must exist');
        $this->assertNotNull(User::where('school_id', $school->id)->where('usergroup_id', 11)->first(), 'Bursar (Accountant) must exist');
    }

    public function test_demo_academy_seeder_is_safe_to_run_twice(): void
    {
        $school = $this->seedDemo();
        $this->seed(DemoAcademySeeder::class);

        $this->assertSame(66, StudentAcademic::where('school_id', $school->id)->count());
        $this->assertSame(84, FeePayment::where('school_id', $school->id)->count());
        $this->assertSame(1320, Attendance::where('school_id', $school->id)->count());
        $this->assertSame(28, Exam::where('school_id', $school->id)->count());
        $this->assertSame(216, DB::table('marks')->where('school_id', $school->id)->count());
    }
}
