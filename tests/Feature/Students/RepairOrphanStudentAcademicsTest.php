<?php

namespace Tests\Feature\Students;

use App\Helpers\DashboardCache;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class RepairOrphanStudentAcademicsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private StandardLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Orphan School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'nursery',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Baby Class E',
            'status' => 1,
        ]);

        $this->link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        \DB::table('student_id_sequences')->insertOrIgnore([
            'school_id' => $this->school->id,
            'next_seq' => 9,
        ]);
    }

    public function test_dry_run_reports_orphan_without_writing(): void
    {
        $orphan = User::create([
            'name' => 'Grace Orphan',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('secret'),
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'status' => 'active',
        ]);

        $exit = Artisan::call('students:repair-orphan-academics', [
            '--school' => $this->school->id,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertNull(StudentAcademic::where('user_id', $orphan->id)->first());
        $this->assertStringContainsString('WOULD repair', Artisan::output());
    }

    public function test_repairs_orphan_using_batch_mate_modal_link(): void
    {
        $createdAt = now();

        $mate = User::create([
            'name' => 'Batch Mate',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('secret'),
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'status' => 'active',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $mate->id,
            'standardLink_id' => $this->link->id,
            'klassapp_student_id' => 'KLS0030001',
        ]);

        $orphan = User::create([
            'name' => 'Grace Mbabazi',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('secret'),
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'status' => 'active',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        Cache::forever('studentCount_'.$this->school->id, 0);

        $exit = Artisan::call('students:repair-orphan-academics', [
            '--school' => $this->school->id,
        ]);

        $this->assertSame(0, $exit);
        $academic = StudentAcademic::where('user_id', $orphan->id)->first();
        $this->assertNotNull($academic);
        $this->assertSame((int) $this->link->id, (int) $academic->standardLink_id);
        $this->assertSame((int) $this->year->id, (int) $academic->academic_year_id);
        $this->assertMatchesRegularExpression('/^KLS\d{7}$/', (string) $academic->klassapp_student_id);
        $this->assertNull(Cache::get('studentCount_'.$this->school->id));
    }

    public function test_ignores_students_that_already_have_academics(): void
    {
        $student = User::create([
            'name' => 'Complete Student',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('secret'),
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $student->id,
            'standardLink_id' => $this->link->id,
            'klassapp_student_id' => 'KLS0030008',
        ]);

        $exit = Artisan::call('students:repair-orphan-academics', [
            '--school' => $this->school->id,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('No orphaned active students found', Artisan::output());
        $this->assertSame(1, StudentAcademic::where('user_id', $student->id)->count());
    }
}
