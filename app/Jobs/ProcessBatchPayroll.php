<?php

namespace App\Jobs;

use App\Models\Payroll;
use App\Models\PayslipItem;
use App\Models\Salary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBatchPayroll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $schoolId,
        protected int $userId,
        protected int $templateId,
        protected string $startDate,
        protected string $endDate,
        protected array $rows,
    ) {}

    public function handle(): void
    {
        $payrollNumbers = [];

        DB::beginTransaction();
        try {
            foreach ($this->rows as $row) {
                $payrollNo = $this->nextPayrollNumber();

                $payroll = Payroll::create([
                    'school_id'       => $this->schoolId,
                    'payrollno'       => $payrollNo,
                    'staff_id'        => $row['staff_id'],
                    'salary_id'       => $row['salary_id'],
                    'start_date'      => $this->startDate,
                    'end_date'        => $this->endDate,
                    'percentage'      => 100,
                    'leave'           => 0,
                    'leave_deduction' => $row['leave_deduction'] ?? 0,
                    'status'          => 'unpaid',
                    'comments'        => "Batch run from template #{$this->templateId}",
                ]);

                $salary = Salary::with('salaryitems')->find($row['salary_id']);
                if ($salary && $salary->salaryitems->isNotEmpty()) {
                    foreach ($salary->salaryitems as $item) {
                        PayslipItem::create([
                            'payroll_id'     => $payroll->id,
                            'salary_item_id' => $item->id,
                            'amount'         => $item->amount,
                        ]);
                    }
                }

                $payrollNumbers[] = $payrollNo;
            }

            DB::commit();
            Log::info('Batch payroll completed', [
                'school_id' => $this->schoolId,
                'count' => count($this->rows),
                'payrolls' => $payrollNumbers,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch payroll job failed: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    private function nextPayrollNumber(): string
    {
        $last = Payroll::where('school_id', $this->schoolId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$last) {
            return '#_' . str_pad('1', 3, '0', STR_PAD_LEFT);
        }

        $parts = explode('_', $last->payrollno);
        $num = isset($parts[1]) ? (int) $parts[1] + 1 : 1;

        return '#_' . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }
}
