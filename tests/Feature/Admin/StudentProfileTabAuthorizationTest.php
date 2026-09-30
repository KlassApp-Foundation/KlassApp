<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\TransactionAccount;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Finding for the profile-bugs PR: is tab data restricted by role on the SERVER, or does only
 * the browser hide tabs? (ProfileTab.vue hides just "Notes" outside admin mode; every other tab
 * is rendered for every role and simply calls its endpoint.)
 *
 * These tests pin the answer: the server decides. They run with the REAL route middleware
 * (no withoutMiddleware), so a regression that exposes an admin-only tab to another role fails here.
 */
class StudentProfileTabAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET_ACCOUNT = 'ACCT-SECRET-7781';

    private const SECRET_HEALTH = 'SecretPenicillinAllergy';

    private School $school;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([3 => 'schooladmin', 5 => 'teacher', 6 => 'student', 7 => 'parent'] as $id => $name) {
            DB::table('usergroups')->upsert([['id' => $id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]], 'id');
        }

        $this->school = $this->makeSchool('Tab Authz School');
        $year = $this->makeYear($this->school);

        $this->student = $this->makeMember($this->school, 6, 'tab-pupil');
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->student->id,
            'medication_problems' => self::SECRET_HEALTH,
            'academic_status' => 'pass',
        ]);

        TransactionAccount::unguarded(fn () => TransactionAccount::create([
            'school_id' => $this->school->id,
            'user_id' => $this->student->id,
            'name' => 'Test Bank',
            'key' => 'k',
            'account_number' => self::SECRET_ACCOUNT,
            'ifsc_code' => 'X',
        ]));
    }

    private function makeSchool(string $name): School
    {
        return School::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'email' => uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
    }

    private function makeYear(School $school): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
    }

    private function makeMember(School $school, int $usergroup, string $name): User
    {
        $user = User::factory()->create([
            'usergroup_id' => $usergroup,
            'school_id' => $school->id,
            'name' => $name,
            'email' => $name.'@t.sch.ug',
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $usergroup,
            'firstname' => ucfirst($name),
            'lastname' => 'Tester',
            'status' => 'active',
        ]);

        return $user;
    }

    /** @return array<string, list<string>> */
    private function adminOnlyTabUrls(): array
    {
        return [
            'bank details' => ['/admin/bankdetails/get/'.$this->student->name],
            'medical history' => ['/admin/student/show/medicalHistory/'.$this->student->name],
            'health records' => ['/admin/student/health/'.$this->student->id],
            'fees' => ['/admin/student/show/fees/'.$this->student->name],
        ];
    }

    public function test_non_admin_roles_cannot_fetch_admin_tab_data_even_when_they_call_the_endpoint_directly(): void
    {
        foreach ([5 => 'teacher', 6 => 'student', 7 => 'parent'] as $usergroup => $label) {
            $actor = $this->makeMember($this->school, $usergroup, 'actor-'.$label);

            foreach ($this->adminOnlyTabUrls() as $tab => [$url]) {
                $response = $this->actingAs($actor)->get($url);
                $body = $response->getContent();

                $this->assertFalse($response->isOk(), "{$label} got 200 from {$tab} ({$url})");
                $this->assertStringNotContainsString(self::SECRET_ACCOUNT, $body, "{$label} read bank details via {$tab}");
                $this->assertStringNotContainsString(self::SECRET_HEALTH, $body, "{$label} read health notes via {$tab}");
            }
        }
    }

    /** Positive control: the endpoints do return the data to the role that owns them, so the denials above are not vacuous. */
    public function test_school_admin_can_fetch_the_same_tabs(): void
    {
        \App\Models\Standard::create(['school_id' => $this->school->id, 'name' => 'P1', 'display_name' => 'P1', 'order' => 1, 'status' => 1]);
        $admin = $this->makeMember($this->school, 3, 'the-admin');

        $this->actingAs($admin)
            ->get('/admin/bankdetails/get/'.$this->student->name)
            ->assertOk()
            ->assertSee(self::SECRET_ACCOUNT);

        $this->actingAs($admin)
            ->get('/admin/student/show/medicalHistory/'.$this->student->name)
            ->assertOk()
            ->assertSee(self::SECRET_HEALTH);
    }

    public function test_bank_details_post_is_also_closed_to_teachers(): void
    {
        $teacher = $this->makeMember($this->school, 5, 'actor-teacher');

        $response = $this->actingAs($teacher)->post('/admin/bankdetails/add/'.$this->student->name, [
            'bank_name' => 'Evil', 'key' => 'k', 'account_number' => '1', 'ifsc_code' => 'X',
        ]);

        $this->assertFalse($response->isOk());
        $this->assertSame(1, TransactionAccount::where('user_id', $this->student->id)->count());
    }

    public function test_teacher_portal_has_no_bank_or_fee_tab_endpoints(): void
    {
        $teacher = $this->makeMember($this->school, 5, 'actor-teacher');

        foreach (['/teacher/bankdetails/get/', '/teacher/student/show/fees/'] as $prefix) {
            $this->actingAs($teacher)->get($prefix.$this->student->name)->assertNotFound();
        }
    }

    public function test_teacher_off_roster_cannot_read_student_tabs_including_health(): void
    {
        $teacher = $this->makeMember($this->school, 5, 'off-roster-teacher');

        foreach (['details', 'medicalHistory', 'relations', 'siblings', 'discipline', 'attendance', 'libraryactivity'] as $tab) {
            $response = $this->actingAs($teacher)->get("/teacher/student/show/{$tab}/".$this->student->name);

            $response->assertForbidden();
            $this->assertStringNotContainsString(self::SECRET_HEALTH, $response->getContent());
        }
    }

    public function test_admin_cannot_read_another_schools_bank_details(): void
    {
        $otherSchool = $this->makeSchool('Other Authz School');
        $this->makeYear($otherSchool);
        $otherAdmin = $this->makeMember($otherSchool, 3, 'other-admin');

        $response = $this->actingAs($otherAdmin)->get('/admin/bankdetails/get/'.$this->student->name);

        $this->assertStringNotContainsString(self::SECRET_ACCOUNT, $response->getContent());
        $this->assertFalse($response->isOk());
    }
}
