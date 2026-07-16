<?php

namespace Tests\Feature\Toshi;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToshiVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        \Illuminate\Support\Facades\DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    /** @test */
    public function verify_returns_false_when_record_does_not_exist()
    {
        $user = User::factory()->create(['usergroup_id' => 3, 'school_id' => 999]);
        $this->actingAs($user);

        $tool = app(\App\AiAgents\Tools\RecordAttendanceTool::class);
        $request = new \Laravel\Ai\Tools\Request([
            'student' => 'Nobody',
            'date' => '2026-07-15',
            'status' => 'present',
        ]);

        $result = $tool->verify($request);

        $this->assertFalse($result['verified']);
        $this->assertStringContainsStringIgnoringCase('not found', $result['message']);
    }

    /** @test */
    public function verify_returns_true_after_successful_attendance_write()
    {
        $school = $this->createSchool('Verification School');
        $user = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
        ]);
        $this->actingAs($user);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $standard = Standard::create([
            'name' => 'P5',
            'school_id' => $school->id,
            'order' => 1,
        ]);

        $section = Section::create([
            'name' => 'P5 East',
            'school_id' => $school->id,
            'standard_id' => $standard->id,
        ]);

        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $school->id,
            'name' => 'Alice Student',
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'user_id' => $student->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => 1,
        ]);

        $args = [
            'student' => $student->name,
            'date' => '2026-07-15',
            'status' => 'present',
            'session' => 'forenoon',
        ];

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('schoolId', $school->id);
        $component->set('pendingToolConfirm', [
            'tool' => 'toolRecordAttendance',
            'args' => $args,
        ]);

        $component->call('confirmYes');

        $this->assertDatabaseHas('attendances', [
            'school_id' => $school->id,
            'user_id' => $student->id,
            'status' => 1,
        ]);

        $messages = $component->get('messages');
        $lastMessage = end($messages);
        $this->assertNotNull($lastMessage);
        $this->assertStringStartsWith('✅', $lastMessage['text']);
    }

    /** @test */
    public function verification_warning_appears_when_verify_fails_after_write()
    {
        $school = $this->createSchool('Verification Fail School');
        $user = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
        ]);
        $this->actingAs($user);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $standard = Standard::create([
            'name' => 'P6',
            'school_id' => $school->id,
            'order' => 2,
        ]);

        $section = Section::create([
            'name' => 'P6 West',
            'school_id' => $school->id,
            'standard_id' => $standard->id,
        ]);

        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $school->id,
            'name' => 'Bob Student',
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'user_id' => $student->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => 1,
        ]);

        $args = [
            'student' => $student->name,
            'date' => '2026-07-15',
            'status' => 'present',
            'session' => 'forenoon',
        ];

        $component = Livewire::test(\App\Livewire\AgentToshi::class);
        $component->set('schoolId', $school->id);
        $component->set('pendingToolConfirm', [
            'tool' => 'toolRecordAttendance',
            'args' => $args,
        ]);

        Attendance::created(function ($attendance) use ($school, $student) {
            Attendance::where('school_id', $school->id)
                ->where('user_id', $student->id)
                ->where('status', 1)
                ->delete();
        });

        $component->call('confirmYes');

        $messages = $component->get('messages');
        $lastMessage = end($messages);
        $this->assertNotNull($lastMessage);
        $this->assertStringStartsWith('⚠️', $lastMessage['text']);
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
}
