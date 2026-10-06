<?php

namespace Tests\Feature\Admin;

use App\Models\Admission;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admissions are scoped to the caller's school: another school's admission
 * must 404 and must not change, and the approval flow must keep working.
 */
class AdmissionSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private AcademicYear $yearA;
    private AcademicYear $yearB;
    private Standard $standardA;
    private Standard $standardB;
    private Section $sectionA;
    private Section $sectionB;
    private User $adminA;
    private Admission $admissionA;
    private Admission $admissionB;
    private Admission $admissionB2;
    private Admission $admissionA2;
    private Admission $admissionA3;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = School::create(['name' => 'Adm Scope A', 'slug' => 'adm-scope-a-' . uniqid(), 'email' => uniqid() . '@t.sch.ug', 'phone' => '+256700111222', 'status' => 1, 'registration_country' => 'Uganda']);
        $this->schoolB = School::create(['name' => 'Adm Scope B', 'slug' => 'adm-scope-b-' . uniqid(), 'email' => uniqid() . '@t.sch.ug', 'phone' => '+256700111333', 'status' => 1, 'registration_country' => 'Uganda']);

        $this->yearA = AcademicYear::create(['school_id' => $this->schoolA->id, 'name' => '2026', 'description' => 'y', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
        $this->yearB = AcademicYear::create(['school_id' => $this->schoolB->id, 'name' => '2026', 'description' => 'y', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);

        $this->standardA = Standard::create(['school_id' => $this->schoolA->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $this->standardB = Standard::create(['school_id' => $this->schoolB->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $this->sectionA = Section::create(['school_id' => $this->schoolA->id, 'name' => 'P4', 'status' => 1]);
        $this->sectionB = Section::create(['school_id' => $this->schoolB->id, 'name' => 'P4', 'status' => 1]);

        StandardLink::create(['school_id' => $this->schoolA->id, 'academic_year_id' => $this->yearA->id, 'standard_id' => $this->standardA->id, 'section_id' => $this->sectionA->id, 'status' => 1]);
        StandardLink::create(['school_id' => $this->schoolB->id, 'academic_year_id' => $this->yearB->id, 'standard_id' => $this->standardB->id, 'section_id' => $this->sectionB->id, 'status' => 1]);

        $this->adminA = User::factory()->create(['school_id' => $this->schoolA->id, 'usergroup_id' => 3, 'email' => 'adm-scope-admin-a@test.sch.ug', 'name' => 'Adm Scope Admin A']);

        $mk = function (School $school, AcademicYear $year, Standard $standard, string $no, string $status, string $name): Admission {
            return Admission::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'name' => $name,
                'date_of_birth' => '2016-05-01',
                'gender' => 'male',
                'permanent_address' => 'Addr',
                'address_for_communication' => 'Addr',
                'half_yearly_mark_details' => 'n/a',
                'father_name' => 'Father',
                'father_mobile_no' => '+256700999888',
                'application_no' => $no,
                'application_status' => $status,
                'payment_status' => 'unpaid',
                'remarks' => '',
            ]);
        };

        $this->admissionA = $mk($this->schoolA, $this->yearA, $this->standardA, 'ADM-A-1', 'Draft', 'Adm Scope Child A');
        $this->admissionA2 = $mk($this->schoolA, $this->yearA, $this->standardA, 'ADM-A-2', 'Draft', 'Adm Scope Child A2');
        $this->admissionA3 = $mk($this->schoolA, $this->yearA, $this->standardA, 'ADM-A-3', 'Draft', 'Adm Scope Child A3');
        $this->admissionB = $mk($this->schoolB, $this->yearB, $this->standardB, 'ADM-B-1', 'Pending', 'Adm Scope Child B');
        $this->admissionB2 = $mk($this->schoolB, $this->yearB, $this->standardB, 'ADM-B-2', 'Pending', 'Adm Scope Child B2');
    }

    public function test_show_of_another_schools_admission_gets_a_404_and_returns_nothing(): void
    {
        $response = $this->actingAs($this->adminA)->get('/admin/admission/show/' . $this->admissionB->id);

        $response->assertNotFound();
        $response->assertDontSee('ADM-B-1');
    }

    public function test_show_of_own_schools_admission_still_serves(): void
    {
        $response = $this->actingAs($this->adminA)->get('/admin/admission/show/' . $this->admissionA->id);

        $response->assertOk();
        $response->assertJsonFragment(['application_no' => 'ADM-A-1']);
    }

    public function test_edit_of_another_schools_admission_gets_a_404(): void
    {
        $this->actingAs($this->adminA)->get('/admin/admission/edit/' . $this->admissionB->id)->assertNotFound();
    }

    public function test_edit_of_own_schools_admission_still_works(): void
    {
        $this->actingAs($this->adminA)->get('/admin/admission/edit/' . $this->admissionA->id)->assertOk();
    }

    public function test_update_of_another_schools_admission_gets_a_404_and_changes_nothing(): void
    {
        $studentsBefore = DB::table('users')->where('usergroup_id', 6)->count();

        $this->actingAs($this->adminA)->post('/admin/admission/update/' . $this->admissionB->id, [
            'application_status' => 'Draft',
            'section_id' => $this->sectionB->id,
            'fee_group_id' => 1,
            'payment_status' => 'unpaid',
        ])->assertNotFound();

        $this->admissionB->refresh();
        $this->assertSame('Pending', $this->admissionB->application_status);
        $this->assertSame($studentsBefore, DB::table('users')->where('usergroup_id', 6)->count());
    }

    public function test_destroy_of_another_schools_admission_gets_a_404_and_keeps_it(): void
    {
        $this->actingAs($this->adminA)->get('/admin/admission/delete/' . $this->admissionB2->id)->assertNotFound();

        $this->assertTrue(Admission::where('id', $this->admissionB2->id)->exists());
    }

    public function test_destroy_in_own_school_still_works(): void
    {
        $this->actingAs($this->adminA)->get('/admin/admission/delete/' . $this->admissionA3->id)->assertOk();

        $this->assertFalse(Admission::where('id', $this->admissionA3->id)->exists());
    }

    public function test_admission_list_does_not_include_another_schools_admissions(): void
    {
        $response = $this->actingAs($this->adminA)->get('/admin/admissionlist');

        $response->assertOk();
        $response->assertDontSee('ADM-B-1');
        $response->assertSee('ADM-A-1', false);
    }

    public function test_approving_and_paid_in_own_school_still_creates_the_student(): void
    {
        $studentsBefore = DB::table('users')->where('school_id', $this->schoolA->id)->where('usergroup_id', 6)->count();

        $this->actingAs($this->adminA)->post('/admin/admission/update/' . $this->admissionA2->id, [
            'application_status' => 'Approved',
            'section_id' => $this->sectionA->id,
            'fee_group_id' => 1,
            'payment_status' => 'paid',
        ])->assertOk();

        $this->admissionA2->refresh();
        $this->assertSame('Approved', $this->admissionA2->application_status);

        $studentsAfter = DB::table('users')->where('school_id', $this->schoolA->id)->where('usergroup_id', 6)->count();
        $this->assertSame($studentsBefore + 1, $studentsAfter);

        $student = User::where('school_id', $this->schoolA->id)->where('usergroup_id', 6)->latest('id')->first();
        $this->assertNotNull($student);
        $this->assertStringStartsWith('KLS', (string) $student->registration_number);
    }
}
