{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Unmatched Payments</h1>
            <p class="dashboard-subtitle">School Pay transactions that need manual fee category assignment.</p>
        </div>
    </div>

    @include('partials.message')

    @if($transactions->count() === 0)
        <div class="bg-white rounded-lg shadow border border-gray-100 p-8 text-center text-gray-400 text-sm">
            All payments have been matched to fee categories.
        </div>
    @else
        <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-5 py-3 font-semibold">Date</th>
                            <th class="px-5 py-3 font-semibold">Student</th>
                            <th class="px-5 py-3 font-semibold">Receipt</th>
                            <th class="px-5 py-3 font-semibold">Amount</th>
                            <th class="px-5 py-3 font-semibold">Channel</th>
                            <th class="px-5 py-3 font-semibold">Match Fee Category</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($transactions as $txn)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 text-gray-600 whitespace-nowrap text-xs">
                                    {{ $txn->paid_at ? $txn->paid_at->format('d M Y H:i') : '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-gray-700 font-medium">{{ $txn->student_name ?: 'Unknown' }}</span>
                                    <span class="text-gray-400 text-xs block">{{ $txn->student_payment_code }}</span>
                                </td>
                                <td class="px-5 py-4 text-gray-500 text-xs font-mono">
                                    {{ $txn->schoolpay_receipt }}
                                </td>
                                <td class="px-5 py-4 text-gray-700 font-medium">
                                    UGX {{ number_format($txn->amount, 0) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                        {{ $txn->channel ?: '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <form method="POST" action="{{ route('admin.fee-payments.match', $txn->id) }}" class="flex gap-2 items-center">
                                        @csrf
                                        <select name="fee_category_id" class="text-xs border rounded px-2 py-1.5 w-40" required>
                                            <option value="">Select category...</option>
                                            @foreach($feeCategories as $cat)
                                                <option value="{{ $cat->id }}">
                                                    {{ $cat->name }} (UGX {{ number_format($cat->amount, 0) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                                class="px-3 py-1.5 text-xs font-medium rounded text-white border-0"
                                                style="background:#1E6FD9;">
                                            Match
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="px-5 py-3 border-t border-gray-100">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
