<?php

namespace Tests\Feature\Toshi\Accountant;

use App\AiAgents\AccountantOperationsAgent;
use App\AiAgents\Tools\Accountant\CreateTaskTool;
use App\AiAgents\Tools\Accountant\ManagePayrollTool;
use App\AiAgents\Tools\Accountant\RecordPaymentTool;
use App\AiAgents\Tools\Accountant\ViewDashboardTool;
use App\AiAgents\Tools\Accountant\ViewFeeStructureTool;
use App\AiAgents\Tools\Accountant\ViewUnpaidReportsTool;
use App\AiAgents\Tools\AddCoAdminTool;
use App\Models\ActivityLog;
use App\Models\FeePayment;
use App\Models\Payroll;
use App\Models\PayrollTemplate;
use App\Models\Salary;
use App\Models\Task;
use App\Models\User;
use App\Services\ToshiActionService;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;
use Tests\TestCase;

class AccountantOperationsToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $accountant;

    private User $student;

    private User $staff;

    private int $schoolId;

    private int $feeCategoryId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => 'nonteaching', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Accountant Toshi School',
            'slug' => 'accountant-toshi',
            'email' => 'accountant-toshi@test.sch.ug',
            'phone' => '+256700000011',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->accountant = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 11,
            'email' => 'accountant.toshi@test.sch.ug',
            'name' => 'Toshi Accountant',
        ]);

        $this->student = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.acct@test.sch.ug',
            'name' => 'Fee Student',
        ]);

        $this->staff = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 13,
            'email' => 'staff.acct@test.sch.ug',
            'name' => 'Payroll Staff',
        ]);

        $standardId = DB::table('standards')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => 'primary',
            'order' => 1,
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->feeCategoryId = (int) DB::table('fees_categories')->insertGetId([
            'school_id' => $this->schoolId,
            'standard_id' => $standardId,
            'section_id' => null,
            'name' => 'Tuition',
            'amount' => 450000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_accountant_agent_tools_exclude_school_admin_tools(): void
    {
        $agent = new AccountantOperationsAgent;
        $classes = collect($agent->tools())->map(fn ($t) => $t::class)->all();

        $this->assertNotContains(AddCoAdminTool::class, $classes);
        $this->assertContains(RecordPaymentTool::class, $classes);
        $this->assertContains(ManagePayrollTool::class, $classes);
        $this->assertCount(6, $classes);
    }

    public function test_gates_isolate_accountant_from_school_and_teacher(): void
    {
        $this->actingAs($this->accountant);

        $this->assertTrue(Gate::inspect('toshi-school-action', $this->accountant)->denied());
        $this->assertTrue(Gate::inspect('toshi-teacher-action', $this->accountant)->denied());
        $this->assertTrue(Gate::inspect('toshi-accountant-action', $this->accountant)->allowed());
    }

    public function test_school_admin_tool_denies_accountant_via_authorize(): void
    {
        $this->actingAs($this->accountant);
        ToshiActionService::$bypassConfirm = true;

        $result = (new AddCoAdminTool)->handle(new Request([
            'name' => 'Should Fail',
            'email' => 'coadmin.fail@test.sch.ug',
        ]));

        $this->assertStringStartsWith('❌', $result);
        ToshiActionService::$bypassConfirm = false;
    }

    public function test_record_payment_happy_path_and_validation(): void
    {
        $this->actingAs($this->accountant);
        ToshiActionService::$bypassConfirm = true;

        $ok = (new RecordPaymentTool)->handle(new Request([
            'student_id' => $this->student->id,
            'amount' => 50000,
            'fee_category_id' => $this->feeCategoryId,
            'payment_method' => 'cash',
        ]));
        $this->assertStringStartsWith('✅', $ok);
        $this->assertTrue(FeePayment::where('user_id', $this->student->id)->where('amount', 50000)->where('fee_category_id', $this->feeCategoryId)->where('recorded_by', $this->accountant->id)->exists());

        $bad = (new RecordPaymentTool)->handle(new Request([
            'student_id' => $this->student->id,
            'amount' => -1,
            'fee_category_id' => $this->feeCategoryId,
        ]));
        $this->assertStringStartsWith('❌', $bad);

        $missingCategory = (new RecordPaymentTool)->handle(new Request([
            'student_id' => $this->student->id,
            'amount' => 25000,
            'payment_method' => 'cash',
        ]));
        $this->assertStringStartsWith('❌', $missingCategory);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_record_payment_requires_confirmation_without_bypass(): void
    {
        $this->actingAs($this->accountant);
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        $result = (new RecordPaymentTool)->handle(new Request([
            'student_id' => $this->student->id,
            'amount' => 10000,
            'fee_category_id' => $this->feeCategoryId,
            'payment_method' => 'mobile_money',
        ]));

        $decoded = json_decode($result, true);
        $this->assertTrue($decoded['__tier2_confirm'] ?? false);
        $this->assertSame('toolAccountantRecordPayment', $decoded['tool']);
        $this->assertFalse(FeePayment::where('user_id', $this->student->id)->where('amount', 10000)->exists());
    }

    public function test_manage_payroll_happy_path(): void
    {
        $this->actingAs($this->accountant);
        ToshiActionService::$bypassConfirm = true;

        $templateId = PayrollTemplate::create([
            'school_id' => $this->schoolId,
            'name' => 'Monthly Staff',
            'status' => 1,
            'created_by' => $this->accountant->id,
        ])->id;

        Salary::create([
            'school_id' => $this->schoolId,
            'staff_id' => $this->staff->id,
            'template_id' => $templateId,
            'gross_salary' => 1000000,
            'effective_date' => '2026-01-01',
            'comments' => null,
        ]);

        $result = (new ManagePayrollTool)->handle(new Request([
            'template_id' => $templateId,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]));

        $this->assertStringStartsWith('✅', $result);
        $this->assertTrue(Payroll::where('staff_id', $this->staff->id)->where('status', 'unpaid')->exists());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_create_task_happy_path(): void
    {
        $this->actingAs($this->accountant);
        ToshiActionService::$bypassConfirm = true;

        $result = (new CreateTaskTool)->handle(new Request([
            'title' => 'Reconcile fees',
            'task_date' => '2026-08-10',
        ]));

        $this->assertStringStartsWith('✅', $result);
        $this->assertTrue(Task::where('user_id', $this->accountant->id)->where('title', 'Reconcile fees')->exists());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_view_tools_authorized_for_accountant(): void
    {
        $this->actingAs($this->accountant);

        foreach ([
            new ViewUnpaidReportsTool,
            new ViewFeeStructureTool,
            new ViewDashboardTool,
        ] as $tool) {
            $result = $tool->handle(new Request([]));
            $this->assertStringStartsWith('✅', $result, $tool::class);
        }
    }

    public function test_unconfirmed_or_read_only_audit_leaves_approver_id_null(): void
    {
        $this->actingAs($this->accountant);

        $log = ToshiAuditService::logExecution(
            user: $this->accountant,
            school: $this->accountant->school,
            toolName: 'ViewFeeStructureTool',
            arguments: [],
            result: '✅ ok',
            actingUser: $this->accountant,
            approver: null,
        );

        $this->assertSame($this->accountant->id, $log->properties['acting_user_id']);
        $this->assertNull($log->properties['approver_id']);
        $this->assertSame($this->accountant->id, $log->causer_id);
        $this->assertTrue(ActivityLog::where('log_name', 'toshi')->where('causer_id', $this->accountant->id)->exists());
    }

    public function test_tier2_confirm_yes_sets_approver_id_to_confirming_accountant(): void
    {
        $this->actingAs($this->accountant);

        Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('schoolId', $this->schoolId)
            ->set('pendingToolConfirm', [
                'tool' => 'toolAccountantRecordPayment',
                'args' => [
                    'student_id' => $this->student->id,
                    'amount' => 25000,
                    'fee_category_id' => $this->feeCategoryId,
                    'payment_method' => 'cash',
                ],
            ])
            ->call('confirmYes');

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $this->schoolId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'Toshi audit log should exist after Tier-2 confirmYes');
        $this->assertSame('toolAccountantRecordPayment', $log->properties['tool']);
        $this->assertSame($this->accountant->id, $log->properties['acting_user_id']);
        $this->assertSame($this->accountant->id, $log->properties['approver_id']);
        $this->assertTrue(FeePayment::where('user_id', $this->student->id)->where('amount', 25000)->exists());
    }
}
