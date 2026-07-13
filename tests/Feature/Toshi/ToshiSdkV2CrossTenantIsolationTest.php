<?php

namespace Tests\Feature\Toshi;

use App\Models\User;
use App\AiAgents\Tools\GetStudentCountTool;
use App\AiAgents\Tools\ListTeachersTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToshiSdkV2CrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolA;
    private int $schoolB;
    private User $adminA;
    private User $adminB;
    private int $studentAId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $now = now();

        $this->schoolA = DB::table('schools')->insertGetId([
            'name' => 'School A', 'slug' => 'school-a',
            'email' => 'a@test.sch.ug', 'phone' => '+256700000001',
            'status' => 1, 'registration_country' => 'Uganda',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->schoolB = DB::table('schools')->insertGetId([
            'name' => 'School B', 'slug' => 'school-b',
            'email' => 'b@test.sch.ug', 'phone' => '+256700000002',
            'status' => 1, 'registration_country' => 'Uganda',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA, 'usergroup_id' => 3,
            'email' => 'admin.a@test.sch.ug',
        ]);
        $this->adminB = User::factory()->create([
            'school_id' => $this->schoolB, 'usergroup_id' => 3,
            'email' => 'admin.b@test.sch.ug',
        ]);

        // Seed School A: 1 student + 1 teacher
        $this->studentAId = DB::table('users')->insertGetId([
            'school_id' => $this->schoolA, 'usergroup_id' => 6,
            'name' => 'Alice A', 'email' => 'alice.a@test.sch.ug',
            'password' => bcrypt('password'), 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('users')->insert([
            'school_id' => $this->schoolA, 'usergroup_id' => 5,
            'name' => 'Teacher A', 'email' => 'teacher.a@test.sch.ug',
            'password' => bcrypt('password'), 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        // Seed School B: 1 student + 1 teacher
        DB::table('users')->insertGetId([
            'school_id' => $this->schoolB, 'usergroup_id' => 6,
            'name' => 'Bob B', 'email' => 'bob.b@test.sch.ug',
            'password' => bcrypt('password'), 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('users')->insert([
            'school_id' => $this->schoolB, 'usergroup_id' => 5,
            'name' => 'Teacher B', 'email' => 'teacher.b@test.sch.ug',
            'password' => bcrypt('password'), 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /** @test */
    public function student_count_is_scoped_to_school()
    {
        $this->actingAs($this->adminA);
        $tool = app(GetStudentCountTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([]));
        $this->assertStringContainsString('**1**', $result,
            'Admin A should see 1 student in their school');

        $this->actingAs($this->adminB);
        $resultB = $tool->handle(new \Laravel\Ai\Tools\Request([]));
        $this->assertStringContainsString('**1**', $resultB,
            'Admin B should also see 1 student (not 2 — would indicate leak)');
    }

    /** @test */
    public function list_teachers_returns_only_own_school()
    {
        $this->actingAs($this->adminA);
        $tool = app(ListTeachersTool::class);
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([]));
        $this->assertStringContainsString('Teacher A', $result);
        $this->assertStringNotContainsString('Teacher B', $result,
            'Admin A must not see School B teachers');

        $this->actingAs($this->adminB);
        $resultB = $tool->handle(new \Laravel\Ai\Tools\Request([]));
        $this->assertStringContainsString('Teacher B', $resultB);
        $this->assertStringNotContainsString('Teacher A', $resultB,
            'Admin B must not see School A teachers');
    }
}
