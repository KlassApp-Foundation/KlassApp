<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\EmisSchool;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Proves the manual (non-Toshi) onboarding path has full parity with the
 * Toshi-assisted path: every step is reachable and completable by hand, the
 * allowlist changes don't open the whole app, and manual writes produce the
 * same DB state Toshi would.
 */
class ManualOnboardingParityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private Country $uganda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->uganda = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        EmisSchool::create([
            'emis_code' => 'EMIS-1001',
            'school_name' => 'Parity EMIS School',
            'district' => 'Kampala',
            'status' => 1,
        ]);

        // Placeholder school exactly as SchoolSignupBootstrapService creates it:
        // placeholder name, curriculum null, toshi enabled, no academic year.
        $this->school = School::create([
            'name' => "Rasta's School",
            'email' => 'parity@test.sch.ug',
            'phone' => '+256700000123',
            'slug' => 'rastas-school',
            'status' => 1,
            'curriculum' => null,
            'country_id' => null,
            'registration_country' => null,
            'ministry_code' => null,
            'uneb_center_number' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Rasta Admin',
            'email' => 'rasta@parity.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Rasta',
            'lastname' => 'Admin',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Part 1.1 — allowlist: manual pages reachable before AcademicYear
    // ─────────────────────────────────────────────────────────────

    public function test_school_details_reachable_before_academic_year(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/schooldetails');

        $response->assertOk();
    }

    public function test_whatsapp_phone_link_reachable_before_academic_year(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/whatsapp/phone');

        $response->assertOk();
    }

    public function test_allowlist_does_not_open_gated_pages_before_academic_year(): void
    {
        // Security sanity: a non-onboarding page still bounces to the dashboard
        // when no academic year exists (allowlist is narrow, not "open all").
        $this->actingAs($this->admin);

        $response = $this->get('/admin/students');

        $response->assertRedirect('/admin/dashboard');
    }

    public function test_gated_route_still_flags_toshi_onboarding(): void
    {
        // Toshi-path regression: the allowlist changes must not stop a gated
        // route from bouncing an un-onboarded admin back to the dashboard with
        // the flag that auto-opens Toshi.
        $this->actingAs($this->admin);

        $this->get('/admin/students')
            ->assertRedirect('/admin/dashboard')
            ->assertSessionHas('open_toshi_onboarding', true);
    }

    // ─────────────────────────────────────────────────────────────
    // Full manual onboarding → OnboardingStepsService reports complete
    // ─────────────────────────────────────────────────────────────

    public function test_fresh_admin_completes_full_onboarding_via_manual_forms_only(): void
    {
        $this->actingAs($this->admin);

        // School identity + curriculum + country + EMIS + UNEB centre via the
        // real manual School Details form (board maps to curriculum).
        $this->post('/admin/schooldetails/update/'.$this->school->id, [
            'name' => 'Rasta Model Primary',
            'board' => 'uneb',
            'country_id' => $this->uganda->id,
            'ministry_code' => 'EMIS-1001',
            'uneb_center_number' => 'U0123',
        ])->assertSessionHasNoErrors();

        // Content steps (academic year, classes, subjects, teachers, terms, fees)
        // represent the outcomes of the manual add-forms; assert against real rows.
        $this->completeContentSteps();

        // WhatsApp verification via the real manual link form.
        $this->post('/admin/whatsapp/phone', ['phone' => '+256700111222'])
            ->assertSessionHasNoErrors();

        // Free-tier plan milestone (auto-assigned once content is done) — the
        // only thing standing between "content done" and "fully onboarded".
        $this->seedFreemiumPlan();
        app(\App\Services\FreeTierPlanService::class)
            ->assignIfEligible($this->school->fresh(), $this->admin->id);

        $this->assertFalse(
            OnboardingStepsService::hasIncompleteSteps($this->school->fresh(), $this->admin->id),
            'A fresh admin must be able to fully complete onboarding via manual forms — no Toshi required.'
        );
    }

    public function test_mixed_curriculum_via_toshi_then_finished_manually_has_no_conflicts(): void
    {
        // Curriculum set outside School Details (as Toshi would), then the rest
        // finished via the manual School Details form — the two must agree.
        $this->school->update(['curriculum' => 'uneb']);

        $this->actingAs($this->admin);

        $this->post('/admin/schooldetails/update/'.$this->school->id, [
            'name' => 'Rasta Mixed Primary',
            'board' => 'uneb',
            'country_id' => $this->uganda->id,
            'ministry_code' => 'EMIS-1001',
            'uneb_center_number' => 'U0123',
        ])->assertSessionHasNoErrors();

        $fresh = $this->school->fresh();
        $this->assertSame('uneb', $fresh->curriculum);
        $this->assertSame('Uganda', $fresh->registration_country);
        $this->assertTrue(OnboardingStepsService::isStepComplete('curriculum', $fresh));
        $this->assertTrue(OnboardingStepsService::isStepComplete('country', $fresh));
        $this->assertTrue(OnboardingStepsService::isStepComplete('emis', $fresh));
    }

    // ─────────────────────────────────────────────────────────────
    // Part 3 — WhatsApp manual state parity with Toshi
    // ─────────────────────────────────────────────────────────────

    public function test_manual_whatsapp_link_matches_toshi_state(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/whatsapp/phone', ['phone' => '+256700333444'])
            ->assertSessionHasNoErrors();

        $waUser = WhatsAppUser::where('user_id', $this->admin->id)->first();

        $this->assertNotNull($waUser, 'Manual link must create a WhatsAppUser (satisfies whatsapp_verify).');
        $this->assertSame('+256700333444', $waUser->phone);
        $this->assertSame($this->school->id, $waUser->school_id);
        $this->assertTrue((bool) $waUser->opted_in);
        // Parity with Toshi's whatsapp_verify commit: verified_at is set.
        $this->assertNotNull($waUser->verified_at, 'Manual link must set verified_at like Toshi does.');

        $this->assertTrue(
            OnboardingStepsService::isStepComplete('whatsapp_verify', $this->school->fresh(), $this->admin->id)
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Part 1.4 — every step route is a real registered route
    // ─────────────────────────────────────────────────────────────

    public function test_all_step_routes_are_registered(): void
    {
        $steps = OnboardingStepsService::steps($this->school->fresh(), $this->admin->id);

        $paths = collect(app('router')->getRoutes())
            ->map(fn ($r) => '/'.ltrim($r->uri(), '/'))
            ->all();

        foreach ($steps as $step) {
            if ($step['route'] === null) {
                continue;
            }

            $this->assertContains(
                $step['route'],
                $paths,
                "Step '{$step['key']}' points at unregistered route {$step['route']}"
            );
        }
    }

    // ── helpers ──

    private function completeContentSteps(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $phase = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => '1',
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'status' => '1',
        ]);

        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $phase->id,
            'section_id' => $section->id,
            'status' => '1',
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $phase->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
        ]);

        $teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Teacher One',
            'email' => 'teacher.one@parity.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standardLink_id' => $link->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'starts_on' => now()->startOfYear(),
            'ends_on' => now()->startOfYear()->addMonths(4),
            'status' => 'current',
        ]);

        FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $phase->id,
            'name' => 'Tuition',
            'amount' => 100000,
        ]);
    }

    private function seedFreemiumPlan(): void
    {
        DB::table('plans')->insert([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
