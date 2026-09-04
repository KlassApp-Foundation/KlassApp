@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

<div class="ds-page-head">
    <div>
        <h1 class="ds-page-head-title">Payments</h1>
        <p class="ds-page-head-sub">Track all fee payments and financial transactions.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-button href="{{ route('admin.fee-payments.create') }}" variant="success" size="sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Record Payment
        </x-button>
    </div>
</div>

@include('partials.message')

@if($payments->isEmpty())
    <x-card padding="lg" class="text-center">
        <p class="text-gray-400 text-sm">No payments recorded yet. Use Toshi or the "Record Payment" button above.</p>
    </x-card>
@else
    <x-table :headers="['#', 'Student', 'Amount', 'Method', 'Reference', 'Fee Category', 'Paid On', 'Recorded By']" striped hover class="mt-4">
        @foreach($payments as $payment)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="font-medium">{{ $payment->student->name ?? 'Deleted' }}</td>
            <td>{{ number_format($payment->amount, 0) }} UGX</td>
            <td>{{ $payment->payment_method ?? '-' }}</td>
            <td><code>{{ $payment->reference ?? '-' }}</code></td>
            <td>
                @if($payment->feeCategory)
                    {{ $payment->feeCategory->name }}@if(optional($payment->feeCategory->standard)->name) ({{ $payment->feeCategory->standard->name }})@endif
                @else
                    —
                @endif
            </td>
            <td>{{ \Carbon\Carbon::parse($payment->paid_on)->format('d M Y') }}</td>
            <td>{{ $payment->recorder->name ?? '—' }}</td>
        </tr>
        @endforeach
    </x-table>

    <div class="mt-4">
        {{ $payments->links() }}
    </div>
@endif

</div>
@endsection
