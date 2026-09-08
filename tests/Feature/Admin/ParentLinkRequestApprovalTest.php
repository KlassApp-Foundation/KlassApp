<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\WhatsApp\ParentLinkRequestService;
use App\Services\WhatsAppBusinessService;
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ParentLinkRequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $student;

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

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBeSchoolAdmin::class,
            MustBePrivilege::class,
        ]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Link Request School',
            'email' => 'link-req@test.sch.ug',
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'School Admin',
        ]);

        $link = $this->createStandardLink($this->school, 'P.3');
        $this->student = $this->createStudent($this->school, $link, 'Amope Nandawula');
    }

    public function test_flow_submission_creates_request_and_approval(): void
    {
        $request = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700111222',
            [
                'parent_name' => 'Jane Parent',
                'child_name' => 'Amope Nandawula',
                'child_class' => 'P.3',
                'school_name' => 'Link Request School',
            ],
        );

        $this->assertSame('pending', $request->status);
        $this->assertSame($this->school->id, $request->school_id);
        $this->assertSame('Link Request School', $request->school_name);
        $this->assertSame($this->student->id, $request->suggested_student_id);

        $this->assertDatabaseHas('parent_link_requests', [
            'phone' => '+256700111222',
            'child_name' => 'Amope Nandawula',
            'school_name' => 'Link Request School',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('approvals', [
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $request->id,
            'state' => Pending::class,
        ]);
    }

    public function test_school_name_is_primary_signal_over_cross_school_child_match(): void
    {
        $otherSchool = School::create([
            'name' => 'Other Primary School',
            'email' => 'other-link@test.sch.ug',
            'status' => 1,
        ]);
        $otherLink = $this->createStandardLink($otherSchool, 'P.3');
        $this->createStudent($otherSchool, $otherLink, 'Amope Nandawula');

        $request = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700777888',
            [
                'parent_name' => 'Jane Parent',
                'child_name' => 'Amope Nandawula',
                'child_class' => 'P.3',
                'school_name' => 'Link Request School',
            ],
        );

        $this->assertSame($this->school->id, $request->school_id);
        $this->assertSame([$this->student->id], $request->candidate_student_ids);
        $this->assertSame($this->student->id, $request->suggested_student_id);
    }

    public function test_admin_approve_creates_parent_link(): void
    {
        $linkRequest = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700333444',
            [
                'parent_name' => 'Jane Parent',
                'child_name' => 'Amope Nandawula',
                'child_class' => 'P.3',
                'school_name' => 'Link Request School',
            ],
        );

        $approval = Approval::where('approvable_id', $linkRequest->id)
            ->where('approvable_type', ParentLinkRequest::class)
            ->firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('admin.approvals.approve', $approval), [
            'matched_student_id' => $this->student->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $linkRequest->refresh();
        $approval->refresh();

        $this->assertSame('approved', $linkRequest->status);
        $this->assertSame($this->student->id, $linkRequest->matched_student_id);
        $this->assertInstanceOf(Approved::class, $approval->state);

        $whatsappUser = WhatsAppUser::where('phone', '+256700333444')->first();
        $this->assertNotNull($whatsappUser);
        $this->assertNotNull($whatsappUser->user_id);

        $parent = User::find($whatsappUser->user_id);
        $this->assertNotNull($parent);
        $this->assertSame(7, (int) $parent->usergroup_id);

        $this->assertDatabaseHas('student_parent_links', [
            'parent_id' => $parent->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
        ]);

        $this->assertDatabaseHas('whatsapp_users', [
            'phone' => '+256700333444',
            'user_id' => $parent->id,
        ]);
    }

    public function test_admin_reject_marks_request_rejected_and_notifies_parent(): void
    {
        $linkRequest = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700555666',
            [
                'parent_name' => 'Reject Parent',
                'child_name' => 'Amope Nandawula',
                'child_class' => 'P.3',
                'school_name' => 'Link Request School',
            ],
        );

        $approval = Approval::where('approvable_id', $linkRequest->id)
            ->where('approvable_type', ParentLinkRequest::class)
            ->firstOrFail();

        $whatsApp = Mockery::mock(WhatsAppBusinessService::class)->makePartial();
        $whatsApp->shouldReceive('sendInteractiveButtons')
            ->once()
            ->withArgs(function (string $phone, string $message, array $buttons, ?string $flowType) {
                $ids = collect($buttons)->pluck('id')->all();

                return $phone === '+256700555666'
                    && $flowType === 'parent_link_rejected'
                    && str_contains($message, "couldn't approve")
                    && str_contains($message, 'Student not enrolled here')
                    && str_contains($message, 'Amope Nandawula')
                    && str_contains($message, 'Tap *Request Link* below')
                    && in_array('parent_link_flow', $ids, true);
            })
            ->andReturn(['success' => true, 'message_id' => 'rej']);
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $response = $this->actingAs($this->admin)->post(route('admin.approvals.reject', $approval), [
            'comments' => 'Student not enrolled here',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('rejected', $linkRequest->fresh()->status);
        $this->assertFalse(
            StudentParentLink::where('student_id', $this->student->id)->exists()
        );
        $this->assertFalse(
            WhatsAppUser::where('phone', '+256700555666')->exists()
        );
    }

    public function test_second_flow_submission_while_pending_does_not_duplicate(): void
    {
        $service = app(ParentLinkRequestService::class);

        $first = $service->createFromFlowSubmission(
            '+256700999000',
            [
                'parent_name' => 'First Parent',
                'child_name' => 'Amope Nandawula',
                'child_class' => 'P.3',
                'school_name' => 'Link Request School',
            ],
        );

        $second = $service->createFromFlowSubmission(
            '+256700999000',
            [
                'parent_name' => 'Second Attempt',
                'child_name' => 'Different Child',
                'child_class' => 'P.4',
                'school_name' => 'Link Request School',
            ],
        );

        $this->assertTrue($first->wasRecentlyCreated);
        $this->assertFalse($second->wasRecentlyCreated);
        $this->assertSame($first->id, $second->id);
        $this->assertSame('First Parent', $second->parent_name);
        $this->assertSame(1, ParentLinkRequest::where('phone', '+256700999000')->count());
        $this->assertSame(1, Approval::where('approvable_type', ParentLinkRequest::class)
            ->where('approvable_id', $first->id)
            ->count());
    }

    public function test_pending_at_school_a_does_not_block_flow_submission_at_school_b(): void
    {
        $schoolB = School::create([
            'name' => 'Second Link School',
            'email' => 'second-link@test.sch.ug',
            'status' => 1,
        ]);
        $linkB = $this->createStandardLink($schoolB, 'P.4');
        $studentB = $this->createStudent($schoolB, $linkB, 'Eric Ssempala');

        $phone = '+256700111333';
        $service = app(ParentLinkRequestService::class);

        $pendingAtA = $service->createFromFlowSubmission($phone, [
            'parent_name' => 'Cross School Parent',
            'child_name' => 'Amope Nandawula',
            'child_class' => 'P.3',
            'school_name' => 'Link Request School',
        ]);

        $this->assertTrue($pendingAtA->wasRecentlyCreated);
        $this->assertSame($this->school->id, $pendingAtA->school_id);
        $this->assertSame('pending', $pendingAtA->status);

        $atSchoolB = $service->createFromFlowSubmission($phone, [
            'parent_name' => 'Cross School Parent',
            'child_name' => 'Eric Ssempala',
            'child_class' => 'P.4',
            'school_name' => 'Second Link School',
        ]);

        $this->assertTrue(
            $atSchoolB->wasRecentlyCreated,
            'Pending at school A must not suppress a new Flow submission for school B'
        );
        $this->assertNotSame($pendingAtA->id, $atSchoolB->id);
        $this->assertSame($schoolB->id, $atSchoolB->school_id);
        $this->assertSame($studentB->id, $atSchoolB->suggested_student_id);
        $this->assertSame('pending', $atSchoolB->status);

        $this->assertSame(2, ParentLinkRequest::where('phone', $phone)->where('status', 'pending')->count());
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $atSchoolB->id,
            'state' => Pending::class,
        ]);

        // Same-school duplicate still suppressed
        $dupAtB = $service->createFromFlowSubmission($phone, [
            'parent_name' => 'Cross School Parent',
            'child_name' => 'Someone Else',
            'child_class' => 'P.5',
            'school_name' => 'Second Link School',
        ]);
        $this->assertFalse($dupAtB->wasRecentlyCreated);
        $this->assertSame($atSchoolB->id, $dupAtB->id);
        $this->assertSame(2, ParentLinkRequest::where('phone', $phone)->count());
    }

    public function test_approve_second_parent_when_student_already_has_a_link(): void
    {
        $firstParent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'name' => 'First Parent',
            'status' => 'active',
        ]);
        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $firstParent->id,
            'student_id' => $this->student->id,
            'status' => 1,
        ]);

        $linkRequest = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700444555',
            [
                'parent_name' => 'Aladini',
                'child_name' => 'Amope Nandawula',
                'child_class' => 'P.3',
                'school_name' => 'Link Request School',
            ],
        );

        $approval = Approval::where('approvable_id', $linkRequest->id)
            ->where('approvable_type', ParentLinkRequest::class)
            ->firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('admin.approvals.approve', $approval), [
            'matched_student_id' => $this->student->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $whatsappUser = WhatsAppUser::where('phone', '+256700444555')->firstOrFail();
        $secondParent = User::findOrFail($whatsappUser->user_id);
        $this->assertSame(7, (int) $secondParent->usergroup_id);
        $this->assertSame('Aladini', $secondParent->name);
        $this->assertNotSame($firstParent->id, $secondParent->id);

        $this->assertSame(2, StudentParentLink::where('student_id', $this->student->id)->where('status', 1)->count());
        $this->assertDatabaseHas('student_parent_links', [
            'parent_id' => $secondParent->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
        ]);
        $this->assertSame('approved', $linkRequest->fresh()->status);
    }

    public function test_approve_reclaims_whatsapp_phone_bound_to_non_parent(): void
    {
        $adminUser = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Stuck Admin',
            'status' => 'active',
        ]);
        WhatsAppUser::create([
            'phone' => '+256700666777',
            'user_id' => $adminUser->id,
            'school_id' => $this->school->id,
            'opted_in' => true,
        ]);

        $linkRequest = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700666777',
            [
                'parent_name' => 'Aladini',
                'child_name' => 'Amope Nandawula',
                'child_class' => 'P.3',
                'school_name' => 'Link Request School',
            ],
        );

        $approval = Approval::where('approvable_id', $linkRequest->id)
            ->where('approvable_type', ParentLinkRequest::class)
            ->firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('admin.approvals.approve', $approval), [
            'matched_student_id' => $this->student->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $whatsappUser = WhatsAppUser::where('phone', '+256700666777')->firstOrFail();
        $this->assertNotSame($adminUser->id, $whatsappUser->user_id);

        $parent = User::findOrFail($whatsappUser->user_id);
        $this->assertSame(7, (int) $parent->usergroup_id);
        $this->assertSame('Aladini', $parent->name);
        $this->assertDatabaseHas('student_parent_links', [
            'parent_id' => $parent->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
        ]);
        $this->assertSame(3, (int) $adminUser->fresh()->usergroup_id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary_lower', 'order' => 1]);
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
