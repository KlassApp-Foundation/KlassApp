<?php

namespace Tests\Feature\Students;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Qualification;
use App\Models\School;
use App\Models\TeacherProfile;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberListPaginationAndScopingTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private int $teacherAId;
    private int $teacherBId;
    private int $staffAId;
    private int $staffBId;
    private int $extraTeacherIds = 0;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'name' => 'OldStudent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        foreach (['A', 'B'] as $letter) {
            ${'school' . $letter} = School::create([
                'name' => 'Member School ' . $letter,
                'slug' => 'member-school-' . strtolower($letter),
                'email' => strtolower($letter) . '@member.test',
                'phone' => '+25670000000' . ($letter === 'A' ? 1 : 2),
                'status' => 1,
                'registration_country' => 'Uganda',
            ]);
            ${'year' . $letter} = AcademicYear::create([
                'school_id' => ${'school' . $letter}->id,
                'name' => '2026 ' . $letter,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
            ]);
            AcademicTerm::create([
                'school_id' => ${'school' . $letter}->id,
                'academic_year_id' => ${'year' . $letter}->id,
                'name' => 'Term 1',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-04-30',
                'status' => 'current',
            ]);
            ${'standard' . $letter} = Standard::create([
                'school_id' => ${'school' . $letter}->id,
                'name' => 'primary',
                'order' => 1,
                'status' => 1,
            ]);
            ${'section' . $letter} = Section::create([
                'school_id' => ${'school' . $letter}->id,
                'name' => 'P.1 ' . $letter,
                'status' => 1,
            ]);
            ${'stream' . $letter} = StandardLink::create([
                'school_id' => ${'school' . $letter}->id,
                'academic_year_id' => ${'year' . $letter}->id,
                'standard_id' => ${'standard' . $letter}->id,
                'section_id' => ${'section' . $letter}->id,
                'stream' => 'A',
                'status' => 1,
            ]);
            ${'admin' . $letter} = User::factory()->create([
                'school_id' => ${'school' . $letter}->id,
                'usergroup_id' => 3,
                'email' => 'admin.' . strtolower($letter) . '@member.test',
            ]);
        }

        $this->schoolA = $schoolA;
        $this->schoolB = $schoolB;
        $this->adminA = $adminA;

        $this->teacherAId = $this->makeMember($this->schoolA->id, 5, 'Teacher', 'Alpha', true)->id;
        $this->teacherBId = $this->makeMember($this->schoolB->id, 5, 'Teacher', 'Beta', true)->id;
        $this->staffAId = $this->makeMember($this->schoolA->id, 8, 'Librarian', 'Librarian Alpha', true)->id;
        $this->staffBId = $this->makeMember($this->schoolB->id, 8, 'Librarian', 'Librarian Beta', true)->id;

        for ($i = 0; $i < 30; $i++) {
            $this->makeMember($this->schoolA->id, 5, 'Teacher', 'Filler' . $i, true);
        }
        $this->extraTeacherIds = 30;
    }

    private function makeMember(int $schoolId, int $usergroupId, string $designation, string $name, bool $active): User
    {
        $user = User::factory()->create([
            'school_id' => $schoolId,
            'usergroup_id' => $usergroupId,
            'email' => strtolower(str_replace(' ', '.', $name)) . $schoolId . '@member.test',
        ]);
        $this->forceUserStatus($user, $active ? 'active' : 'inactive');
        Userprofile::create([
            'school_id' => $schoolId,
            'user_id' => $user->id,
            'usergroup_id' => $usergroupId,
            'firstname' => $name,
            'lastname' => 'Member',
            'gender' => 'male',
            'status' => $active ? 'active' : 'inactive',
        ]);
        if (in_array($usergroupId, [5], true)) {
            TeacherProfile::create([
                'school_id' => $schoolId,
                'academic_year_id' => \App\Models\AcademicYear::where('school_id', $schoolId)->first()->id,
                'user_id' => $user->id,
                'designation' => strtolower($designation),
                'employee_id' => 'EMP-' . $user->id,
            ]);
        }
        return $user;
    }

    private function forceUserStatus(User $user, string $status): void
    {
        $user->forceFill(['status' => $status])->save();
    }

    private function fetch(string $endpoint): \Illuminate\Testing\TestResponse
    {
        return $this
            ->actingAs($this->adminA)
            ->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class, \App\Http\Middleware\VerifyCsrfToken::class)
            ->get($endpoint);
    }

    /** @test */
    public function teacher_find_returns_paginated_payload()
    {
        $resp = $this->fetch('/admin/teachers/find');
        $resp->assertOk();

        file_put_contents('/tmp/findbody.txt', $resp->getContent());
        $json = $this->safeJson($resp);
        $rows = $this->rowsOf($json);

        $this->assertSame(25, count($rows), 'teacher find payload should be page-sized (25)');
        $ids = array_column($rows, 'id');
        $this->assertContains($this->teacherAId, $ids);
        $this->assertNotContains($this->teacherBId, $ids, 'cross-school teacher must not leak into the payload');
        $meta = $this->metaOf($json);
        $this->assertSame(31, $meta['total']);
        $this->assertSame(2, $meta['last_page']);
    }

    /** @test */
    public function teacher_find_page_two_is_renderable()
    {
        $resp2 = $this->fetch('/admin/teachers/find?page=2');
        $resp2->assertOk();
        $rows2 = $this->rowsOf($resp2->json());
        $this->assertSame(6, count($rows2));
        $ids2 = array_column($rows2, 'id');
        $this->assertNotContains($this->teacherBId, $ids2, 'page 2 remains school-scoped');
        $resp1 = $this->fetch('/admin/teachers/find');
        $ids1 = array_column($this->rowsOf($resp1->json()), 'id');
        $this->assertContains($this->teacherAId, $ids1, 'deterministic ordering puts Alpha on page 1');
        $this->assertEmpty(array_intersect($ids2, $ids1), 'page windows must not overlap');
    }

    /** @test */
    public function crafted_school_param_does_not_change_scope_on_teacher_find()
    {
        $resp = $this->fetch('/admin/teachers/find?school_id=' . $this->schoolB->id . '&school=' . $this->schoolB->id);
        $rows = $this->rowsOf($resp->json());
        $this->assertNotContains($this->teacherBId, $rows ? array_column($rows, 'id') : []);
    }

    /** @test */
    public function staff_find_returns_paginated_payload()
    {
        $resp = $this->fetch('/admin/staffs/find');
        $rows = $this->rowsOf($resp->json());
        $this->assertSame(1, count($rows), 'page size holds; only school A librarian matches');
        $this->assertSame($this->staffAId, $rows[0]['id']);
        $this->assertNotContains($this->staffBId, array_column($rows, 'id'));
    }

    private function safeJson($resp)
    {
        $raw = $resp->getContent();
        $json = json_decode($raw, true);
        if (! is_array($json)) {
            fwrite(STDERR, "RAW-BODY-HEAD " . substr($raw, 0, 500) . "\n");
        }
        return $json;
    }

    private function rowsOf(array $payload): array
    {
        foreach (['data.data', 'data'] as $path) {
            $rows = data_get($payload, $path);
            $first = is_array($rows) ? (array_values($rows)[0] ?? null) : null;
            if (is_array($first) && array_key_exists('id', $first)) {
                return $rows;
            }
        }
        return [];
    }

    private function metaOf(array $payload): array
    {
        return data_get($payload, 'meta') ?? data_get($payload, 'data.meta') ?? [];
    }
}
