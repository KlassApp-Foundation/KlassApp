<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\User;
use App\Services\FeePaymentRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeePaymentController extends Controller
{
    public function index()
    {
        $schoolId = Auth::user()->school_id;
        $payments = FeePayment::with(['student', 'feeCategory', 'recorder'])
            ->where('school_id', $schoolId)
            ->orderByDesc('created_at')
            ->paginate(50);

        // Same fee position the admin kit reports — the bursar records payments,
        // so the bursar must be able to see the resulting balance.
        $kpis = app(\App\Services\FeePositionService::class)->forSchool($schoolId);
        $paymentRecorded = session()->has('successmessage');

        return view('accountant.fees.payments', compact('payments', 'kpis', 'paymentRecorded'));
    }

    public function create()
    {
        $schoolId = Auth::user()->school_id;
        $students = User::where('school_id', $schoolId)->where('usergroup_id', 6)->orderBy('name')->paginate(50);
        $feeCategories = FeesCategories::with('standard')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        return view('accountant.fees.payment-create', compact('students', 'feeCategories'));
    }

    public function store(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $recorder = app(FeePaymentRecorder::class);
        $validated = $request->validate($recorder->rules((int) $schoolId));
        $recorder->record((int) $schoolId, (int) Auth::id(), $validated);

        return redirect()->route('accountant.fee-payments')->with('successmessage', 'Payment recorded successfully!');
    }
}
