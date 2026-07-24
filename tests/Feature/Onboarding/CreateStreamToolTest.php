<?php

namespace Tests\Feature\Onboarding;

use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateStreamToolTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private Standard $phase;
    private int $academicYearId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Test School',
            'email' => 'test@test.sch.ug',
            'phone' => '0700000000',
            'slug' => 'test-school',
            'status' => 1,
            'curriculum' => 'uneb',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'School Admin',
            'email' => 'admin@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $this->phase = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'description' => 'Current Academic Year',
        ]);
        $this->academicYearId = $academicYear->id;

        ToshiActionService::$bypassConfirm = true;
    }

    /** @test */
    public function creates_direct_class_with_no_streams(): void
    {
        $this->actingAs($this->admin);

        $tool = app(\App\AiAgents\Tools\CreateStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
        ]);

        $result = $tool->handle($request);

        $this->assertStringStartsWith('✅', $result);

        $section = Section::where('school_id', $this->school->id)->where('name', 'Primary 1')->first();
        $this->assertNotNull($section);

        $link = StandardLink::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->where('academic_year_id', $this->academicYearId)
            ->whereNull('stream')
            ->first();
        $this->assertNotNull($link, 'Direct class must create a StandardLink with stream = null');
        $this->assertEquals($this->phase->id, $link->standard_id);
    }

    /** @test */
    public function creates_streamed_class(): void
    {
        $this->actingAs($this->admin);

        $tool = app(\App\AiAgents\Tools\CreateStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streams' => ['East', 'West'],
        ]);

        $result = $tool->handle($request);

        $this->assertStringStartsWith('✅', $result);

        $section = Section::where('school_id', $this->school->id)->where('name', 'Primary 1')->first();
        $this->assertNotNull($section);

        $links = StandardLink::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->where('academic_year_id', $this->academicYearId)
            ->get();

        $this->assertCount(2, $links);
        $this->assertNotNull($links->firstWhere('stream', 'East'));
        $this->assertNotNull($links->firstWhere('stream', 'West'));

        // No null-stream link should exist
        $nullStream = $links->firstWhere('stream', null);
        $this->assertNull($nullStream, 'Streamed class must not have a null-stream StandardLink');
    }

    /** @test */
    public function multi_phase_school_without_phase_param_returns_ambiguity_error(): void
    {
        $this->actingAs($this->admin);

        // Add a second phase to create ambiguity
        Standard::create([
            'school_id' => $this->school->id,
            'name' => 'secondary',
            'order' => 2,
            'status' => 1,
        ]);

        $tool = app(\App\AiAgents\Tools\CreateStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Senior 1',
        ]);

        $result = $tool->handle($request);

        $this->assertStringContainsString('multiple phases', strtolower($result));
        $this->assertStringStartsWith('❌', $result, 'Tool must not guess which phase when ambiguous');
    }

    /** @test */
    public function multi_phase_school_with_phase_param_lands_on_correct_standard(): void
    {
        $this->actingAs($this->admin);

        Standard::create([
            'school_id' => $this->school->id,
            'name' => 'secondary',
            'order' => 2,
            'status' => 1,
        ]);

        $tool = app(\App\AiAgents\Tools\CreateStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Senior 1',
            'phase' => 'secondary',
        ]);

        $result = $tool->handle($request);

        $this->assertStringStartsWith('✅', $result);

        $section = Section::where('school_id', $this->school->id)->where('name', 'Senior 1')->first();
        $this->assertNotNull($section);

        $secondaryStandard = Standard::where('school_id', $this->school->id)->where('name', 'secondary')->first();
        $link = StandardLink::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->first();
        $this->assertEquals($secondaryStandard->id, $link->standard_id);
    }

    /** @test */
    public function guard_blocks_direct_class_on_top_of_streamed_class(): void
    {
        $this->actingAs($this->admin);

        // First create a streamed class
        $tool = app(\App\AiAgents\Tools\CreateStreamTool::class);
        $tool->handle(new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streams' => ['East', 'West'],
        ]));

        // Try to turn it into a direct class
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
        ]));

        $this->assertStringContainsString('already has streams', strtolower($result));
        $this->assertStringStartsWith('❌', $result);
    }

    /** @test */
    public function guard_allows_streaming_previously_direct_class(): void
    {
        $this->actingAs($this->admin);

        // First create a direct class
        $tool = app(\App\AiAgents\Tools\CreateStreamTool::class);
        $tool->handle(new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
        ]));

        // Now add streams to it — should succeed (AssignStudentsToStreamTool exists)
        $result = $tool->handle(new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streams' => ['East', 'West'],
        ]));

        $this->assertStringStartsWith('✅', $result, 'Adding streams to a direct class must be allowed');

        $section = Section::where('school_id', $this->school->id)->where('name', 'Primary 1')->first();

        // Original null-stream link must still exist
        $nullLink = StandardLink::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->whereNull('stream')
            ->first();
        $this->assertNotNull($nullLink, 'Pre-existing no-stream link must be untouched');

        // New stream links must exist
        $eastLink = StandardLink::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->where('stream', 'East')
            ->first();
        $this->assertNotNull($eastLink, 'New East stream link must be created');

        $westLink = StandardLink::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->where('stream', 'West')
            ->first();
        $this->assertNotNull($westLink, 'New West stream link must be created');
    }

    /** @test */
    public function verify_matches_db_state(): void
    {
        $this->actingAs($this->admin);

        $tool = app(\App\AiAgents\Tools\CreateStreamTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streams' => ['East', 'West'],
        ]);

        $tool->handle($request);

        $verifyResult = $tool->verify($request);

        $this->assertTrue($verifyResult['verified']);
    }
}