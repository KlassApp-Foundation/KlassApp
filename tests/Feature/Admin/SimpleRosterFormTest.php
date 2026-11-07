<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Student and parent edit forms keep the import columns and leave the rest stored. */
class SimpleRosterFormTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $student;

    private User $parent;

    private StandardLink $stream;

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
            'name' => 'Roster Form School',
            'slug' => 'roster-form-'.uniqid(),
            'email' => 'roster-form-'.uniqid().'@t.sch.ug',
            'phone' => '+256703'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $this->school->id, 'name' => 'P.5 A', 'status' => 1,
        ]);
        $this->stream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => 'A',
            'status' => 1,
        ]);
        Section::create(['school_id' => $this->school->id, 'name' => 'P.3', 'status' => 1]);
        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => Section::query()->where('name', 'P.3')->where('school_id', $this->school->id)->value('id'),
            'status' => 1,
        ]);

        $this->admin = $this->person(3, 'Form Admin', 'form.admin@roster.test');
        $this->student = $this->person(6, 'Amina Pupil', 'amina.pupil@roster.test', [
            'mobile_no' => '+256700111222',
        ]);
        $this->student->userprofile->forceFill([
            'address' => 'Plot 4, Kampala',
            'notes' => 'kept-note',
            'gender' => 'female',
            'date_of_birth' => '2015-04-02',
        ])->save();
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $this->stream->id,
            'lin' => 'LIN-KEEP-1',
            'school_student_id' => 'SUN-KEEP',
        ]);
        $this->parent = $this->person(7, 'Grace Parent', 'grace.parent@roster.test', [
            'mobile_no' => '+256700333444',
        ]);
        $this->parent->userprofile->forceFill([
            'address' => 'Parent lane',
            'notes' => 'kept-parent-note',
        ])->save();
        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $this->parent->id,
            'student_id' => $this->student->id,
            'status' => 1,
        ]);
    }

    public function test_the_student_form_shows_only_the_import_fields_and_saves_them(): void
    {
        $page = $this->actingAs($this->admin)->get('/admin/student/edit/'.$this->student->name)->assertOk();
        $page->assertSee('data-testid="student-simple-edit"', false);
        $page->assertSee('Name');
        $page->assertSee('Parent phone');
        $page->assertDontSee('name="address"', false);
        $page->assertDontSee('Aadhaar');
        $page->assertDontSee('caste');

        $this->actingAs($this->admin)->post('/admin/student/edit/'.$this->student->name, [
            'name' => 'Amina Nakato',
            'class' => 'P.3',
            'stream' => '',
            'gender' => 'female',
            'date_of_birth' => '2015-04-02',
            'parent_name' => 'Grace Nakato',
            'parent_phone' => '+256700555666',
        ])->assertRedirect('/admin/students/'.$this->student->id);

        $this->student->refresh();
        $this->parent->refresh();
        $this->assertSame('Amina Nakato', $this->student->name);
        $this->assertSame('+256700111222', $this->student->mobile_no);
        $this->assertSame('Plot 4, Kampala', $this->student->userprofile->fresh()->address);
        $this->assertSame('kept-note', $this->student->userprofile->fresh()->notes);
        $this->assertSame('LIN-KEEP-1', StudentAcademic::query()->where('user_id', $this->student->id)->value('lin'));
        $this->assertSame('SUN-KEEP', StudentAcademic::query()->where('user_id', $this->student->id)->value('school_student_id'));
        $moved = StudentAcademic::query()->where('user_id', $this->student->id)->first();
        $this->assertNotSame($this->stream->id, (int) $moved->standardLink_id);
        $this->assertSame('+256700555666', $this->parent->fresh()->mobile_no);
        $this->assertSame('Grace Nakato', $this->parent->fresh()->name);
        $this->assertSame('kept-parent-note', $this->parent->userprofile->fresh()->notes);
        $this->assertSame('Parent lane', $this->parent->userprofile->fresh()->address);
    }

    public function test_an_unknown_class_is_rejected_and_another_school_cannot_edit(): void
    {
        $this->actingAs($this->admin)->post('/admin/student/edit/'.$this->student->name, [
            'name' => 'Amina Pupil',
            'class' => 'Not A Class',
            'stream' => 'Z',
            'gender' => 'female',
            'parent_name' => 'Grace Parent',
            'parent_phone' => '+256700333444',
        ])->assertSessionHasErrors('class');

        $this->assertSame($this->stream->id, (int) StudentAcademic::query()->where('user_id', $this->student->id)->value('standardLink_id'));

        $other = School::create([
            'name' => 'Other Roster School',
            'slug' => 'roster-other-'.uniqid(),
            'email' => 'roster-other-'.uniqid().'@t.sch.ug',
            'phone' => '+256704'.random_int(100000, 999999),
            'status' => 1,
        ]);
        AcademicYear::create([
            'school_id' => $other->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        Standard::create([
            'school_id' => $other->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);
        $otherAdmin = $this->person(3, 'Other Admin', 'other.admin@roster.test', [], $other);
        $this->actingAs($otherAdmin)->get('/admin/student/edit/'.$this->student->name)->assertNotFound();
        $this->actingAs($otherAdmin)->post('/admin/student/edit/'.$this->student->name, [
            'name' => 'Hacked',
            'class' => 'P.3',
        ])->assertNotFound();

        $teacher = $this->person(5, 'Teacher', 'teacher.form@roster.test');
        $this->actingAs($teacher)->get('/admin/student/edit/'.$this->student->name)->assertRedirect('/teacher/dashboard');
    }

    public function test_each_row_action_opens_a_real_page_for_this_student(): void
    {
        $admin = $this->actingAs($this->admin);

        $admin->get('/admin/students/'.$this->student->id.'?action=marks')
            ->assertOk()
            ->assertSee('data-testid="student-profile"', false)
            ->assertSee('Add marks for');

        $admin->get('/admin/students/'.$this->student->id.'?action=pay')
            ->assertOk()
            ->assertSee('Record a payment');

        $admin->get('/admin/students/'.$this->student->id)
            ->assertOk()
            ->assertSee('Amina Pupil');

        $admin->get(route('admin.attendance.register', ['standardLink_id' => $this->stream->id]))
            ->assertOk()
            ->assertSee('data-testid="teacher-attendance-register"', false);
    }

    public function test_the_parent_form_saves_name_phone_and_email_only(): void
    {
        $page = $this->actingAs($this->admin)->get('/admin/parent/edit/'.$this->parent->name)->assertOk();
        $page->assertSee('data-testid="parent-simple-edit"', false);
        $page->assertSee('Phone');
        $page->assertDontSee('Official Address');
        $page->assertDontSee('name="relation"', false);

        $this->actingAs($this->admin)->post('/admin/parent/edit/'.$this->parent->name, [
            'name' => 'Grace A. Parent',
            'phone' => '+256700777888',
            'email' => 'grace.new@roster.test',
        ])->assertRedirect();

        $this->parent->refresh();
        $this->assertSame('Grace A. Parent', $this->parent->name);
        $this->assertSame('+256700777888', $this->parent->mobile_no);
        $this->assertSame('grace.new@roster.test', $this->parent->email);
        $this->assertSame('kept-parent-note', $this->parent->userprofile->fresh()->notes);
        $this->assertSame('Parent lane', $this->parent->userprofile->fresh()->address);
        $this->assertSame('+256700111222', $this->student->fresh()->mobile_no);
    }

    private function person(int $group, string $name, string $email, array $extra = [], ?School $school = null): User
    {
        $school = $school ?: $this->school;
        $user = User::create(array_merge([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ], $extra));
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name, 2)[1] ?? '',
        ]);

        return $user->fresh('userprofile');
    }
}
