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
use App\States\Approval\Approved;
use App\States\Approval\Pending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            ],
        );

        $this->assertSame('pending', $request->status);
        $this->assertSame($this->school->id, $request->school_id);
        $this->assertSame($this->student->id, $request->suggested_student_id);

        $this->assertDatabaseHas('parent_link_requests', [
            'phone' => '+256700111222',
            'child_name' => 'Amope Nandawula',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('approvals', [
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $request->id,
            'state' => Pending::class,
        ]);
    }

    public function test_admin_approve_creates_parent_link(): void
    {
        $linkRequest = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700333444',
            [
                'parent_name' => 'Jane Parent',
                'child_name' => 'Amope Nandawula',
                'child_class' => 'P.3',
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

    public function test_admin_reject_marks_request_rejected(): void
    {
        $linkRequest = app(ParentLinkRequestService::class)->createFromFlowSubmission(
            '+256700555666',
            [
                'parent_name' => 'Reject Parent',
                'child_name' => 'Amope Nandawula',
                'child_class' => 'P.3',
            ],
        );

        $approval = Approval::where('approvable_id', $linkRequest->id)
            ->where('approvable_type', ParentLinkRequest::class)
            ->firstOrFail();

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
