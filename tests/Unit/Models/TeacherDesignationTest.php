<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherDesignationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert(['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('schools')->insert(['id' => 1, 'name' => 'Test School', 'slug' => 'test-school', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function makeUser(?array $designations = null): User
    {
        return User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => 1,
            'password' => bcrypt('password'),
            'teacher_designations' => $designations,
        ]);
    }

    public function test_has_designation_returns_true_for_assigned_role(): void
    {
        $user = $this->makeUser(['principal', 'leave_checker', 'class_coordinator']);

        $this->assertTrue($user->hasDesignation('principal'));
        $this->assertTrue($user->hasDesignation('leave_checker'));
        $this->assertTrue($user->hasDesignation('class_coordinator'));
    }

    public function test_has_designation_returns_false_for_unassigned_role(): void
    {
        $user = $this->makeUser(['principal']);

        $this->assertFalse($user->hasDesignation('leave_applier'));
        $this->assertFalse($user->hasDesignation('transport_coordinator'));
    }

    public function test_has_designation_returns_false_when_column_is_null(): void
    {
        $user = $this->makeUser(null);
        $this->assertFalse($user->hasDesignation('principal'));
    }

    public function test_has_designation_returns_false_when_column_is_empty_array(): void
    {
        $user = $this->makeUser([]);
        $this->assertFalse($user->hasDesignation('principal'));
    }

    public function test_add_designation_appends_new_role(): void
    {
        $user = $this->makeUser(['principal']);
        $user->addDesignation('leave_applier');
        $user->refresh();

        $this->assertTrue($user->hasDesignation('leave_applier'));
        $this->assertCount(2, $user->teacher_designations);
    }

    public function test_add_designation_does_not_duplicate_existing_role(): void
    {
        $user = $this->makeUser(['principal']);
        $user->addDesignation('principal');
        $user->refresh();

        $this->assertCount(1, $user->teacher_designations);
    }

    public function test_add_designation_initializes_null_column(): void
    {
        $user = $this->makeUser(null);
        $user->addDesignation('leave_applier');
        $user->refresh();

        $this->assertTrue($user->hasDesignation('leave_applier'));
        $this->assertCount(1, $user->teacher_designations);
    }

    public function test_remove_designation_removes_existing_role(): void
    {
        $user = $this->makeUser(['principal', 'leave_checker']);
        $user->removeDesignation('principal');
        $user->refresh();

        $this->assertFalse($user->hasDesignation('principal'));
        $this->assertEquals(['leave_checker'], $user->teacher_designations);
    }

    public function test_remove_designation_does_nothing_for_unassigned_role(): void
    {
        $user = $this->makeUser(['principal']);
        $user->removeDesignation('nonexistent');
        $user->refresh();

        $this->assertCount(1, $user->teacher_designations);
    }

    public function test_remove_designation_handles_null_column(): void
    {
        $user = $this->makeUser(null);
        $user->removeDesignation('principal');
        $user->refresh();

        $this->assertNull($user->teacher_designations);
    }

    public function test_teacher_designations_is_cast_to_array(): void
    {
        $user = $this->makeUser(['principal']);
        $this->assertIsArray($user->teacher_designations);
    }
}
