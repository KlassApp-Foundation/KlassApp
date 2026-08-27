<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StreamingTransitionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private int $academicYearId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin'],
            ['id' => 3, 'name' => 'schooladmin'],
            ['id' => 5, 'name' => 'teacher'],
            ['id' => 6, 'name' => 'student'],
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

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary 1',
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

        // Create a no-stream StandardLink (simulating an existing direct class)
        $originalLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYearId,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => null,
            'no_of_students' => 0,
            'status' => 1,
        ]);

        // Create 3 students already enrolled on the no-stream link
        $students = [];
        $names = ['Alice Mwangi', 'Bob Okello', 'Charlie Nabunya'];
        foreach ($names as $i => $name) {
            $student = User::create([
                'school_id' => $this->school->id,
                'usergroup_id' => 6,
                'name' => $name,
                'email' => "student{$i}@test.sch.ug",
                'password' => bcrypt('password'),
                'status' => 'active',
                'email_verified' => 1,
                'registration_number' => sprintf('KLS%03d%04d', $this->school->id, $i + 1),
            ]);

            StudentAcademic::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $this->academicYearId,
                'user_id' => $student->id,
                'standardLink_id' => $originalLink->id,
            ]);

            $students[] = $student;
        }

        ToshiActionService::$bypassConfirm = true;
    }

    /** @test */
    public function streaming_transition_round_trip(): void
    {
        $this->actingAs($this->admin);

        $createTool = app(\App\AiAgents\Tools\CreateStreamTool::class);
        $assignTool = app(\App\AiAgents\Tools\AssignStudentsToStreamTool::class);

        // Step 1: CreateStreamTool adds streams East and West to the direct class
        $createResult = $createTool->handle(new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streams' => ['East', 'West'],
        ]));
        $this->assertStringStartsWith('✅', $createResult, 'CreateStreamTool must allow adding streams to a direct class');

        // Step 2: Verify the 3 students are still on the old no-stream link (not broken)
        $section = Section::where('school_id', $this->school->id)->where('name', 'Primary 1')->first();
        $nullLink = StandardLink::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->whereNull('stream')
            ->first();
        $this->assertNotNull($nullLink, 'No-stream link must still exist');

        $studentsOnNullLink = StudentAcademic::where('school_id', $this->school->id)
            ->where('standardLink_id', $nullLink->id)
            ->where('academic_year_id', $this->academicYearId)
            ->count();
        $this->assertEquals(3, $studentsOnNullLink, 'All 3 students must still be on the old no-stream link');

        // Step 3: Get the new stream links
        $eastLink = StandardLink::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->where('stream', 'East')
            ->first();
        $westLink = StandardLink::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->where('stream', 'West')
            ->first();

        $this->assertNotNull($eastLink, 'East stream must exist');
        $this->assertNotNull($westLink, 'West stream must exist');

        // Step 4: AssignStudentsToStreamTool sorts 3 students across East and West
        $assignEastResult = $assignTool->handle(new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'East',
            'students' => ['Alice Mwangi', 'Bob Okello'],
        ]));
        $this->assertStringStartsWith('✅', $assignEastResult, 'Must assign Alice and Bob to East');

        $assignWestResult = $assignTool->handle(new \Laravel\Ai\Tools\Request([
            'className' => 'Primary 1',
            'streamName' => 'West',
            'students' => ['Charlie Nabunya'],
        ]));
        $this->assertStringStartsWith('✅', $assignWestResult, 'Must assign Charlie to West');

        // Step 5: Assert final state

        // Alice → East
        $aliceAcademic = StudentAcademic::where('school_id', $this->school->id)
            ->whereHas('user', fn($q) => $q->where('name', 'Alice Mwangi'))
            ->where('academic_year_id', $this->academicYearId)
            ->first();
        $this->assertEquals($eastLink->id, $aliceAcademic->standardLink_id, 'Alice must be on East');

        // Bob → East
        $bobAcademic = StudentAcademic::where('school_id', $this->school->id)
            ->whereHas('user', fn($q) => $q->where('name', 'Bob Okello'))
            ->where('academic_year_id', $this->academicYearId)
            ->first();
        $this->assertEquals($eastLink->id, $bobAcademic->standardLink_id, 'Bob must be on East');

        // Charlie → West
        $charlieAcademic = StudentAcademic::where('school_id', $this->school->id)
            ->whereHas('user', fn($q) => $q->where('name', 'Charlie Nabunya'))
            ->where('academic_year_id', $this->academicYearId)
            ->first();
        $this->assertEquals($westLink->id, $charlieAcademic->standardLink_id, 'Charlie must be on West');

        // No students left on the old no-stream link
        $remainingOnNull = StudentAcademic::where('school_id', $this->school->id)
            ->where('standardLink_id', $nullLink->id)
            ->where('academic_year_id', $this->academicYearId)
            ->count();
        $this->assertEquals(0, $remainingOnNull, 'No students must remain on the old no-stream link');
    }
}
