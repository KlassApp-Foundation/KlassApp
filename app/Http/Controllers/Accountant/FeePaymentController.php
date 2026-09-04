<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\User;
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

        return view('accountant.fees.payments', compact('payments'));
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

        $validated = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'amount'         => 'required|numeric|min:1',
            'fee_category_id'=> [
                'required',
                'exists:fees_categories,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($schoolId): void {
                    if (! FeesCategories::where('school_id', $schoolId)->where('id', $value)->exists()) {
                        $fail('The selected fee category does not belong to your school.');
                    }
                },
            ],
            'payment_method' => 'nullable|string|max:50',
            'reference'      => 'nullable|string|max:255',
            'paid_on'        => 'nullable|date',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $student = User::where('school_id', $schoolId)->where('id', $validated['user_id'])->first();
        if (!$student) {
            return back()->withErrors(['user_id' => 'Student not found in your school.']);
        }

        FeePayment::create([
            'school_id'       => $schoolId,
            'fee_category_id' => $validated['fee_category_id'],
            'user_id'         => $validated['user_id'],
            'amount'          => $validated['amount'],
            'paid_on'         => $validated['paid_on'] ?? now()->toDateString(),
            'payment_method'  => $validated['payment_method'] ?? null,
            'reference'       => $validated['reference'] ?? null,
            'notes'           => $validated['notes'] ?? null,
            'recorded_by'     => Auth::id(),
            'status'          => 'paid',
        ]);

        return redirect()->route('accountant.fee-payments')->with('successmessage', 'Payment recorded successfully!');
    }
}
