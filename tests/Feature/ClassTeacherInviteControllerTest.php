<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClassTeacherInviteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_admin_can_view_invite_form_for_their_section(): void
    {
        $admin = $this->admin();
        $section = Section::create([
            'school_id' => $admin->school_id,
            'name'      => 'P1 A',
            'status'    => 1,
        ]);
        StandardLink::create([
            'school_id'        => $admin->school_id,
            'section_id'       => $section->id,
            'standard_id'      => Standard::create(['school_id' => $admin->school_id, 'name' => 'primary', 'order' => 1, 'status' => 1])->id,
            'academic_year_id' => AcademicYear::create(['school_id' => $admin->school_id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1])->id,
            'status'           => 1,
            'stream'           => 'A',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.class-teacher-invite.create', $section)
        );

        $response->assertOk();
        $response->assertSee($section->name);
        $response->assertSee('Invite Class Teacher');
    }

    public function test_admin_can_invite_new_teacher_via_web_route(): void
    {
        $admin = $this->admin();
        $link = $this->stream($admin->school_id);

        $response = $this->actingAs($admin)->post(
            route('admin.class-teacher-invite.store', $link),
            ['email' => 'new@school.ug', 'name' => 'John Kato', 'phone' => '0777']
        );

        $response->assertRedirect(route('admin.classes'));
        $response->assertSessionHas('successmessage');

        $link->refresh();
        $this->assertNotNull($link->class_teacher_id);
    }

    public function test_admin_can_reassign_existing_teacher_via_web_route(): void
    {
        $admin = $this->admin();
        $teacher = User::factory()->create([
            'school_id'    => $admin->school_id,
            'usergroup_id' => 5,
            'email'        => 'teacher@school.ug',
            'status'       => 'active',
        ]);
        $link = $this->stream($admin->school_id);

        $response = $this->actingAs($admin)->post(
            route('admin.class-teacher-invite.store', $link),
            ['email' => 'teacher@school.ug', 'existing_teacher_id' => $teacher->id]
        );

        $response->assertRedirect(route('admin.classes'));
        $response->assertSessionHas('successmessage');

        $link->refresh();
        $this->assertSame($teacher->id, $link->class_teacher_id);
    }

    private function admin(): User
    {
        $school = School::create([
            'name'   => 'Test School ' . uniqid(),
            'slug'   => 'test-' . uniqid(),
            'email'  => 'test-' . uniqid() . '@school.ug',
            'phone'  => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);
        return User::factory()->create([
            'school_id'    => $school->id,
            'usergroup_id' => 3,
        ]);
    }

    private function stream(int $schoolId): StandardLink
    {
        $section = Section::create([
            'school_id' => $schoolId,
            'name'      => 'P1 A',
            'status'    => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $schoolId,
            'name'      => 'primary',
            'order'     => 1,
            'status'    => 1,
        ]);
        $year = AcademicYear::create([
            'school_id'   => $schoolId,
            'name'        => '2026',
            'start_date'  => '2026-01-01',
            'end_date'    => '2026-12-31',
            'status'      => 1,
        ]);
        return StandardLink::create([
            'school_id'        => $schoolId,
            'section_id'       => $section->id,
            'standard_id'      => $standard->id,
            'academic_year_id' => $year->id,
            'status'           => 1,
            'stream'           => 'A',
        ]);
    }
}
