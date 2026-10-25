<?php

namespace Tests\Feature\Demo;

use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Database\Seeders\DemoSeniorSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Rebuilds the three local demo schools the way a fresh checkout does:
 * Demo Junior and Demo Senior from their seeders, then Sunset PS from
 * demo:provision. Sunset is a second junior-shaped school, so it must
 * finish its own students, classes, streams, class teachers and marks
 * without changing the other two schools or an existing non-demo Sunset PS.
 */
class DemoSchoolsRebuildTest extends TestCase
{
    use RefreshDatabase;

    private string $password = 'sunset-rebuild-secret';

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
        putenv('DEMO_PROVISIONING_ENABLED=true');
        $_ENV['DEMO_PROVISIONING_ENABLED'] = 'true';
        $_SERVER['DEMO_PROVISIONING_ENABLED'] = 'true';
        putenv('DEMO_PROVISION_ADMIN_PASSWORD='.$this->password);
        $_ENV['DEMO_PROVISION_ADMIN_PASSWORD'] = $this->password;
        $_SERVER['DEMO_PROVISION_ADMIN_PASSWORD'] = $this->password;
    }

    protected function tearDown(): void
    {
        putenv('DEMO_PROVISIONING_ENABLED');
        unset($_ENV['DEMO_PROVISIONING_ENABLED'], $_SERVER['DEMO_PROVISIONING_ENABLED']);
        putenv('DEMO_PROVISION_ADMIN_PASSWORD');
        unset($_ENV['DEMO_PROVISION_ADMIN_PASSWORD'], $_SERVER['DEMO_PROVISION_ADMIN_PASSWORD']);
        parent::tearDown();
    }

    public function test_rebuilding_demo_junior_demo_senior_and_sunset_ps_completes(): void
    {
        $this->seed(DemoJuniorSchoolSeeder::class);
        $this->seed(DemoSeniorSchoolSeeder::class);

        $junior = School::where('name', 'Demo Junior School')->where('is_demo', 1)->firstOrFail();
        $senior = School::where('name', 'Demo Senior School')->where('is_demo', 1)->firstOrFail();
        $juniorStudents = StudentAcademic::where('school_id', $junior->id)->count();
        $seniorStudents = StudentAcademic::where('school_id', $senior->id)->count();
        $juniorLin = StudentAcademic::where('school_id', $junior->id)->where('lin', 'UG-JUN-LIN-0003')->value('id');

        $exit = Artisan::call('demo:provision', [
            '--name' => 'Sunset PS',
            '--city' => 'Entebbe',
            '--admin-name' => 'Amina Nakato',
            '--admin-email' => 'amina.sunset@example.com',
            '--terms' => '2026-02-02,2026-04-30,2026-05-18,2026-08-21,2026-08-24,2026-11-28',
            '--confirm' => true,
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, $exit, Artisan::output());
        $this->assertStringNotContainsString($this->password, Artisan::output());

        $sunset = School::where('name', 'Sunset PS')->where('is_demo', 1)->firstOrFail();
        $this->assertNotSame($junior->id, $sunset->id);
        $this->assertNotSame($senior->id, $sunset->id);

        $this->assertSchoolIsComplete($junior, $juniorStudents);
        $this->assertSchoolIsComplete($senior, $seniorStudents);
        $this->assertSchoolIsComplete($sunset, 76);

        $this->assertSame($juniorLin, StudentAcademic::where('school_id', $junior->id)->where('lin', 'UG-JUN-LIN-0003')->value('id'));
        $this->assertSame(0, StudentAcademic::where('school_id', $sunset->id)->where('lin', 'like', 'UG-JUN-LIN-%')->count());
        $this->assertGreaterThan(0, StudentAcademic::where('school_id', $sunset->id)->whereNotNull('lin')->count());
    }

    public function test_provision_does_not_change_an_existing_non_demo_sunset_ps(): void
    {
        $school = School::create([
            'name' => 'Sunset PS',
            'slug' => 'sunset-ps-real',
            'email' => 'office@sunset-ps.example',
            'phone' => '0771000001',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $school->forceFill(['is_demo' => 0, 'is_test' => 0])->save();

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Existing year',
            'start_date' => '2026-02-02',
            'end_date' => '2026-12-04',
            'status' => 1,
        ]);
        $student = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Existing Sunset Learner',
            'email' => 'learner@sunset-ps.example',
            'password' => Hash::make('existing-hash'),
            'status' => 'active',
        ]);
        $academic = StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'academic_status' => 'pass',
            'lin' => 'KEEP-SUNSET-LIN',
        ]);

        $snapshot = [
            'name' => $school->name,
            'email' => $school->email,
            'phone' => $school->phone,
            'is_demo' => (int) $school->fresh()->is_demo,
            'students' => StudentAcademic::where('school_id', $school->id)->count(),
            'users' => User::where('school_id', $school->id)->count(),
            'password' => $student->password,
            'lin' => $academic->lin,
        ];

        $exit = Artisan::call('demo:provision', [
            '--name' => 'Sunset PS',
            '--city' => 'Entebbe',
            '--admin-name' => 'Amina Nakato',
            '--admin-email' => 'amina.sunset@example.com',
            '--terms' => '2026-02-02,2026-04-30,2026-05-18,2026-08-21,2026-08-24,2026-11-28',
            '--confirm' => true,
            '--no-interaction' => true,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('not a demo school', Artisan::output());

        $school->refresh();
        $student->refresh();
        $academic->refresh();
        $this->assertSame($snapshot['name'], $school->name);
        $this->assertSame($snapshot['email'], $school->email);
        $this->assertSame($snapshot['phone'], $school->phone);
        $this->assertSame($snapshot['is_demo'], (int) $school->is_demo);
        $this->assertSame($snapshot['students'], StudentAcademic::where('school_id', $school->id)->count());
        $this->assertSame($snapshot['users'], User::where('school_id', $school->id)->count());
        $this->assertSame($snapshot['password'], $student->password);
        $this->assertSame($snapshot['lin'], $academic->lin);
        $this->assertSame(1, School::where('name', 'Sunset PS')->count());
        $this->assertSame(0, DB::table('marks')->where('school_id', $school->id)->count());
    }

    private function assertSchoolIsComplete(School $school, int $students): void
    {
        $this->assertSame(1, (int) $school->fresh()->is_demo, $school->name);

        $this->assertSame($students, StudentAcademic::where('school_id', $school->id)->count(), $school->name.' students');
        $this->assertGreaterThan(0, Section::where('school_id', $school->id)->count(), $school->name.' classes');
        $this->assertGreaterThan(0, StandardLink::where('school_id', $school->id)->whereNotNull('stream')->count(), $school->name.' streams');
        $this->assertSame(0, Section::where('school_id', $school->id)->whereNull('class_teacher_id')->count(), $school->name.' class teachers');
        $this->assertSame(0, StandardLink::where('school_id', $school->id)->whereNull('class_teacher_id')->count(), $school->name.' stream teachers');
        $this->assertGreaterThan(0, Exam::where('school_id', $school->id)->count(), $school->name.' exams');
        $this->assertGreaterThan(0, DB::table('marks')->where('school_id', $school->id)->count(), $school->name.' marks');

        $teacherIds = User::where('school_id', $school->id)->where('usergroup_id', 5)->pluck('id');
        $assigned = Section::where('school_id', $school->id)->pluck('class_teacher_id');
        $this->assertTrue($assigned->every(fn ($id) => $teacherIds->contains($id)), $school->name.' class teachers belong to the school');
    }
}
