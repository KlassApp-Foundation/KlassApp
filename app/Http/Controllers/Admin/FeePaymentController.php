<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\SchoolPayTransaction;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\School;
use App\Services\FeePositionService;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeePaymentController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $payments = FeePayment::with([
            'student.studentAcademicLatest.standardLink.section',
            'feeCategory.standard',
            'recorder',
        ])
            ->where('school_id', $schoolId)
            ->orderByDesc('created_at')
            ->paginate(50);

        $students = User::where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $feeCategories = FeesCategories::with('standard')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        $kpis = app(FeePositionService::class)->forSchool($schoolId);
        $balances = app(FeePositionService::class)->studentsWithBalance($schoolId);
        $showRecordForm = $request->boolean('record') || $request->session()->has('errors');
        $paymentRecorded = $request->session()->has('successmessage');

        return view('admin.fees.payments', compact(
            'payments',
            'students',
            'feeCategories',
            'kpis',
            'balances',
            'showRecordForm',
            'paymentRecorded'
        ));
    }

    public function create()
    {
        $schoolId = Auth::user()->school_id;
        $students = User::where('school_id', $schoolId)->where('usergroup_id', 6)->orderBy('name')->get();
        $feeCategories = FeesCategories::with('standard')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        return view('admin.fees.payment-create', compact('students', 'feeCategories'));
    }

    public function store(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'fee_category_id' => [
                'required',
                'exists:fees_categories,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($schoolId): void {
                    if (! FeesCategories::where('school_id', $schoolId)->where('id', $value)->exists()) {
                        $fail('The selected fee category does not belong to your school.');
                    }
                },
            ],
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:255',
            'paid_on' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $student = User::where('school_id', $schoolId)->where('id', $validated['user_id'])->first();
        if (! $student) {
            return back()->withErrors(['user_id' => 'Student not found in your school.']);
        }

        FeePayment::create([
            'school_id' => $schoolId,
            'fee_category_id' => $validated['fee_category_id'],
            'user_id' => $validated['user_id'],
            'amount' => $validated['amount'],
            'paid_on' => $validated['paid_on'] ?? now()->toDateString(),
            'payment_method' => $validated['payment_method'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => Auth::id(),
            'status' => 'paid',
        ]);

        return redirect()->route('admin.fee-payments')->with('successmessage', 'Payment recorded successfully!');
    }

    public function unmatched()
    {
        $schoolId = Auth::user()->school_id;

        $transactions = SchoolPayTransaction::with(['student', 'matchedFeeCategory'])
            ->where('school_id', $schoolId)
            ->whereNull('matched_fee_category_id')
            ->orderByDesc('paid_at')
            ->paginate(25);

        $feeCategories = FeesCategories::where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name', 'amount']);

        return view('admin.fees.unmatched', compact('transactions', 'feeCategories'));
    }

    public function matchTransaction(Request $request, $transactionId)
    {
        $schoolId = Auth::user()->school_id;

        $validated = $request->validate([
            'fee_category_id' => 'required|exists:fees_categories,id',
        ]);

        $transaction = SchoolPayTransaction::where('school_id', $schoolId)
            ->whereNull('matched_fee_category_id')
            ->findOrFail($transactionId);

        $transaction->update([
            'matched_fee_category_id' => $validated['fee_category_id'],
            'reconciled_at' => now(),
        ]);

        return back()->with('successmessage', 'Payment matched to fee category.');
    }

    public function sendReminders(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $school = School::query()->findOrFail($schoolId);
        $requested = array_values(array_unique(array_map('intval', (array) $request->input('student_ids', []))));
        $rows = collect(app(FeePositionService::class)->studentsWithBalance($schoolId))->keyBy('id');

        $chosen = [];
        $skipped = [];
        foreach ($requested as $id) {
            $row = $rows->get($id);
            if (! $row) {
                $skipped[] = 'That student is not one with a balance at this school';
                continue;
            }
            $chosen[] = $row;
        }

        $parents = array_sum(array_column($chosen, 'parents'));
        if (! $request->boolean('confirmed')) {
            $noun = $parents === 1 ? '1 parent' : $parents.' parents';

            return back()->with('reminder_result', 'Confirm the reminder first. It would message '.$noun.'.');
        }

        $blocked = (bool) $school->is_demo || (bool) $school->is_test;
        if ($blocked) {
            $noun = $parents === 1 ? '1 parent' : $parents.' parents';

            return back()
                ->with('reminder_result', 'Reminder ready for '.$noun.'. This is a demo school, so nothing was sent.')
                ->with('reminder_tone', 'ready');
        }

        $sent = 0;
        foreach ($chosen as $row) {
            if ($row['parents'] < 1) {
                $skipped[] = $row['name'].': No parent linked';
                continue;
            }
            if (! $row['payment_id']) {
                $skipped[] = $row['name'].': No fee payment on file';
                continue;
            }
            try {
                $inner = Request::create(
                    '/admin/dashboard/send/reminder/'.$row['payment_id'],
                    'POST',
                    ['name' => $row['lookup_name']]
                );
                $result = app(DashboardController::class)->sendReminder($inner, $row['payment_id']);
                if (is_array($result) && isset($result['success'])) {
                    $sent++;
                } else {
                    $skipped[] = $row['name'].': The reminder did not send';
                }
            } catch (\Throwable $e) {
                $skipped[] = $row['name'].': The reminder did not send';
            }
        }

        $message = 'Sent '.$sent.'.';
        if ($skipped !== []) {
            $message .= ' Skipped '.count($skipped).' ('.implode('; ', $skipped).').';
        }

        return back()->with('reminder_result', $message);
    }
}
