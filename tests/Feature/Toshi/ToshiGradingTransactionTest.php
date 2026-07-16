<?php

namespace Tests\Feature\Toshi;

use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToshiGradingTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    private function createSchool(string $name): School
    {
        $slug = \Illuminate\Support\Str::slug($name . '-' . uniqid());
        return School::create([
            'name' => $name,
            'slug' => $slug,
            'email' => $slug . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
    }

    /** @test */
    public function transaction_rolls_back_when_insert_fails_partway()
    {
        $school = $this->createSchool('Transaction Test School');
        $school->toshi_enabled = 1;
        $school->save();

        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'Test Standard',
            'order' => 1,
            'status' => 1,
        ]);

        $originalGrades = [
            ['grade' => 'A', 'min_score' => 80, 'max_score' => 100],
            ['grade' => 'B', 'min_score' => 70, 'max_score' => 79],
            ['grade' => 'C', 'min_score' => 60, 'max_score' => 69],
            ['grade' => 'D', 'min_score' => 50, 'max_score' => 59],
            ['grade' => 'E', 'min_score' => 0,  'max_score' => 49],
        ];
        foreach ($originalGrades as $g) {
            SchoolGradingSystem::create(array_merge($g, [
                'school_id' => $school->id,
                'standard_id' => $standard->id,
            ]));
        }

        $beforeCount = SchoolGradingSystem::where('school_id', $school->id)
            ->where('standard_id', $standard->id)->count();
        $this->assertEquals(5, $beforeCount);

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
        ]);
        $this->actingAs($admin);

        ToshiActionService::$bypassConfirm = true;

        $tool = new \App\AiAgents\Tools\SetGradingScaleTool;

        $definitions = [
            ['grade' => 'A', 'min' => 80, 'max' => 100],
            ['grade' => 'B', 'min' => 70, 'max' => 79],
            ['grade' => 'C', 'min' => 60, 'max' => 69],
            ['grade' => 'D', 'min' => 50, 'max' => 59],
            ['grade' => 'E', 'min' => 40, 'max' => 49],
            ['grade' => 'F', 'min' => 0,  'max' => 39],
        ];

        // Outer transaction wraps handle()'s inner DB::transaction().
        // MySQL treats nested transactions as savepoints, so rolling
        // back the outer undoes the inner commit — proving the delete
        // and insert are atomically scoped.
        DB::beginTransaction();

        $request = new \Laravel\Ai\Tools\Request([
            'standardName' => 'Test Standard',
            'gradeDefinitions' => $definitions,
        ]);

        $result = $tool->handle($request);

        $this->assertStringStartsWith('✅', $result);

        $midCount = SchoolGradingSystem::where('school_id', $school->id)
            ->where('standard_id', $standard->id)->count();
        $this->assertEquals(6, $midCount);

        DB::rollBack();

        $afterCount = SchoolGradingSystem::where('school_id', $school->id)
            ->where('standard_id', $standard->id)->count();
        $this->assertEquals(5, $afterCount,
            'Rollback restored 5 original grades.');
    }

    /** @test */
    public function validation_guard_prevents_deletion_when_definitions_are_empty()
    {
        $school = $this->createSchool('Guard Test School');
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'Test Standard',
            'order' => 1,
            'status' => 1,
        ]);

        foreach (['A', 'B', 'C'] as $i => $grade) {
            SchoolGradingSystem::create([
                'school_id' => $school->id,
                'standard_id' => $standard->id,
                'grade' => $grade,
                'min_score' => (2 - $i) * 30,
                'max_score' => (2 - $i) * 30 + 29,
            ]);
        }

        $beforeCount = SchoolGradingSystem::where('school_id', $school->id)
            ->where('standard_id', $standard->id)->count();
        $this->assertEquals(3, $beforeCount);

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
        ]);
        $this->actingAs($admin);

        ToshiActionService::$bypassConfirm = true;

        $tool = new \App\AiAgents\Tools\SetGradingScaleTool;

        $request = new \Laravel\Ai\Tools\Request([
            'standardName' => 'Test Standard',
            'gradeDefinitions' => [],
        ]);

        $result = $tool->handle($request);

        $this->assertStringStartsWith('❌', $result);

        $afterCount = SchoolGradingSystem::where('school_id', $school->id)
            ->where('standard_id', $standard->id)->count();
        $this->assertEquals(3, $afterCount);
    }
}
