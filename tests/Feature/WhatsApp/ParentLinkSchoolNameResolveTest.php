<?php

namespace Tests\Feature\WhatsApp;

use App\Models\AcademicYear;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\WhatsApp\ParentLinkRequestService;
use App\States\Approval\Pending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParentLinkSchoolNameResolveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
        ]);

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1416403124879552',
            'services.whatsapp.business_api_version' => 'v21.0',
        ]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_normalize_collapses_spaces_in_school_name(): void
    {
        $service = app(ParentLinkRequestService::class);

        $this->assertSame(
            'greenfieldprimaryschool',
            $service->normalizeSchoolNameKey('Green field primary school')
        );
        $this->assertSame(
            'greenfieldprimaryschool',
            $service->normalizeSchoolNameKey('Greenfield Primary School')
        );
    }

    public function test_resolve_school_by_name_matches_spaced_variant(): void
    {
        $school = School::create([
            'name' => 'Greenfield Primary School',
            'email' => 'greenfield@test.sch.ug',
            'status' => 1,
        ]);

        $resolved = app(ParentLinkRequestService::class)
            ->resolveSchoolByName('Green field primary school');

        $this->assertNotNull($resolved);
        $this->assertSame($school->id, $resolved->id);
    }

    public function test_flow_submission_with_spaced_school_name_creates_approval(): void
    {
        $school = School::create([
            'name' => 'Greenfield Primary School',
            'email' => 'greenfield2@test.sch.ug',
            'status' => 1,
        ]);
        $link = $this->createStandardLink($school, 'Primary Seven');
        $student = $this->createStudent($school, $link, 'Grace Auma');

        $request = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700000001',
            [
                'parent_name' => 'Sunday Johnson',
                'child_name' => 'Grace Auma',
                'child_class' => 'P.7',
                'school_name' => 'Green field primary school',
            ],
        );

        $this->assertTrue($request->wasRecentlyCreated);
        $this->assertSame($school->id, $request->school_id);
        $this->assertSame($student->id, $request->suggested_student_id);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $request->id,
        ]);
        $this->assertTrue(
            Approval::query()
                ->where('approvable_id', $request->id)
                ->where('approvable_type', ParentLinkRequest::class)
                ->first()
                ?->state
                ->equals(Pending::class)
        );
    }

    public function test_repair_unresolved_pending_sets_school_and_creates_approval(): void
    {
        $school = School::create([
            'name' => 'Greenfield Primary School',
            'email' => 'greenfield3@test.sch.ug',
            'status' => 1,
        ]);
        $link = $this->createStandardLink($school, 'Primary Seven');
        $student = $this->createStudent($school, $link, 'Grace Auma');

        $orphan = ParentLinkRequest::create([
            'school_id' => null,
            'phone' => '+256700000001',
            'parent_name' => 'Sunday Johnson',
            'child_name' => 'Grace Auma',
            'child_class' => 'P.7',
            'school_name' => 'Green field primary school',
            'status' => 'pending',
            'suggested_student_id' => null,
            'candidate_student_ids' => null,
        ]);

        $this->assertSame(0, $orphan->approvals()->count());

        $stats = app(ParentLinkRequestService::class)->repairUnresolvedPending($orphan->id);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(1, $stats['resolved']);
        $this->assertSame(1, $stats['approvals_created']);
        $this->assertSame(0, $stats['still_unresolved']);

        $orphan->refresh();
        $this->assertSame($school->id, $orphan->school_id);
        $this->assertSame($student->id, $orphan->suggested_student_id);
        $this->assertSame(1, $orphan->approvals()->count());
    }

    public function test_p7_token_matches_primary_seven_section(): void
    {
        $school = School::create([
            'name' => 'Alias Class School',
            'email' => 'alias-class@test.sch.ug',
            'status' => 1,
        ]);
        $link = $this->createStandardLink($school, 'Primary Seven');
        $student = $this->createStudent($school, $link, 'Grace Auma');

        $candidates = app(ParentLinkRequestService::class)
            ->findCandidateStudents('Grace Auma', 'P.7', $school->id);

        $this->assertCount(1, $candidates);
        $this->assertSame($student->id, $candidates->first()->id);
    }

    public function test_repair_command_dry_run_does_not_write(): void
    {
        School::create([
            'name' => 'Greenfield Primary School',
            'email' => 'greenfield4@test.sch.ug',
            'status' => 1,
        ]);

        $orphan = ParentLinkRequest::create([
            'school_id' => null,
            'phone' => '+256700000001',
            'parent_name' => 'Parent',
            'child_name' => 'Child',
            'child_class' => 'P.1',
            'school_name' => 'Green field primary school',
            'status' => 'pending',
        ]);

        $this->artisan('whatsapp:repair-parent-link-requests', [
            '--id' => $orphan->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $orphan->refresh();
        $this->assertNull($orphan->school_id);
        $this->assertSame(0, $orphan->approvals()->count());
    }

    // ------------------------------------------------------------------ //
    // Step 4: close-match (typo-tolerant + word-order)
    // ------------------------------------------------------------------ //

    public function test_resolve_school_one_typo_matches(): void
    {
        $school = School::create([
            'name'   => 'Hillcrest Primary Academy',
            'email'  => 'hillcrest-typo@test.sch.ug',
            'status' => 1,
        ]);

        // "Primery" is one substitution (a→e) from "Primary".
        $resolved = app(ParentLinkRequestService::class)
            ->resolveSchoolByName('Hillcrest Primery Academy');

        $this->assertNotNull($resolved);
        $this->assertSame($school->id, $resolved->id);
    }

    public function test_resolve_school_word_order_matches(): void
    {
        $school = School::create([
            'name'   => 'Hillcrest Primary Academy',
            'email'  => 'hillcrest-wordorder@test.sch.ug',
            'status' => 1,
        ]);

        // Words rearranged — word-sorted keys collapse to the same string.
        $resolved = app(ParentLinkRequestService::class)
            ->resolveSchoolByName('Primary Academy Hillcrest');

        $this->assertNotNull($resolved);
        $this->assertSame($school->id, $resolved->id);
    }

    public function test_resolve_school_two_close_schools_ambiguous_returns_null(): void
    {
        // Both schools have the same edit distance (4 insertions) from the input.
        // Input "Greenview Primary" (normalized: "greenviewprimary", 16 chars):
        //   vs "Greenview East Primary" (normalized: "greenvieweastprimary")  → dist 4
        //   vs "Greenview West Primary" (normalized: "greenviewwestprimary")  → dist 4
        // Threshold = max(2, ceil(16 × 0.25)) = 4.  Both within threshold, gap = 0 < 2.
        School::create(['name' => 'Greenview East Primary', 'email' => 'gv-east@test.sch.ug', 'status' => 1]);
        School::create(['name' => 'Greenview West Primary', 'email' => 'gv-west@test.sch.ug', 'status' => 1]);

        $resolved = app(ParentLinkRequestService::class)
            ->resolveSchoolByName('Greenview Primary');

        $this->assertNull($resolved);
    }

    public function test_resolve_school_no_match_returns_null(): void
    {
        School::create(['name' => 'Greenfield Primary School', 'email' => 'gps-nm@test.sch.ug', 'status' => 1]);

        $resolved = app(ParentLinkRequestService::class)
            ->resolveSchoolByName('XYZ Completely Different');

        $this->assertNull($resolved);
    }

    public function test_flow_submission_unresolvable_school_still_creates_request(): void
    {
        // No school at all — the submission must arrive with school_name preserved
        // and no Approval (admin or platform resolves it manually).
        $request = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700000099',
            [
                'parent_name' => 'Sunday Johnson',
                'child_name'  => 'Grace Auma',
                'child_class' => 'P.7',
                'school_name' => 'Completely Unknown School XYZ',
            ],
        );

        $this->assertTrue($request->wasRecentlyCreated);
        $this->assertNull($request->school_id);
        $this->assertSame('Completely Unknown School XYZ', $request->school_name);
        // No approval row — will be resolved by admin.
        $this->assertDatabaseMissing('approvals', [
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id'   => $request->id,
        ]);
    }

    public function test_class_filter_fallback_finds_candidate_when_class_mismatches(): void
    {
        $school = School::create([
            'name'   => 'Fallback Class School',
            'email'  => 'fallback-class@test.sch.ug',
            'status' => 1,
        ]);
        // Student is in "Primary Seven" but parent types a non-standard "Senior 7".
        $link    = $this->createStandardLink($school, 'Primary Seven');
        $student = $this->createStudent($school, $link, 'Grace Auma');

        // "Senior 7" matches no section variant — fallback retries without class.
        $candidates = app(ParentLinkRequestService::class)
            ->findCandidateStudents('Grace Auma', 'Senior 7', $school->id);

        // The direct call returns whatever the class filter allows; the
        // createFromFlowSubmission retry is integration-tested here via a submission.
        $request = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700000098',
            [
                'parent_name' => 'Sunday Johnson',
                'child_name'  => 'Grace Auma',
                'child_class' => 'Senior 7',
                'school_name' => 'Fallback Class School',
            ],
        );

        $this->assertTrue($request->wasRecentlyCreated);
        $this->assertSame($school->id, $request->school_id);
        // The fallback retry (no class filter) must have found Grace Auma.
        $this->assertSame($student->id, $request->suggested_student_id);
    }

    private function createStandardLink(School $school, string $sectionName): StandardLink
    {
        $ay = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
            'status' => 1,
        ]);

        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary_upper', 'order' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => $sectionName]);

        return StandardLink::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $ay->id,
        ]);
    }

    private function createStudent(School $school, StandardLink $link, string $name): User
    {
        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        return $student;
    }
}
