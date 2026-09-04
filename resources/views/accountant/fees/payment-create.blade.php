@extends('layouts.accountant.layout')

@section('content')
<div class="container mx-auto p-2">
    <div class="flex items-center justify-between mb-4 p-2 bg-gray-100 rounded shadow">
        <h2 class="text-lg font-semibold">Record Payment</h2>
        <a href="{{ route('accountant.fee-payments') }}"
           class="bg-gray-500 text-white py-1 px-3 rounded hover:bg-gray-600">
            &larr; Back
        </a>
    </div>

    @include('partials.message')
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('accountant.fee-payments.store') }}" class="bg-white p-6 rounded shadow max-w-lg">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Student *</label>
            <select name="user_id" required
                    class="w-full border border-gray-400 rounded px-3 py-2">
                <option value="">Select student...</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->displayName ?: $student->name }} ({{ $student->email }})</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Amount (UGX) *</label>
            <input type="number" name="amount" min="1" step="0.01" required
                   class="w-full border border-gray-400 rounded px-3 py-2" placeholder="e.g. 250000">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Fee Category *</label>
            <select name="fee_category_id" required
                    class="w-full border border-gray-400 rounded px-3 py-2">
                <option value="">Select fee category...</option>
                @foreach($feeCategories as $fee)
                    <option value="{{ $fee->id }}">{{ $fee->labeledName() }} ({{ number_format($fee->amount, 0) }} UGX)</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Payment Method</label>
            <select name="payment_method"
                    class="w-full border border-gray-400 rounded px-3 py-2">
                <option value="">-- Select --</option>
                <option value="cash">Cash</option>
                <option value="cheque">Cheque</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="bank_transfer">Bank Transfer</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Reference</label>
            <input type="text" name="reference" maxlength="255"
                   class="w-full border border-gray-400 rounded px-3 py-2" placeholder="Cheque number, transaction ID">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Paid On</label>
            <input type="date" name="paid_on" value="{{ date('Y-m-d') }}"
                   class="w-full border border-gray-400 rounded px-3 py-2">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Notes</label>
            <textarea name="notes" rows="2" maxlength="1000"
                      class="w-full border border-gray-400 rounded px-3 py-2" placeholder="Optional"></textarea>
        </div>

        <button type="submit"
                class="bg-green-500 text-white py-2 px-6 rounded hover:bg-green-600 font-medium">
            Record Payment
        </button>
    </form>
</div>
@endsection
