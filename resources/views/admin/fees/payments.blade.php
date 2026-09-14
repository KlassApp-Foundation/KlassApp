@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" data-testid="fees-payments">

<div class="ds-page-head" data-testid="fees-page-head">
    <div>
        <h1 class="ds-page-head-title">Fee payments</h1>
        <p class="ds-page-head-sub" data-testid="fees-page-sub">{{ $kpis['context'] }}</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        @if($paymentRecorded)
            <span class="ds-save-indicator ds-save-indicator--saved" data-testid="fees-save-indicator" id="fees-save-indicator">
                <span class="ds-save-indicator__dot" aria-hidden="true"></span>
                Payment recorded
            </span>
        @endif
        <a href="{{ url('/admin/report/fees') }}" class="ds-btn ds-btn-ghost text-sm">Export statement</a>
        <button type="button" class="ds-btn ds-btn-primary text-sm" id="fees-record-toggle" data-testid="fees-record-toggle" aria-expanded="{{ $showRecordForm ? 'true' : 'false' }}" aria-controls="fees-record-form">
            Record payment
        </button>
    </div>
</div>

@include('partials.message')

<div class="dashboard-kpi-grid" data-testid="fees-kpi-grid" style="margin-top: 0; margin-bottom: 20px;">
    <x-ds-kpi-card icon="dollar" :value="$kpis['collected_label']" label="Collected this term" color="green" />
    <x-ds-kpi-card icon="money" :value="$kpis['outstanding_label']" label="Outstanding" color="amber" />
    <x-ds-kpi-card icon="users" :value="$kpis['arrears_label']" label="Students in arrears" color="red" />
    <x-ds-kpi-card icon="check" :value="$kpis['rate_label']" label="Collection rate" color="blue" />
</div>

<div id="fees-record-form" data-testid="fees-record-form" @if(! $showRecordForm) hidden @endif style="margin-bottom: 16px;">
    <x-card title="Record a payment">
        <form method="POST" action="{{ route('admin.fee-payments.store') }}" id="fees-inline-record-form">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4">
                <x-form-group
                    label="Student"
                    name="user_id"
                    type="select"
                    required
                    :options="$students->mapWithKeys(fn ($s) => [$s->id => ($s->displayName ?: $s->name)])->prepend('Select student...', '')->toArray()"
                    :error="$errors->first('user_id')"
                />
                <x-form-group
                    label="Amount (UGX)"
                    name="amount"
                    type="number"
                    required
                    placeholder="450000"
                    help="Figures only, no separators."
                    :error="$errors->first('amount')"
                />
                <x-form-group
                    label="Method"
                    name="payment_method"
                    type="select"
                    :options="['' => '-- Select --', 'schoolpay' => 'SchoolPay', 'mobile_money' => 'Mobile Money', 'cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque']"
                    :error="$errors->first('payment_method')"
                />
            </div>
            <x-form-group
                label="Fee Category"
                name="fee_category_id"
                type="select"
                required
                :options="['' => 'Select fee category...'] + $feeCategories->mapWithKeys(function ($fee) {
                    return [$fee->id => $fee->labeledName().' — '.number_format((float) $fee->amount, 0).' UGX'];
                })->toArray()"
                :error="$errors->first('fee_category_id')"
            />
            <div class="flex flex-wrap gap-2 mt-1">
                <button type="submit" class="ds-btn ds-btn-primary text-sm" id="fees-save-payment">Save payment</button>
                <button type="button" class="ds-btn ds-btn-ghost text-sm" id="fees-record-cancel">Cancel</button>
            </div>
            <p class="ds-form-help" style="margin-top: 10px;">The parent gets a WhatsApp receipt as soon as you save.</p>
        </form>
    </x-card>
</div>

@if($payments->isEmpty())
    <div data-testid="fees-empty">
        <x-card padding="lg" class="text-center">
            <p class="ds-empty-state-title">No payments recorded yet</p>
            <p class="ds-empty-state-desc">Use Record payment above, or open the full form.</p>
        </x-card>
    </div>
@else
    <div data-testid="fees-ledger-card">
        <x-card padding="none">
            <div data-testid="fees-ledger">
                <x-table :headers="['Date', 'Student', 'Class', 'Amount', 'Method', 'Status']" sortable striped>
                    @foreach($payments as $payment)
                        @php
                            $className = $payment->student?->studentAcademicLatest?->standardLink?->section?->name
                                ?? $payment->feeCategory?->standard?->name
                                ?? null;
                            $status = $payment->status ?: 'paid';
                        @endphp
                        <tr>
                            <td data-label="Date">{{ \Carbon\Carbon::parse($payment->paid_on)->format('d M') }}</td>
                            <td data-label="Student">
                                @if($payment->student)
                                    <a class="dt-name-link" href="{{ url('/admin/student/edit/' . $payment->student->name) }}">
                                        {{ $payment->student->displayName ?: $payment->student->name }}
                                    </a>
                                @else
                                    Deleted
                                @endif
                            </td>
                            <td data-label="Class">{{ $className ?: '—' }}</td>
                            <td class="dt-cell-num" data-label="Amount">UGX {{ number_format($payment->amount, 0) }}</td>
                            <td data-label="Method">{{ $payment->payment_method ?: '—' }}</td>
                            <td class="dt-cell-badge" data-label="Status">
                                @if($status === 'paid')
                                    <span class="dt-badge dt-badge-active">Paid</span>
                                @elseif($status === 'partial' || $status === 'warning')
                                    <span class="dt-badge" style="background:#FFFBEB;color:#B45309;">Part paid</span>
                                @else
                                    <span class="dt-badge dt-badge-inactive">{{ ucfirst($status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
            <div class="dt-pagination px-4">
                {{ $payments->links() }}
            </div>
        </x-card>
    </div>
@endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    function formWrap() { return document.getElementById('fees-record-form'); }
    function toggleBtn() { return document.getElementById('fees-record-toggle'); }

    function setOpen(open) {
        var form = formWrap();
        var toggle = toggleBtn();
        if (!form || !toggle) return;
        form.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    // Delegate: Vue remounts #app and replaces the button after this script runs.
    document.addEventListener('click', function (e) {
        if (e.target.closest('#fees-record-toggle')) {
            var form = formWrap();
            if (!form) return;
            setOpen(!!form.hidden);
            return;
        }
        if (e.target.closest('#fees-record-cancel')) {
            setOpen(false);
        }
    });

    document.addEventListener('submit', function (e) {
        if (!e.target || e.target.id !== 'fees-inline-record-form') return;
        var btn = document.getElementById('fees-save-payment');
        if (!btn) return;
        var saving = document.createElement('span');
        saving.className = 'ds-save-indicator ds-save-indicator--saving';
        saving.setAttribute('data-testid', 'fees-save-saving');
        saving.innerHTML = '<span class="ds-save-indicator__dot" aria-hidden="true"></span>Saving…';
        btn.insertAdjacentElement('beforebegin', saving);
    });

    var indicator = document.getElementById('fees-save-indicator');
    if (indicator) {
        setTimeout(function () {
            indicator.style.opacity = '0';
            setTimeout(function () { indicator.remove(); }, 300);
        }, 2600);
    }
})();
</script>
@endpush
