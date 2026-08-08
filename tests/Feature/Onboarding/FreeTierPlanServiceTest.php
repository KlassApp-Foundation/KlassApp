<?php

namespace Tests\Feature\Onboarding;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\FreeTierPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FreeTierPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Freemium Test School',
            'email' => 'freemium@test.sch.ug',
            'phone' => '+256700900900',
            'slug' => 'freemium-test-school',
            'status' => 1,
            'curriculum' => 'cambridge', // non-UNEB → no UNEB-centre step required
            'registration_country' => 'Kenya', // non-Uganda → EMIS not required
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Freemium Admin',
            'email' => 'admin@freemium.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    private function completeAllContentExceptPlan(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $phase = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => '1']);
        $section = Section::create(['school_id' => $this->school->id, 'name' => 'P1', 'status' => '1']);
        $link = StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'standard_id' => $phase->id, 'section_id' => $section->id, 'status' => '1',
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'standard_id' => $phase->id, 'section_id' => $section->id, 'name' => 'Maths',
        ]);
        $teacher = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => 5, 'name' => 'T', 'email' => 't@freemium.sch.ug',
            'password' => bcrypt('x'), 'status' => 'active', 'email_verified' => 1,
        ]);
        Teacherlink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id,
            'standardLink_id' => $link->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
        ]);
        AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1',
            'starts_on' => now()->startOfYear(), 'ends_on' => now()->startOfYear()->addMonths(4), 'status' => 'current',
        ]);
        FeesCategories::create(['school_id' => $this->school->id, 'standard_id' => $phase->id, 'name' => 'Tuition', 'amount' => 1000]);
        WhatsAppUser::create([
            'phone' => '+256700111000', 'user_id' => $this->admin->id, 'school_id' => $this->school->id,
            'verified_at' => now(), 'opted_in' => true,
        ]);
    }

    private function seedFreemium(int $students = 0, int $users = 0): Plan
    {
        return Plan::create([
            'name' => 'Freemium', 'display_name' => 'Freemium', 'cycle' => 30,
            'no_of_students' => $students, 'no_of_users' => $users, 'amount' => 0,
            'order' => 1, 'is_active' => 1,
        ]);
    }

    /** @test */
    public function assigns_freemium_when_content_complete_and_no_plan(): void
    {
        $this->completeAllContentExceptPlan();
        $plan = $this->seedFreemium();

        $result = app(FreeTierPlanService::class)->assignIfEligible($this->school->fresh(), $this->admin->id);

        $this->assertNotNull($result);
        $this->assertDatabaseHas('current_plans', [
            'school_id' => $this->school->id,
            'plan_id' => $plan->id,
        ]);
    }

    /** @test */
    public function does_not_assign_when_content_incomplete(): void
    {
        // Only some content — no fees/whatsapp etc.
        $this->seedFreemium();

        $result = app(FreeTierPlanService::class)->assignIfEligible($this->school->fresh(), $this->admin->id);

        $this->assertNull($result);
        $this->assertDatabaseMissing('current_plans', ['school_id' => $this->school->id]);
    }

    /** @test */
    public function never_overwrites_an_existing_current_plan(): void
    {
        $this->completeAllContentExceptPlan();
        $existing = $this->seedFreemium();
        CurrentPlan::create(['school_id' => $this->school->id, 'plan_id' => $existing->id, 'status' => 'running']);

        $result = app(FreeTierPlanService::class)->assignIfEligible($this->school->fresh(), $this->admin->id);

        $this->assertNull($result, 'Must never touch a school that already has a CurrentPlan.');
        $this->assertSame(1, CurrentPlan::where('school_id', $this->school->id)->count());
    }

    /** @test */
    public function does_not_assign_a_plan_that_would_block_current_usage(): void
    {
        // Existing "unlimited" school that is already larger than the free tier.
        $this->completeAllContentExceptPlan();
        $this->seedFreemium(students: 2); // free tier caps at 2 students

        User::factory()->count(5)->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
        ]);

        $result = app(FreeTierPlanService::class)->assignIfEligible($this->school->fresh(), $this->admin->id);

        $this->assertNull($result, 'A school over the free-tier limit must stay plan-less (unlimited), never be shrunk.');
        $this->assertDatabaseMissing('current_plans', ['school_id' => $this->school->id]);
    }

    /** @test */
    public function falls_back_to_smallest_fitting_plan_when_no_freemium_named(): void
    {
        // No plan literally named "Freemium" — service should still pick the
        // smallest active tier that fits the (zero) student count.
        $this->completeAllContentExceptPlan();

        $growth = Plan::create([
            'name' => 'Growth', 'display_name' => 'Growth', 'cycle' => 30,
            'no_of_students' => 200, 'no_of_users' => 50, 'amount' => 0,
            'order' => 1, 'is_active' => 1,
        ]);

        $result = app(FreeTierPlanService::class)->assignIfEligible($this->school->fresh(), $this->admin->id);

        $this->assertNotNull($result);
        $this->assertSame($growth->id, (int) $result->plan_id);
    }
}
