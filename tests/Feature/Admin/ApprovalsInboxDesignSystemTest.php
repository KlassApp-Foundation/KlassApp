<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\User;
use App\States\Approval\Pending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Approvals inbox on the design-system table/buttons (2026-09-27 handoff,
 * item 2). Before: raw Tailwind table, and bg-green-600 / bg-red-500 buttons
 * that the Tailwind v4 build never emitted — white text on a transparent
 * background, ~22px tall.
 */
class ApprovalsInboxDesignSystemTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([VerifyCsrfToken::class, MustBeSchoolAdmin::class, MustBePrivilege::class]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create(['name' => 'Inbox School', 'email' => 'inbox@test.sch.ug', 'status' => 1]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'usergroup_id' => 3, 'name' => 'Inbox Admin']);
    }

    public function test_empty_inbox_uses_the_ds_empty_state(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.approvals.inbox'));

        $response->assertOk();
        $response->assertSee('class="ds-empty-state"', false);
        $response->assertSee('No approval requests yet', false);
        $response->assertSee('Staff leave, parent-link and marks requests you need to review will appear here.', false);
        $response->assertDontSee('No approval requests yet.', false);
    }

    public function test_pending_row_renders_on_ds_table_buttons_and_inputs(): void
    {
        $student = User::factory()->create(['school_id' => $this->school->id, 'usergroup_id' => 6, 'name' => 'Candidate Child']);
        $linkRequest = ParentLinkRequest::create([
            'school_id' => $this->school->id,
            'phone' => '+256700444000',
            'parent_name' => 'Inbox Parent',
            'child_name' => 'Candidate Child',
            'child_class' => 'P5',
            'school_name' => 'Inbox School',
            'suggested_student_id' => $student->id,
            'candidate_student_ids' => [$student->id],
            'status' => 'pending',
        ]);
        $approval = Approval::create([
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $linkRequest->id,
            'state' => Pending::class,
            'requested_by' => null,
            'comments' => $linkRequest->summaryLine(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.approvals.inbox'));
        $html = $response->getContent();

        $response->assertOk();
        // x-table: ledger header + stacked mobile cards, all six columns labelled.
        $this->assertStringContainsString('ds-table-ledger', $html);
        $this->assertStringContainsString('ds-table-card-mobile', $html);
        foreach (['Type', 'Requester', 'Status', 'Comments', 'Requested', 'Actions'] as $col) {
            $this->assertStringContainsString('data-label="'.$col.'"', $html);
        }
        // One size step for all three actions; primary approve, danger reject/confirm.
        $this->assertMatchesRegularExpression('/class="ds-btn ds-btn-primary ds-btn-sm"[^>]*>\s*Approve/', $html);
        $this->assertMatchesRegularExpression('/class="ds-btn ds-btn-danger ds-btn-sm"[^>]*>\s*Reject/', $html);
        $this->assertMatchesRegularExpression('/class="ds-btn ds-btn-danger ds-btn-sm"[^>]*>\s*Confirm/', $html);
        // Native confirm() stays until the dialog PR.
        $this->assertStringContainsString("return confirm('Approve this request?')", $html);
        $this->assertStringContainsString('ds-form-select', $html);
        $this->assertStringContainsString('ds-form-input', $html);
        $this->assertStringContainsString('reject-form-'.$approval->id, $html);
        // The dead/low-contrast utilities are gone from the inbox.
        foreach (['bg-green-600', 'bg-red-500', 'hover:bg-green-500', 'hover:bg-red-400'] as $dead) {
            $this->assertStringNotContainsString($dead, $html);
        }
        $inbox = file_get_contents(resource_path('views/admin/approvals/inbox.blade.php'));
        $this->assertStringNotContainsString('text-gray-400', $inbox);
    }
}
