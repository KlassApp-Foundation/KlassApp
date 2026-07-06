<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use App\Models\User;
use App\Models\PayrollTemplate;
use App\Models\TemplateItem;
use App\Models\Salary;
use App\Models\SalaryItem;
use App\Models\PayrollItem;
use App\Models\PayCategory;

class DemoPayrollSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('status', 1)->first();

        if (! $school) {
            $this->command->warn('DemoPayrollSeeder: no active school found.');
            return;
        }

        $teacher = User::where([
            ['school_id', $school->id],
            ['usergroup_id', 5],
            ['status', 'active'],
        ])->first();

        if (! $teacher) {
            $this->command->warn("DemoPayrollSeeder: no active teacher for school {$school->name}.");
            return;
        }

        $admin = User::where([
            ['school_id', $school->id],
            ['usergroup_id', 3],
        ])->first();

        $createdBy = $admin?->id ?? $teacher->id;

        $basicSalary = PayrollItem::firstOrCreate(
            ['key' => 'BS'],
            ['name' => 'Basic Salary', 'type' => 'earning']
        );

        $transportAllowance = PayrollItem::firstOrCreate(
            ['key' => 'TA'],
            ['name' => 'Transport Allowance', 'type' => 'earning']
        );

        $nssf = PayrollItem::firstOrCreate(
            ['key' => 'NSSF'],
            ['name' => 'NSSF Contribution (Employee 5%)', 'type' => 'deduction']
        );

        $earningCategory = PayCategory::firstOrCreate(['name' => 'Earning / Allowance']);
        $deductionCategory = PayCategory::firstOrCreate(['name' => 'Statutory Deduction']);

        $template = PayrollTemplate::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Basic Salary Template'],
            [
                'status'     => 1,
                'created_by' => $createdBy,
            ]
        );

        $templateItems = [
            ['item_id' => $basicSalary->id,        'paycategory_id' => $earningCategory->id],
            ['item_id' => $transportAllowance->id, 'paycategory_id' => $earningCategory->id],
            ['item_id' => $nssf->id,               'paycategory_id' => $deductionCategory->id],
        ];

        foreach ($templateItems as $item) {
            TemplateItem::firstOrCreate(
                [
                    'template_id'    => $template->id,
                    'item_id'        => $item['item_id'],
                    'paycategory_id' => $item['paycategory_id'],
                ],
                ['category_value' => null]
            );
        }

        // Create an active salary for the teacher
        $effectiveDate = now()->format('Y-m-d');

        $salary = Salary::firstOrCreate(
            [
                'school_id' => $school->id,
                'staff_id'  => $teacher->id,
            ],
            [
                'template_id'    => $template->id,
                'gross_salary'   => 1000000,
                'effective_date' => $effectiveDate,
                'comments'       => 'Demo salary for end-to-end payroll test',
            ]
        );

        $salaryItems = [
            'Basic Salary'        => 1000000,
            'Transport Allowance' => 100000,
            'NSSF Contribution (Employee 5%)' => 50000,
        ];

        foreach ($salaryItems as $itemName => $amount) {
            $payrollItem = PayrollItem::where('name', $itemName)->first();
            if (! $payrollItem) {
                continue;
            }

            $templateItem = TemplateItem::where([
                ['template_id', $template->id],
                ['item_id', $payrollItem->id],
            ])->first();

            if (! $templateItem) {
                continue;
            }

            SalaryItem::firstOrCreate(
                [
                    'salary_id'        => $salary->id,
                    'template_item_id' => $templateItem->id,
                ],
                ['amount' => $amount]
            );
        }

        $this->command->info(sprintf(
            'DemoPayrollSeeder: template #%d + salary #%d created for teacher %s (school %s).',
            $template->id,
            $salary->id,
            $teacher->email,
            $school->name
        ));
    }
}
