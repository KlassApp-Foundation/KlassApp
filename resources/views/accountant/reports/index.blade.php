@extends('layouts.accountant.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Data Exports &amp; Reports</h1>
            <p class="dashboard-subtitle">Download financial and payroll reports.</p>
        </div>
    </div>

    @include('partials.message')

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Payroll Reports --}}
        <div class="ds-card p-5 flex flex-col items-start">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M3 4.5v13.5A1.5 1.5 0 004.5 19h15a1.5 1.5 0 001.5-1.5V4.5A1.5 1.5 0 0019.5 3h-15A1.5 1.5 0 003 4.5zm5 4h8M7.5 12h9M7.5 15h4.5"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-800 mb-1">Unpaid Payrolls</h3>
            <p class="text-sm text-gray-500 mb-4">View and export unpaid payroll records.</p>
            <div class="mt-auto flex gap-2">
                <a href="{{ url('accountant/unpaid/report') }}" class="ds-btn ds-btn-outline ds-btn-sm">View</a>
                <a href="{{ url('accountant/exportUnpaidpayroll') }}" class="ds-btn ds-btn-outline ds-btn-sm">Export CSV</a>
                <a href="{{ url('accountant/unpaid/bank') }}" class="ds-btn ds-btn-outline ds-btn-sm">Bank View</a>
            </div>
        </div>

        {{-- Fee Payment Records --}}
        <div class="ds-card p-5 flex flex-col items-start">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-800 mb-1">Payments</h3>
            <p class="text-sm text-gray-500 mb-4">View recorded fee payments.</p>
            <a href="{{ route('accountant.fee-payments') }}" class="mt-auto ds-btn ds-btn-outline ds-btn-sm">View Payments</a>
        </div>
    </div>
</div>
@endsection
