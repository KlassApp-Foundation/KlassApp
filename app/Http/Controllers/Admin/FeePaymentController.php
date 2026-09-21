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

        $kpis = $this->feePaymentKpis($schoolId);
        $showRecordForm = $request->boolean('record') || $request->session()->has('errors');
        $paymentRecorded = $request->session()->has('successmessage');

        return view('admin.fees.payments', compact(
            'payments',
            'students',
            'feeCategories',
            'kpis',
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

    /**
     * School-scoped fee KPIs for the payments kit fold.
     *
     * @return array{context: string, collected_label: string, outstanding_label: string, arrears_label: string, rate_label: string, arrears_direction: ?string, arrears_delta_label: ?string, arrears_hint: ?string}
     */
    private function feePaymentKpis(int $schoolId): array
    {
        [$termLabel, $startsOn, $endsOn] = $this->currentTermWindow($schoolId);

        $collectedQuery = FeePayment::query()->where('school_id', $schoolId);
        if ($startsOn && $endsOn) {
            $collectedQuery->whereDate('paid_on', '>=', $startsOn)
                ->whereDate('paid_on', '<=', $endsOn);
        }
        $collected = (float) $collectedQuery->sum('amount');

        $categoriesByStandard = FeesCategories::query()
            ->where('school_id', $schoolId)
            ->get()
            ->groupBy('standard_id')
            ->map(fn ($rows) => (float) $rows->sum('amount'));

        $activeStudents = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->pluck('id');

        $latestAcademics = StudentAcademic::query()
            ->select('student_academics.user_id', 'standards_link.standard_id')
            ->join('standards_link', 'student_academics.standardLink_id', '=', 'standards_link.id')
            ->where('student_academics.school_id', $schoolId)
            ->whereNull('student_academics.deleted_at')
            ->whereIn('student_academics.user_id', $activeStudents)
            ->whereIn('student_academics.academic_year_id', function ($q) {
                $q->select('id')->from('academic_years')->where('status', 1);
            })
            ->orderByDesc('student_academics.id')
            ->get()
            ->unique('user_id');

        $paidByStudent = FeePayment::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_id', $activeStudents)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(amount) as total_paid'))
            ->pluck('total_paid', 'user_id');

        $expected = 0.0;
        $outstanding = 0.0;
        $arrears = 0;

        foreach ($latestAcademics as $row) {
            $due = (float) ($categoriesByStandard[$row->standard_id] ?? 0);
            if ($due <= 0) {
                continue;
            }
            $paid = (float) ($paidByStudent[$row->user_id] ?? 0);
            $balance = max(0, $due - $paid);
            $expected += $due;
            $outstanding += $balance;
            if ($balance > 0) {
                $arrears++;
            }
        }

        $rate = $expected > 0 ? (int) round(($expected - $outstanding) / $expected * 100) : 0;

        // Like-for-like trend for the arrears card: the SAME definition (standard
        // fee due vs payments received) evaluated BEFORE this term started, so the
        // card can show whether arrears are rising or falling. Null when there is
        // no term window to compare against — the card then renders no indicator.
        $arrearsDirection = null;
        $arrearsDeltaLabel = null;
        $arrearsHint = null;

        if ($startsOn) {
            $paidBeforeByStudent = FeePayment::query()
                ->where('school_id', $schoolId)
                ->whereIn('user_id', $activeStudents)
                ->whereDate('paid_on', '<', $startsOn)
                ->groupBy('user_id')
                ->select('user_id', DB::raw('SUM(amount) as total_paid'))
                ->pluck('total_paid', 'user_id');

            $arrearsBefore = 0;
            foreach ($latestAcademics as $row) {
                $due = (float) ($categoriesByStandard[$row->standard_id] ?? 0);
                if ($due <= 0) {
                    continue;
                }
                $paidBefore = (float) ($paidBeforeByStudent[$row->user_id] ?? 0);
                if (max(0, $due - $paidBefore) > 0) {
                    $arrearsBefore++;
                }
            }

            // Fewer students in arrears than at term start = 'down' = good
            // (the card passes invertDirection so the indicator reads positive).
            $arrearsDirection = $arrears <=> $arrearsBefore ? ($arrears > $arrearsBefore ? 'up' : 'down') : 'flat';
            $delta = $arrears - $arrearsBefore;
            $arrearsDeltaLabel = $delta === 0 ? null : (($delta > 0 ? '+' : '').$delta);
            $arrearsHint = 'vs term start';
        }

        return [
            'context' => $termLabel ?: 'All classes',
            'collected_label' => 'UGX '.$this->compactMoney($collected),
            'outstanding_label' => 'UGX '.$this->compactMoney($outstanding),
            'arrears_label' => (string) $arrears,
            'rate_label' => $rate.'%',
            'arrears_direction' => $arrearsDirection,
            'arrears_delta_label' => $arrearsDeltaLabel,
            'arrears_hint' => $arrearsHint,
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function currentTermWindow(int $schoolId): array
    {
        $year = SiteHelper::getAcademicYear($schoolId);
        if (! $year) {
            return [null, null, null];
        }

        $term = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('status', 'current')
            ->orderByDesc('id')
            ->first();

        if (! $term) {
            $today = now()->toDateString();
            $term = AcademicTerm::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->whereDate('starts_on', '<=', $today)
                ->whereDate('ends_on', '>=', $today)
                ->orderByDesc('id')
                ->first();
        }

        if ($term) {
            $label = trim(($term->name ?: '').' '.($year->name ?: ''));

            return [
                $label !== '' ? $label.' · all classes' : ($year->name.' · all classes'),
                $term->starts_on?->toDateString(),
                $term->ends_on?->toDateString(),
            ];
        }

        return [$year->name ? $year->name.' · all classes' : null, null, null];
    }

    private function compactMoney(float $amount): string
    {
        if ($amount >= 1_000_000) {
            $m = $amount / 1_000_000;

            return rtrim(rtrim(number_format($m, 1, '.', ''), '0'), '.').'M';
        }
        if ($amount >= 1_000) {
            $k = $amount / 1_000;

            return rtrim(rtrim(number_format($k, 1, '.', ''), '0'), '.').'K';
        }

        return number_format($amount, 0);
    }
}
