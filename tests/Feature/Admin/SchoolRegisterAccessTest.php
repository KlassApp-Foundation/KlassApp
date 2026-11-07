<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admins and class heads may open the one-click register for any class in
 * their school. Stream teachers stay on their own streams.
 */
class SchoolRegisterAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $admin;

    private User $classHead;

    private User $streamTeacher;

    private StandardLink $ownStream;

    private StandardLink $otherStream;

    private User $pupil;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Register Access School',
            'slug' => 'reg-access-'.uniqid(),
            'email' => 'reg-access-'.uniqid().'@t.sch.ug',
            'phone' => '+256701'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$this->school->id);

        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $this->admin = $this->user(3, 'School Admin');
        $this->classHead = $this->user(5, 'Class Head');
        $this->streamTeacher = $this->user(5, 'Stream Teacher');

        $headed = Section::create([
            'school_id' => $this->school->id, 'name' => 'P.4', 'status' => 1, 'class_teacher_id' => $this->classHead->id,
        ]);
        $other = Section::create([
            'school_id' => $this->school->id, 'name' => 'P.6', 'status' => 1,
        ]);
        $this->ownStream = $this->link($standard, $headed, 'A', $this->classHead->id);
        $this->otherStream = $this->link($standard, $other, 'East', $this->streamTeacher->id);
        $this->pupil = $this->user(6, 'Other Pupil');
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->pupil->id,
            'standardLink_id' => $this->otherStream->id,
        ]);
    }

    public function test_an_admin_can_open_and_save_any_stream_in_the_school(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.attendance.register', ['standardLink_id' => $this->otherStream->id]))
            ->assertOk()
            ->assertSee('OTHER PUPIL')
            ->assertSee('data-testid="teacher-attendance-register"', false);

        $saved = $this->actingAs($this->admin)->post(route('admin.attendance.register.save'), [
            'standardLink_id' => $this->otherStream->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows' => [
                $this->pupil->id => ['status' => '0', 'comment' => 'Sent home'],
            ],
        ]);
        $saved->assertRedirect();
        $location = (string) $saved->headers->get('Location');
        $this->assertStringContainsString('/admin/attendance/register', $location);
        $this->assertStringContainsString('standardLink_id='.$this->otherStream->id, $location);

        $this->assertDatabaseHas('attendances', [
            'school_id' => $this->school->id,
            'standardLink_id' => $this->otherStream->id,
            'user_id' => $this->pupil->id,
            'status' => 0,
            'recorded_by' => $this->admin->id,
        ]);
    }

    public function test_a_class_head_can_open_another_class_in_the_same_school(): void
    {
        $this->actingAs($this->classHead)
            ->get(route('teacher.attendance.register', ['standardLink_id' => $this->otherStream->id]))
            ->assertOk()
            ->assertSee('OTHER PUPIL');

        $this->actingAs($this->classHead)
            ->get(route('teacher.attendance.register', ['section_id' => $this->ownStream->section_id]))
            ->assertOk()
            ->assertSee('P.4');
    }

    public function test_a_stream_teacher_cannot_open_another_class(): void
    {
        $this->actingAs($this->streamTeacher)
            ->get(route('teacher.attendance.register', ['standardLink_id' => $this->ownStream->id]))
            ->assertForbidden();

        $this->actingAs($this->streamTeacher)->post(route('teacher.attendance.register.save'), [
            'standardLink_id' => $this->ownStream->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows' => [
                $this->pupil->id => ['status' => '1', 'comment' => ''],
            ],
        ])->assertForbidden();

        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_another_school_and_other_roles_cannot_use_the_register(): void
    {
        $other = School::create([
            'name' => 'Other Register School',
            'slug' => 'reg-other-'.uniqid(),
            'email' => 'reg-other-'.uniqid().'@t.sch.ug',
            'phone' => '+256702'.random_int(100000, 999999),
            'status' => 1,
        ]);
        $otherAdmin = $this->user(3, 'Other Admin', $other);
        AcademicYear::create([
            'school_id' => $other->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'status' => 1,
        ]);

        $this->actingAs($otherAdmin)
            ->get(route('admin.attendance.register', ['standardLink_id' => $this->otherStream->id]))
            ->assertForbidden();
        $this->actingAs($otherAdmin)
            ->get(route('admin.attendance.register'))
            ->assertOk()
            ->assertDontSee('OTHER PUPIL');

        $parent = $this->user(7, 'A Parent');
        $this->actingAs($parent)->get(route('admin.attendance.register'))->assertNotFound();
        $this->actingAs($parent)->get(route('teacher.attendance.register'))->assertNotFound();

        $student = $this->user(6, 'A Student');
        $this->actingAs($student)->get(route('teacher.attendance.register'))->assertRedirect('/student/dashboard');
    }

    private function link(Standard $standard, Section $section, string $stream, int $teacherId): StandardLink
    {
        return StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => $stream,
            'class_teacher_id' => $teacherId,
            'status' => 1,
        ]);
    }

    private function user(int $group, string $name, ?School $school = null): User
    {
        $school = $school ?: $this->school;
        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@t.sch.ug',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name, 2)[1] ?? 'Person',
        ]);

        return $user;
    }
}
