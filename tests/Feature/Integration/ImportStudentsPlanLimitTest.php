<?php

namespace Tests\Feature\Integration;

use App\Livewire\Admin\ImportStudents;
use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * #1279 (plan limits) stops a student import at the plan's remaining slots and names each row it
 * skipped. #1269's Import students page must show those rows the way the setup wizard does.
 */
class ImportStudentsPlanLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->school = School::create([
            'name' => 'Plan Limit Import School', 'email' => 'plan-import@example.test', 'phone' => '+256700333111',
            'slug' => 'plan-import', 'status' => 1, 'registration_country' => 'Uganda', 'toshi_enabled' => 0,
        ]);
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026', 'description' => 'Current Academic Year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 2, 'status' => 1]);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'P.1', 'status' => 1]);
        StandardLink::create(['school_id' => $this->school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1]);

        $plan = Plan::create([
            'cycle' => 30, 'name' => 'TwoStudents', 'display_name' => 'Two students', 'no_of_students' => 2, 'no_of_users' => 5,
            'is_active' => 1, 'order' => 99, 'amount' => 0,
        ]);
        CurrentPlan::create(['school_id' => $this->school->id, 'plan_id' => $plan->id, 'status' => 'running']);

        $this->admin = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => 3, 'name' => 'Plan Import Admin', 'email' => 'plan-import-admin@example.test',
            'password' => bcrypt('secret'), 'status' => 'active', 'email_verified' => 1,
        ]);
        Userprofile::create(['user_id' => $this->admin->id, 'school_id' => $this->school->id, 'usergroup_id' => 3, 'firstname' => 'Plan', 'lastname' => 'Admin']);
    }

    public function test_rows_over_the_plan_are_named_on_the_import_students_page(): void
    {
        $this->actingAs($this->admin);
        $csv = "Name,Class,Stream\nAmina One,P.1,\nBrian Two,P.1,\nCarol Three,P.1,\nDavid Four,P.1,\n";

        $component = Livewire::test(ImportStudents::class)
            ->set('studentUpload', UploadedFile::fake()->createWithContent('students.csv', $csv));

        $this->assertSame(2, User::query()->where('school_id', $this->school->id)->where('usergroup_id', 6)->count());
        $component->assertSet('studentUploadReport', '2 students added, 2 skipped');
        $component->assertSee('plan allows');
        $component->assertSee('Carol Three');
        $component->assertSee('David Four');
    }
}
