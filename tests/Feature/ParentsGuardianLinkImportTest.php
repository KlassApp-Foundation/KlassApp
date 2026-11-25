<?php

namespace Tests\Feature;

use App\Imports\ParentsImport;
use App\Models\School;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * The parents importer links a guardian to a learner who already exists.
 * It does not create standalone parents, and it reuses a guardian who is
 * already in the school so a parent with two children stays one account.
 */
class ParentsGuardianLinkImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $guardian;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Guardian Link School',
            'email' => 'guardian.link@example.test',
            'phone' => '+256700888001',
            'slug' => 'guardian-link-school',
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Import Admin',
            'email' => 'import.admin@example.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ]);
        $this->actingAs($admin);

        $this->learner = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => 'Mia Achieng',
            'email' => 'mia@example.test',
            'registration_number' => 'KLS0010042',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->learner->id,
            'usergroup_id' => 6,
            'firstname' => 'Mia',
            'lastname' => 'Achieng',
            'status' => 'active',
        ]);

        $this->guardian = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 7,
            'name' => 'Grace Achieng',
            'email' => 'grace@example.test',
            'mobile_no' => '+256700888002',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->guardian->id,
            'usergroup_id' => 7,
            'firstname' => 'Grace',
            'lastname' => 'Achieng',
            'status' => 'active',
        ]);
    }

    private function runRows(array $rows): array
    {
        (new ParentsImport)->collection(collect(array_map(fn (array $row) => new Collection($row), $rows)));

        return [
            'linked' => Session::get('insertedcount'),
            'failures' => Session::get('import_row_failures'),
        ];
    }

    public function test_it_links_an_existing_guardian_to_a_learner_by_kls_number(): void
    {
        $result = $this->runRows([[
            'kls' => 'KLS0010042',
            'firstname' => 'Grace',
            'lastname' => 'Achieng',
            'mobile_no' => '+256700888002',
            'email' => 'grace@example.test',
        ]]);

        $this->assertSame(1, $result['linked']);
        $this->assertSame([], $result['failures']);
        $this->assertDatabaseHas('student_parent_links', [
            'school_id' => $this->school->id,
            'student_id' => $this->learner->id,
            'parent_id' => $this->guardian->id,
            'status' => 'active',
        ]);
        // No second guardian account was created for the same phone.
        $this->assertSame(1, User::where('school_id', $this->school->id)->where('usergroup_id', 7)->count());
    }

    public function test_it_creates_a_second_guardian_and_links_them(): void
    {
        $result = $this->runRows([[
            'kls' => 'KLS0010042',
            'firstname' => 'Peter',
            'lastname' => 'Kimani',
            'mobile_no' => '+256700888003',
        ]]);

        $this->assertSame(1, $result['linked']);

        $second = User::where('mobile_no', '+256700888003')->first();
        $this->assertNotNull($second);
        $this->assertSame(7, (int) $second->usergroup_id);
        $this->assertDatabaseHas('student_parent_links', [
            'student_id' => $this->learner->id,
            'parent_id' => $second->id,
        ]);
    }

    public function test_it_reports_an_unknown_learner_and_writes_nothing(): void
    {
        $result = $this->runRows([[
            'kls' => 'KLS9999999',
            'firstname' => 'Ghost',
            'mobile_no' => '+256700888004',
        ]]);

        $this->assertSame(0, $result['linked']);
        $this->assertStringContainsString('no learner with that KLS number', $result['failures'][0]);
        $this->assertSame(0, StudentParentLink::count());
    }

    public function test_it_refuses_a_guardian_with_no_phone_and_no_email(): void
    {
        $result = $this->runRows([[
            'kls' => 'KLS0010042',
            'firstname' => 'No Contact',
        ]]);

        $this->assertSame(0, $result['linked']);
        $this->assertStringContainsString('either a phone number or an email', $result['failures'][0]);
    }

    public function test_it_reports_an_already_linked_guardian_instead_of_duplicating(): void
    {
        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $this->guardian->id,
            'student_id' => $this->learner->id,
            'status' => 'active',
        ]);

        $result = $this->runRows([[
            'kls' => 'KLS0010042',
            'firstname' => 'Grace',
            'mobile_no' => '+256700888002',
        ]]);

        $this->assertSame(0, $result['linked']);
        $this->assertStringContainsString('already linked', $result['failures'][0]);
        $this->assertSame(1, StudentParentLink::count());
    }
}
