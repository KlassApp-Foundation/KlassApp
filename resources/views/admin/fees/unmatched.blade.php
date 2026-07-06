{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell px-4 md:px-6 py-4">

<div class="ds-page-head">
    <div>
        <h1 class="ds-page-head-title">Unmatched Payments</h1>
        <p class="ds-page-head-sub">School Pay transactions that need manual fee category assignment.</p>
    </div>
</div>

@include('partials.message')

@if($transactions->count() === 0)
    <x-card padding="lg" class="text-center">
        <p class="text-gray-400 text-sm">All payments have been matched to fee categories.</p>
    </x-card>
@else
    <x-table :headers="['Date', 'Student', 'Receipt', 'Amount', 'Channel', 'Match Fee Category']" hover class="mt-4">
        @foreach($transactions as $txn)
        <tr>
            <td class="text-xs whitespace-nowrap">
                {{ $txn->paid_at ? $txn->paid_at->format('d M Y H:i') : '—' }}
            </td>
            <td>
                <span class="font-medium">{{ $txn->student_name ?: 'Unknown' }}</span>
                <span class="text-gray-400 text-xs block">{{ $txn->student_payment_code }}</span>
            </td>
            <td><code>{{ $txn->schoolpay_receipt }}</code></td>
            <td class="font-medium">UGX {{ number_format($txn->amount, 0) }}</td>
            <td>
                @if($txn->channel === 'schoolpay')
                    <x-badge variant="info" size="sm">SchoolPay</x-badge>
                @else
                    <x-badge variant="warning" size="sm">{{ $txn->channel ?? '—' }}</x-badge>
                @endif
            </td>
            <td>
                <form action="{{ route('admin.fee-payments.match', $txn) }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    <select name="fee_category_id" class="ds-form-input ds-form-select text-xs py-1 w-40">
                        <option value="">— Select —</option>
                        @foreach($feeCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <x-button type="submit" variant="primary" size="sm">Match</x-button>
                </form>
            </td>
        </tr>
        @endforeach
    </x-table>
@endif

</div>
@endsection
