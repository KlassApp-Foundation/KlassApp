<?php

namespace App\Services\Toshi;

use App\Helpers\SiteHelper;
use App\Models\Events;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\NoticeBoard;
use App\Models\Payroll;
use App\Models\PayrollTemplate;
use App\Models\PayslipItem;
use App\Models\Salary;
use App\Models\Task;
use App\Models\TeacherLeaveApplication;
use App\Models\User;
use App\Services\UgandaPayrollCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Accountant-scoped Toshi mutations / reads (ug11).
 * Mirrors accountant panel controllers / payroll APIs — does not reuse ug3 ToshiActionService write paths.
 */
class AccountantActionService
{
    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public static function result(bool $success, string $message, array $data = []): array
    {
        $out = [
            'success' => $success,
            'message' => $message,
        ];
        if ($data !== []) {
            $out['data'] = $data;
        }

        return $out;
    }

    public static function recordPayment(User $accountant, array $data): array
    {
        $v = Validator::make($data, [
            'student_id' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'fee_category_id' => 'nullable|integer',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:255',
            'paid_on' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $schoolId = (int) $accountant->school_id;
        $student = User::where('school_id', $schoolId)
            ->where('id', $data['student_id'])
            ->where('usergroup_id', 6)
            ->first();
        if (! $student) {
            return self::result(false, 'Student not found in your school.');
        }

        $feeCategoryId = ! empty($data['fee_category_id']) ? (int) $data['fee_category_id'] : null;
        if ($feeCategoryId) {
            $feeCat = FeesCategories::where('school_id', $schoolId)->where('id', $feeCategoryId)->first();
            if (! $feeCat) {
                return self::result(false, 'Fee category not found.');
            }
        }

        $payment = FeePayment::create([
            'school_id' => $schoolId,
            'fee_category_id' => $feeCategoryId,
            'user_id' => $student->id,
            'amount' => (float) $data['amount'],
            'paid_on' => $data['paid_on'] ?? now()->toDateString(),
            'payment_method' => $data['payment_method'] ?? 'cash',
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $accountant->id,
            'status' => 'paid',
        ]);

        $msg = 'Payment of **'.number_format((float) $data['amount'], 0).' UGX** recorded for **'.$student->name.'**.';
        if (! empty($data['payment_method'])) {
            $msg .= ' Method: **'.$data['payment_method'].'**.';
        }

        return self::result(true, $msg, ['payment_id' => $payment->id]);
    }

    /**
     * Run batch payroll for a template + pay period (preview compute → create payroll rows).
     * Mirrors POST /accountant/payroll/batch/preview + /confirm.
     */
    public static function managePayroll(User $accountant, array $data): array
    {
        $v = Validator::make($data, [
            'template_id' => 'required|integer',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $schoolId = (int) $accountant->school_id;
        $template = PayrollTemplate::where('school_id', $schoolId)
            ->where('id', $data['template_id'])
            ->first();
        if (! $template) {
            return self::result(false, "Payroll template not found: {$data['template_id']}");
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        $salaries = Salary::with(['user.userprofile', 'salaryitems'])
            ->where([
                ['school_id', $schoolId],
                ['template_id', $template->id],
                ['effective_date', '<=', $endDate->format('Y-m-d')],
            ])
            ->get();

        if ($salaries->isEmpty()) {
            return self::result(false, 'No active salaries found for this template.');
        }

        $rows = [];
        foreach ($salaries as $salary) {
            $gross = (float) $salary->gross_salary;
            $statutory = UgandaPayrollCalculator::computeAll($gross, $startDate->copy());
            $leaveDays = self::safeLeaveDays((int) $salary->staff_id, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
            $leaveDeduction = round($leaveDays * ($gross / 30));
            $itemEarnings = (float) $salary->totalearnings();
            $itemDeductions = (float) $salary->totaldeductions();
            $grossPay = $gross + $itemEarnings;
            $netPay = $grossPay - $statutory['total_deductions'] - $itemDeductions - $leaveDeduction;

            $rows[] = [
                'staff_id' => $salary->staff_id,
                'salary_id' => $salary->id,
                'gross' => round($grossPay),
                'paye' => $statutory['paye'],
                'nssf' => $statutory['nssf_employee'],
                'lst' => $statutory['lst'],
                'leave_deduction' => $leaveDeduction,
                'net_pay' => max(0, round($netPay)),
            ];
        }

        $staffIds = collect($rows)->pluck('staff_id')->all();
        $existing = Payroll::whereIn('staff_id', $staffIds)
            ->where('start_date', $startDate->format('Y-m-d'))
            ->where('end_date', $endDate->format('Y-m-d'))
            ->exists();
        if ($existing) {
            return self::result(false, 'Payroll already exists for one or more staff in this period.');
        }

        $payrollNumbers = [];
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $payrollNo = self::nextPayrollNumber($schoolId);
                $payroll = Payroll::create([
                    'school_id' => $schoolId,
                    'payrollno' => $payrollNo,
                    'staff_id' => $row['staff_id'],
                    'salary_id' => $row['salary_id'],
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'percentage' => 100,
                    'leave' => 0,
                    'leave_deduction' => $row['leave_deduction'],
                    'gross_pay' => $row['gross'],
                    'paye' => $row['paye'],
                    'nssf' => $row['nssf'],
                    'lst' => $row['lst'],
                    'net_pay' => $row['net_pay'],
                    'status' => 'unpaid',
                    'comments' => "Toshi batch run from template #{$template->id}",
                ]);

                $salary = Salary::with('salaryitems')->find($row['salary_id']);
                if ($salary && $salary->salaryitems->isNotEmpty()) {
                    foreach ($salary->salaryitems as $item) {
                        PayslipItem::create([
                            'payroll_id' => $payroll->id,
                            'salary_item_id' => $item->id,
                            'amount' => $item->amount,
                        ]);
                    }
                }

                $payrollNumbers[] = $payrollNo;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return self::result(false, 'Failed to create payroll: '.$e->getMessage());
        }

        $count = count($payrollNumbers);

        return self::result(
            true,
            "Payroll created for **{$count}** staff (template **{$template->name}**, {$data['start_date']} → {$data['end_date']}). Numbers: ".implode(', ', $payrollNumbers).'.',
            ['payroll_numbers' => $payrollNumbers, 'count' => $count]
        );
    }

    public static function viewUnpaidReports(User $accountant): array
    {
        $schoolId = (int) $accountant->school_id;
        $payrolls = Payroll::with('user')
            ->where('school_id', $schoolId)
            ->where('status', 'unpaid')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        if ($payrolls->isEmpty()) {
            return self::result(true, 'No unpaid payroll records.');
        }

        $lines = $payrolls->map(function (Payroll $p) {
            $name = $p->user?->name ?? "staff #{$p->staff_id}";
            $net = number_format((float) ($p->net_pay ?? 0), 0);

            return "- {$p->payrollno}: {$name} — net UGX {$net} ({$p->start_date} → {$p->end_date})";
        })->implode("\n");

        return self::result(true, "**Unpaid payrolls ({$payrolls->count()}):**\n{$lines}", [
            'count' => $payrolls->count(),
        ]);
    }

    public static function viewFeeStructure(User $accountant): array
    {
        $schoolId = (int) $accountant->school_id;
        $categories = FeesCategories::where('school_id', $schoolId)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'amount', 'fee_type']);

        if ($categories->isEmpty()) {
            return self::result(true, 'No fee categories found.');
        }

        $lines = $categories->map(function (FeesCategories $c) {
            $amt = number_format((float) $c->amount, 0);
            $type = $c->fee_type ? " [{$c->fee_type}]" : '';

            return "- {$c->name}{$type}: UGX {$amt} (id {$c->id})";
        })->implode("\n");

        return self::result(true, "**Fee structure:**\n{$lines}", ['count' => $categories->count()]);
    }

    public static function viewDashboard(User $accountant): array
    {
        $schoolId = (int) $accountant->school_id;
        $academicYear = SiteHelper::getAcademicYear($schoolId);

        $feeCategoryCount = FeesCategories::where('school_id', $schoolId)->count();
        $totalFeesAmount = (float) FeesCategories::where('school_id', $schoolId)->sum('amount');
        $totalStudents = User::where('school_id', $schoolId)->where('usergroup_id', 6)->count();
        $pendingTasks = Task::where('school_id', $schoolId)->where('task_status', 0)->count();
        $unpaidPayrolls = Payroll::where('school_id', $schoolId)->where('status', 'unpaid')->count();
        $recentPayments = FeePayment::where('school_id', $schoolId)->orderByDesc('id')->limit(5)->count();

        $eventsNote = '';
        if ($academicYear) {
            $events = Events::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYear->id)
                ->where('end_date', '>', now())
                ->count();
            $notices = NoticeBoard::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYear->id)
                ->count();
            $eventsNote = "\n- Upcoming events: {$events}\n- Notices: {$notices}";
        }

        $msg = "**Accountant dashboard**\n"
            ."- Fee categories: {$feeCategoryCount}\n"
            .'- Total fee amounts (categories): UGX '.number_format($totalFeesAmount, 0)."\n"
            ."- Students: {$totalStudents}\n"
            ."- Pending tasks: {$pendingTasks}\n"
            ."- Unpaid payrolls: {$unpaidPayrolls}\n"
            ."- Recent payment rows (sample window): {$recentPayments}"
            .$eventsNote;

        return self::result(true, $msg, [
            'fee_category_count' => $feeCategoryCount,
            'unpaid_payrolls' => $unpaidPayrolls,
        ]);
    }

    public static function createTask(User $accountant, array $data): array
    {
        $v = Validator::make($data, [
            'title' => 'required|string|min:3',
            'to_do_list' => 'nullable|string',
            'task_date' => 'required|date_format:Y-m-d',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $row = Task::create([
            'school_id' => $accountant->school_id,
            'academic_year_id' => optional(SiteHelper::getAcademicYear($accountant->school_id))->id,
            'user_id' => $accountant->id,
            'title' => $data['title'],
            'type' => 'self',
            'to_do_list' => $data['to_do_list'] ?? $data['title'],
            'task_date' => $data['task_date'],
            'reminder' => 'one_day_before_the_task',
            'task_status' => 0,
            'task_flag' => 2,
        ]);

        return self::result(true, "Task **{$row->title}** created (id {$row->id}).", ['id' => $row->id]);
    }

    private static function safeLeaveDays(int $staffId, string $startDate, string $endDate): int
    {
        $leave = TeacherLeaveApplication::where('user_id', $staffId)
            ->where('from_date', '>=', $startDate)
            ->where('status', 'approved')
            ->first();

        if (! $leave) {
            return 0;
        }

        $from = Carbon::parse($leave->from_date)->format('Y-m-d');
        $to = Carbon::parse($leave->to_date)->format('Y-m-d');

        if ($to >= $endDate) {
            return (int) Carbon::parse($from)->diffInDays(Carbon::parse($endDate)) + 1;
        }

        return (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
    }

    private static function nextPayrollNumber(int $schoolId): string
    {
        $last = Payroll::where('school_id', $schoolId)->orderByDesc('id')->first();
        if (! $last) {
            return '#_001';
        }

        $parts = explode('_', (string) $last->payrollno);
        $num = isset($parts[1]) ? (int) $parts[1] + 1 : 1;

        return '#_'.str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }
}
