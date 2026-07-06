@extends('layouts.accountant.layout')

@section('content')
<div class="container mx-auto p-2">
    <div class="flex items-center justify-between mb-4 p-2 bg-gray-100 rounded shadow">
        <h2 class="text-lg font-semibold">Payment Records</h2>
        <a href="{{ route('accountant.fee-payments.create') }}"
           class="bg-green-500 text-white py-1 px-3 rounded hover:bg-green-600">
            + Record Payment
        </a>
    </div>

    @include('partials.message')

    <div class="overflow-x-auto">
        @if($payments->isEmpty())
            <p class="text-gray-500">No payments recorded yet.</p>
        @else
            <table class="min-w-full bg-white border border-gray-400 rounded">
                <thead class="bg-gray-200 text-left">
                    <tr>
                        <th class="py-2 px-4 border border-gray-400">#</th>
                        <th class="py-2 px-4 border border-gray-400">Student</th>
                        <th class="py-2 px-4 border border-gray-400">Amount</th>
                        <th class="py-2 px-4 border border-gray-400">Method</th>
                        <th class="py-2 px-4 border border-gray-400">Reference</th>
                        <th class="py-2 px-4 border border-gray-400">Paid On</th>
                        <th class="py-2 px-4 border border-gray-400">Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border border-gray-400">{{ $loop->iteration }}</td>
                            <td class="py-2 px-4 border border-gray-400">{{ $payment->student->name ?? 'Deleted' }}</td>
                            <td class="py-2 px-4 border border-gray-400">{{ number_format($payment->amount, 0) }} UGX</td>
                            <td class="py-2 px-4 border border-gray-400">{{ $payment->payment_method ?? '-' }}</td>
                            <td class="py-2 px-4 border border-gray-400">{{ $payment->reference ?? '-' }}</td>
                            <td class="py-2 px-4 border border-gray-400">{{ \Carbon\Carbon::parse($payment->paid_on)->format('d M Y') }}</td>
                            <td class="py-2 px-4 border border-gray-400">{{ $payment->recorder->name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
