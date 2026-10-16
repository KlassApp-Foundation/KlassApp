<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\ParentProfile;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentProfileSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $adminA;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $this->schoolA = $this->school('Parent Profile A');
        $this->schoolB = $this->school('Parent Profile B');
        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
            'status' => 'active',
        ]);
    }

    public function test_K112_another_schools_parent_is_404_and_stays_active(): void
    {
        $other = $this->parent($this->schoolB, 'shared-parent', 'Amina', 'Kato');

        $this->actingAs($this->adminA)->get('/admin/parent/show/'.$other->name)->assertNotFound();
        $this->actingAs($this->adminA)->post('/admin/parent/show/'.$other->name.'/deactivate')->assertNotFound();

        $this->assertSame('active', $other->fresh()->status);
    }

    public function test_K112_deactivate_sets_inactive_and_keeps_the_row(): void
    {
        $parent = $this->parent($this->schoolA, 'local-parent', 'Grace', 'Namukasa');

        $this->actingAs($this->adminA)
            ->post('/admin/parent/show/'.$parent->name.'/deactivate')
            ->assertRedirect('/admin/parent/show/'.$parent->name);

        $parent->refresh();
        $this->assertSame('inactive', $parent->status);
        $this->assertSame('inactive', $parent->userprofile->fresh()->status);
        $this->assertNotNull(User::query()->find($parent->id));
    }

    public function test_K112_empty_children_fees_and_relation_are_honest(): void
    {
        $parent = $this->parent($this->schoolA, 'empty-parent', 'JOY', 'MUTEBI');

        $this->actingAs($this->adminA)
            ->get('/admin/parent/show/'.$parent->name)
            ->assertOk()
            ->assertSee('Joy Mutebi')
            ->assertSee('Parent')
            ->assertSee('No children yet')
            ->assertSee('No fees recorded')
            ->assertSee('Not linked')
            ->assertSee('Never')
            ->assertDontSee('Profession')
            ->assertDontSee('Annual income')
            ->assertDontSee('Organisation')
            ->assertDontSee('Designation')
            ->assertDontSee('Official address');
    }

    public function test_K112_child_card_uses_the_section_and_school_fees(): void
    {
        $class = $this->classIn($this->schoolA, 'P.1');
        $other = $this->classIn($this->schoolB, 'S.6');
        $parent = $this->parent($this->schoolA, 'fees-parent', 'Rita', 'Akello', 'Mother');
        $child = $this->learner($this->schoolA, $class, 'AARON');
        StudentParentLink::create([
            'school_id' => $this->schoolA->id,
            'parent_id' => $parent->id,
            'student_id' => $child->id,
            'status' => 1,
        ]);
        FeesCategories::create([
            'school_id' => $this->schoolA->id,
            'standard_id' => $class['link']->standard_id,
            'name' => 'Tuition',
            'amount' => 10000,
        ]);
        FeesCategories::create([
            'school_id' => $this->schoolB->id,
            'standard_id' => $other['link']->standard_id,
            'name' => 'Other school',
            'amount' => 99999,
        ]);
        FeePayment::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $child->id,
            'amount' => 4000,
            'paid_on' => '2026-02-01',
            'recorded_by' => $this->adminA->id,
            'status' => 'paid',
        ]);
        DB::table('school_details')->insert([
            'school_id' => $this->schoolA->id,
            'meta_key' => 'currency',
            'meta_value' => 'UGX',
        ]);
        WhatsAppUser::create([
            'phone' => '256700000111',
            'user_id' => $parent->id,
            'school_id' => $this->schoolA->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $html = $this->actingAs($this->adminA)
            ->get('/admin/parent/show/'.$parent->name.'?tab=children')
            ->assertOk()
            ->assertSee('Mother')
            ->assertSee('Aaron Learner')
            ->assertSee('P.1')
            ->assertSee('KLS0001001')
            ->assertSee('UGX 6,000')
            ->assertSee('Paid UGX 4,000 of UGX 10,000')
            ->assertSee('/admin/students/'.$child->id, false)
            ->assertSee('/admin/reports/cards/'.$class['link']->id.'/student/'.$child->id.'/preview', false)
            ->assertSee('Linked')
            ->assertDontSee('S.6')
            ->assertDontSee('99,999')
            ->getContent();

        $this->assertStringNotContainsString('Profession', $html);
    }

    private function school(string $name): School
    {
        $school = School::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'email' => uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
        ]);
        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        return $school;
    }

    private function parent(School $school, string $name, string $first, string $last, ?string $relation = null): User
    {
        $user = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'name' => $name,
            'status' => 'active',
            'mobile_no' => '256700111222',
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'firstname' => $first,
            'lastname' => $last,
            'status' => 'active',
        ]);
        if ($relation) {
            ParentProfile::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'relation' => $relation,
            ]);
        }

        return $user;
    }

    /**
     * @return array{year: AcademicYear, link: StandardLink}
     */
    private function classIn(School $school, string $sectionName): array
    {
        $year = AcademicYear::query()->where('school_id', $school->id)->where('status', 1)->first();
        $standard = Standard::query()->where('school_id', $school->id)->first();
        $section = Section::create([
            'school_id' => $school->id,
            'name' => $sectionName,
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        return ['year' => $year, 'link' => $link];
    }

    /**
     * @param  array{year: AcademicYear, link: StandardLink}  $class
     */
    private function learner(School $school, array $class, string $first): User
    {
        $user = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'firstname' => $first,
            'lastname' => 'Learner',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $class['year']->id,
            'user_id' => $user->id,
            'standardLink_id' => $class['link']->id,
            'klassapp_student_id' => 'KLS0001001',
        ]);

        return $user;
    }
}
