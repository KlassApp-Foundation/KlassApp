@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Payments',
    'subtitle' => 'Track all fee payments and financial transactions.',
    'actions' => '<a href="' . route('admin.fee-payments.create') . '" class="px-3 py-1.5 rounded text-xs text-white bg-green-600 hover:bg-green-700 flex items-center gap-1"><i class="fa-solid fa-plus text-xs"></i> Record Payment</a>'
])

<div class="relative mt-4">
    <div class="bg-white rounded-lg shadow p-4">
            </a>
        </div>
    </div>

    @include('partials.message')

    <div class="overflow-x-auto">
        @if($payments->isEmpty())
            <p class="text-gray-500">No payments recorded yet. Use Toshi or the "Record Payment" button above.</p>
        @else
            <table class="min-w-full bg-white border border-gray-400 rounded">
                <thead class="bg-gray-200 text-left">
                    <tr>
                        <th class="py-2 px-4 border border-gray-400">#</th>
                        <th class="py-2 px-4 border border-gray-400">Student</th>
                        <th class="py-2 px-4 border border-gray-400">Amount</th>
                        <th class="py-2 px-4 border border-gray-400">Method</th>
                        <th class="py-2 px-4 border border-gray-400">Reference</th>
                        <th class="py-2 px-4 border border-gray-400">Fee Category</th>
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
                            <td class="py-2 px-4 border border-gray-400">{{ $payment->feeCategory->name ?? '-' }}</td>
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
