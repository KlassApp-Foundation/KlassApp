<?php

namespace Tests\Feature\Demo;

use App\Models\Academics\Exam;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoJuniorSchoolSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): School
    {
        $this->seed(DemoJuniorSchoolSeeder::class);

        return School::where('email', 'demo-junior@klassapp.xyz')->firstOrFail();
    }

    public function test_demo_junior_seeder_creates_nursery_and_primary_only(): void
    {
        $school = $this->seedDemo();

        $this->assertSame(1, (int) $school->is_demo);
        $this->assertSame(1, (int) $school->is_test);
        $this->assertSame('demo-junior-school', $school->slug);
        $this->assertSame('Demo Junior School', $school->name);

        $this->assertSame(10, Section::where('school_id', $school->id)->count(), 'Baby/Middle/Top + P.1–P.7');
        $this->assertDatabaseHas('sections', ['school_id' => $school->id, 'name' => 'P.1']);
        $this->assertDatabaseHas('sections', ['school_id' => $school->id, 'name' => 'P.7']);
        $this->assertDatabaseMissing('sections', ['school_id' => $school->id, 'name' => 'S.1']);
        $this->assertDatabaseMissing('sections', ['school_id' => $school->id, 'name' => 'Senior One']);

        $this->assertSame(13, StandardLink::where('school_id', $school->id)->count());
        $this->assertSame(6, StandardLink::where('school_id', $school->id)->whereNotNull('stream')->count());

        $this->assertGreaterThan(50, StudentAcademic::where('school_id', $school->id)->count());
        $this->assertNotNull(User::where('school_id', $school->id)->where('usergroup_id', 3)->first());
        $this->assertNotNull(User::where('school_id', $school->id)->where('usergroup_id', 5)->first());
        $this->assertNotNull(User::where('school_id', $school->id)->where('usergroup_id', 11)->first());

        $this->assertGreaterThan(0, FeePayment::where('school_id', $school->id)->count());
        $this->assertGreaterThan(0, Attendance::where('school_id', $school->id)->count());
        $this->assertGreaterThan(0, Exam::where('school_id', $school->id)->count());
        $this->assertGreaterThan(0, DB::table('marks')->where('school_id', $school->id)->count());
    }

    public function test_demo_junior_seeder_is_idempotent(): void
    {
        $school = $this->seedDemo();
        $students = StudentAcademic::where('school_id', $school->id)->count();
        $fees = FeePayment::where('school_id', $school->id)->count();
        $attendance = Attendance::where('school_id', $school->id)->count();
        $exams = Exam::where('school_id', $school->id)->count();
        $marks = DB::table('marks')->where('school_id', $school->id)->count();

        $this->seed(DemoJuniorSchoolSeeder::class);

        $this->assertSame($students, StudentAcademic::where('school_id', $school->id)->count());
        $this->assertSame($fees, FeePayment::where('school_id', $school->id)->count());
        $this->assertSame($attendance, Attendance::where('school_id', $school->id)->count());
        $this->assertSame($exams, Exam::where('school_id', $school->id)->count());
        $this->assertSame($marks, DB::table('marks')->where('school_id', $school->id)->count());
    }
}
