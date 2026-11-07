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
use App\Services\FeePaymentRecorder;
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

    public function searchStudents(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return response()->json(['students' => []]);
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        $students = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($like, $schoolId) {
                $query->where('name', 'like', $like)
                    ->orWhere('registration_number', 'like', $like)
                    ->orWhereHas('userprofile', function ($profile) use ($like) {
                        $profile->where('registration_number', 'like', $like);
                    })
                    ->orWhereHas('studentAcademicLatest.standardLink.section', function ($section) use ($like, $schoolId) {
                        $section->where('sections.school_id', $schoolId)
                            ->where('sections.name', 'like', $like);
                    });
            })
            ->with(['studentAcademicLatest.standardLink.section', 'userprofile'])
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'students' => $students->map(fn (User $student) => [
                'id' => $student->id,
                'name' => $student->displayName ?: $student->name,
                'admission_number' => $student->admissionNumber(),
                'class' => $student->studentAcademicLatest?->standardLink?->section?->name,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $recorder = app(FeePaymentRecorder::class);
        $validated = $request->validate($recorder->rules($schoolId));
        $recorder->record($schoolId, (int) Auth::id(), $validated);

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
