<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * T-107: routes that act on a person by name (delete, deactivate, reset password, status) must act
 * on exactly one person of the right kind in the signed-in user's school, until T-104 moves them to
 * ids. Synthetic people and schools only.
 */
class NameRouteGuardsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $other;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $this->school = $this->school('Guard A');
        $this->other = $this->school('Guard B');
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'usergroup_id' => 3, 'status' => 'active', 'name' => 'Guard Admin']);
    }

    /** @return array<string, array{0: string, 1: string, 2: int}> route label => [verb, url template, group] */
    public static function writeRoutes(): array
    {
        return [
            'delete parent' => ['delete', '/admin/parent/delete/%s', 7],
            'delete student' => ['delete', '/admin/student/delete/%s', 6],
            'delete teacher' => ['delete', '/admin/teacher/delete/%s', 5],
            'delete staff' => ['delete', '/admin/staff/delete/%s', 11],
            'deactivate parent' => ['post', '/admin/parent/show/%s/deactivate', 7],
            'reset parent password' => ['post', '/admin/parent/show/%s/reset-password', 7],
            'deactivate teacher' => ['post', '/admin/teacher/show/%s/deactivate', 5],
            'set status' => ['post', '/admin/user/updateStatus/%s', 6],
        ];
    }

    #[DataProvider('writeRoutes')]
    public function test_T107_a_name_shared_by_two_people_changes_nobody(string $verb, string $url, int $group): void
    {
        $a = $this->person($this->school, $group, 'Same Name');
        $b = $this->person($this->school, $group, 'Same Name');

        $response = $this->actingAs($this->admin)->{$verb}(sprintf($url, rawurlencode('Same Name')), ['status' => 'inactive']);

        $response->assertStatus(409);
        $this->assertTrue($response->exception === null || str_contains((string) $response->getContent().(string) $response->exception?->getMessage(), 'More than one person'));
        foreach ([$a, $b] as $p) {
            $fresh = User::withTrashed()->find($p->id);
            $this->assertNull($fresh->deleted_at, 'not deleted');
            $this->assertSame('active', $fresh->status, 'not deactivated');
        }
    }

    #[DataProvider('writeRoutes')]
    public function test_T107_another_kind_of_person_with_that_name_is_not_found(string $verb, string $url, int $group): void
    {
        $wrongGroup = str_contains($url, 'updateStatus') ? 3 : ($group === 5 ? 7 : 5);
        $wrong = $this->person($this->school, $wrongGroup, 'Wrong Kind');

        $this->actingAs($this->admin)->{$verb}(sprintf($url, rawurlencode('Wrong Kind')), ['status' => 'inactive'])->assertNotFound();

        $fresh = User::withTrashed()->find($wrong->id);
        $this->assertNull($fresh->deleted_at);
        $this->assertSame('active', $fresh->status);
    }

    #[DataProvider('writeRoutes')]
    public function test_T107_a_name_that_only_exists_in_another_school_is_not_found(string $verb, string $url, int $group): void
    {
        $foreign = $this->person($this->other, $group, 'Elsewhere Person');

        $this->actingAs($this->admin)->{$verb}(sprintf($url, rawurlencode('Elsewhere Person')), ['status' => 'inactive'])->assertNotFound();

        $fresh = User::withTrashed()->find($foreign->id);
        $this->assertNull($fresh->deleted_at);
        $this->assertSame('active', $fresh->status);
    }

    public function test_T107_an_administrator_name_is_never_a_status_target(): void
    {
        $this->actingAs($this->admin)->post('/admin/user/updateStatus/'.rawurlencode('Guard Admin'), ['status' => 'inactive'])->assertNotFound();
        $this->assertSame('active', $this->admin->fresh()->status);
    }

    public function test_T107_a_unique_name_of_the_right_kind_still_works(): void
    {
        $parent = $this->person($this->school, 7, 'Only Parent');
        $teacher = $this->person($this->school, 5, 'Only Teacher');

        $this->actingAs($this->admin)->post('/admin/parent/show/'.rawurlencode('Only Parent').'/deactivate')->assertRedirect();
        $this->assertSame('inactive', $parent->fresh()->status);

        $this->actingAs($this->admin)->delete('/admin/teacher/delete/'.rawurlencode('Only Teacher'));
        $this->assertNotNull(User::withTrashed()->find($teacher->id)->deleted_at);
    }

    private function school(string $name): School
    {
        $school = School::create([
            'name' => $name,
            'slug' => 'g-'.uniqid(),
            'email' => 'g-'.uniqid().'@t.sch.ug',
            'phone' => '+256707'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'AY',
            'start_date' => now()->subMonths(2)->toDateString(), 'end_date' => now()->addMonths(6)->toDateString(), 'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$school->id);
        Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 4, 'status' => 1]);

        return $school;
    }

    private function person(School $school, int $group, string $name): User
    {
        $user = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name, 2)[1] ?? 'Person',
            'status' => 'active',
        ]);

        return $user;
    }
}
