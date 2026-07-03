<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\PayrollTemplate;
use App\Models\TemplateItem;
use App\Models\Salary;
use App\Models\SalaryItem;
use App\Models\PayrollItem;
use App\Models\PayCategory;
use App\Models\Payroll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollBatchEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolId;
    private User $bursar;
    private User $teacher;
    private PayrollTemplate $template;
    private Salary $salary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'bursar', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolId = DB::table('schools')->insertGetId([
            'name'                 => 'Payroll Test School',
            'slug'                 => 'payroll-test',
            'email'                => 'payroll@test.sch.ug',
            'phone'                => '+256700000001',
            'status'               => 1,
            'registration_country' => 'Uganda',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $academicYearId = DB::table('academic_years')->insertGetId([
            'school_id'   => $this->schoolId,
            'name'        => '2025',
            'description' => 'Current Academic Year',
            'status'      => 1,
            'start_date'  => '2025-01-01',
            'end_date'    => '2025-12-31',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('school_details')->insert([
            ['school_id' => $this->schoolId, 'meta_key' => 'school_logo', 'meta_value' => '-', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->teacher = User::create([
            'name'         => 'Teacher Payroll',
            'email'        => 'teacher@payrolltest.sch.ug',
            'password'     => bcrypt('password123'),
            'school_id'    => $this->schoolId,
            'usergroup_id' => 5,
            'status'       => 'active',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('userprofiles')->insert([
            'user_id'      => $this->teacher->id,
            'school_id'    => $this->schoolId,
            'usergroup_id' => 5,
            'firstname'    => 'Teacher',
            'lastname'     => 'Payroll',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->bursar = User::create([
            'name'         => 'Bursar Payroll',
            'email'        => 'bursar@payrolltest.sch.ug',
            'password'     => bcrypt('password123'),
            'school_id'    => $this->schoolId,
            'usergroup_id' => 11,
            'status'       => 'active',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('userprofiles')->insert([
            'user_id'      => $this->bursar->id,
            'school_id'    => $this->schoolId,
            'usergroup_id' => 11,
            'firstname'    => 'Bursar',
            'lastname'     => 'Payroll',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $basicSalary = PayrollItem::create(['name' => 'Basic Salary', 'key' => 'BS', 'type' => 'earning']);
        $transport   = PayrollItem::create(['name' => 'Transport Allowance', 'key' => 'TA', 'type' => 'earning']);
        $nssf        = PayrollItem::create(['name' => 'NSSF Contribution (Employee 5%)', 'key' => 'NSSF', 'type' => 'deduction']);

        $earningCat  = PayCategory::create(['name' => 'Earning']);
        $deductionCat = PayCategory::create(['name' => 'Deduction']);

        $this->template = PayrollTemplate::create([
            'school_id'  => $this->schoolId,
            'name'       => 'End-to-End Test Template',
            'status'     => 1,
            'created_by' => $this->bursar->id,
        ]);

        $basicItem = TemplateItem::create([
            'template_id'    => $this->template->id,
            'item_id'        => $basicSalary->id,
            'paycategory_id' => $earningCat->id,
        ]);

        $transportItem = TemplateItem::create([
            'template_id'    => $this->template->id,
            'item_id'        => $transport->id,
            'paycategory_id' => $earningCat->id,
        ]);

        $nssfItem = TemplateItem::create([
            'template_id'    => $this->template->id,
            'item_id'        => $nssf->id,
            'paycategory_id' => $deductionCat->id,
        ]);

        $this->salary = new Salary;
        $this->salary->school_id = $this->schoolId;
        $this->salary->staff_id = $this->teacher->id;
        $this->salary->template_id = $this->template->id;
        $this->salary->gross_salary = 1000000;
        $this->salary->effective_date = now()->format('Y-m-d');
        $this->salary->comments = 'Test salary';
        $this->salary->save();

        SalaryItem::create(['salary_id' => $this->salary->id, 'template_item_id' => $basicItem->id, 'amount' => 1000000]);
        SalaryItem::create(['salary_id' => $this->salary->id, 'template_item_id' => $transportItem->id, 'amount' => 100000]);
        SalaryItem::create(['salary_id' => $this->salary->id, 'template_item_id' => $nssfItem->id, 'amount' => 50000]);
    }

    public function test_batch_preview_returns_computed_rows(): void
    {
        $this->actingAs($this->bursar)
            ->postJson('/accountant/payroll/batch/preview', [
                'template_id' => $this->template->id,
                'start_date'  => now()->startOfMonth()->format('Y-m-d'),
                'end_date'    => now()->endOfMonth()->format('Y-m-d'),
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('row_count', 1)
            ->assertJsonPath('rows.0.staff_id', $this->teacher->id)
            ->assertJsonPath('rows.0.gross', 2100000)
            ->assertJsonPath('rows.0.net_pay', fn ($net) => $net > 0);
    }

    public function test_batch_confirm_creates_payroll_and_payslip_items(): void
    {
        $preview = $this->actingAs($this->bursar)
            ->postJson('/accountant/payroll/batch/preview', [
                'template_id' => $this->template->id,
                'start_date'  => now()->startOfMonth()->format('Y-m-d'),
                'end_date'    => now()->endOfMonth()->format('Y-m-d'),
            ])
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('rows', $preview);
        $this->assertCount(1, $preview['rows']);

        $this->actingAs($this->bursar)
            ->postJson('/accountant/payroll/batch/confirm', [
                'template_id' => $this->template->id,
                'start_date'  => $preview['start_date'],
                'end_date'    => $preview['end_date'],
                'rows'        => $preview['rows'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('queued', false)
            ->assertJsonPath('payroll_numbers.0', fn ($no) => str_starts_with($no, '#_'));

        $payroll = Payroll::where('school_id', $this->schoolId)
            ->where('staff_id', $this->teacher->id)
            ->first();

        $this->assertNotNull($payroll);
        $this->assertSame('unpaid', $payroll->status);
        $this->assertCount(3, $payroll->payslipitems);

        $this->actingAs($this->bursar)
            ->get("/accountant/payroll/payslip/{$payroll->id}/view")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
