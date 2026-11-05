@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell px-4 md:px-6 py-4">

<div class="ds-page-head">
    <div>
        <h1 class="ds-page-head-title">Record Payment</h1>
    </div>
    <div class="flex items-center gap-2">
        <x-button href="{{ route('admin.fee-payments') }}" variant="ghost" size="sm">
            &larr; Back
        </x-button>
    </div>
</div>

@include('partials.message')

<x-card shadow="md" class="max-w-lg">
    <form method="POST" action="{{ route('admin.fee-payments.store') }}">
        @csrf

        <div class="ds-form-group">
            <label class="ds-form-label ds-form-label-required" for="fee-student-search-page">Student</label>
            <input type="search" id="fee-student-search-page" class="ds-form-input" data-testid="fee-student-search" placeholder="Name, admission number, or class" autocomplete="off">
            <input type="hidden" name="user_id" id="fee-student-id-page" value="{{ old('user_id') }}">
            <ul id="fee-student-results-page" data-testid="fee-student-results" hidden style="margin:6px 0 0; padding:0; list-style:none; border:1px solid #CBD5E1; background:#fff; color:#0F172A;"></ul>
            @if($errors->first('user_id'))
                <p class="ds-form-error">{{ $errors->first('user_id') }}</p>
            @endif
        </div>

        <x-form-group label="Amount (UGX)" name="amount" type="number" required
            placeholder="e.g. 250000"
            :error="$errors->first('amount') ?? null" />

        <x-form-group label="Allocate to a fee" name="fee_category_id" type="select"
            :options="['' => 'Against the student\'s total'] + $feeCategories->mapWithKeys(function ($fee) {
                return [$fee->id => $fee->labeledName().' — '.number_format((float) $fee->amount, 0).' UGX'];
            })->toArray()"
            help="Leave this on the student's total. Choose a fee only when the payment belongs to that fee."
            :error="$errors->first('fee_category_id') ?? null" />

        <x-form-group label="Payment Method" name="payment_method" type="select"
            :options="['' => '-- Select --', 'cash' => 'Cash', 'cheque' => 'Cheque', 'bank_transfer' => 'Bank Transfer', 'mobile_money' => 'Mobile Money']"
            :error="$errors->first('payment_method') ?? null" />

        <x-form-group label="Reference" name="reference" type="text"
            placeholder="Optional reference number"
            :error="$errors->first('reference') ?? null" />

        <x-form-group label="Paid On" name="paid_on" type="date"
            :value="date('Y-m-d')"
            :error="$errors->first('paid_on') ?? null" />

        <div class="flex justify-end mt-6">
            <x-button type="submit" variant="primary">Record Payment</x-button>
        </div>
    </form>
</x-card>

</div>
@endsection

@push('scripts')
<script>
(function () {
    var search = document.getElementById('fee-student-search-page');
    var results = document.getElementById('fee-student-results-page');
    var studentId = document.getElementById('fee-student-id-page');
    if (!search || !results || !studentId) return;
    var searchUrl = @json(route('admin.fee-payments.students'));
    var timer = null;
    search.addEventListener('input', function () {
        studentId.value = '';
        window.clearTimeout(timer);
        var q = search.value.trim();
        if (q.length < 1) {
            results.hidden = true;
            return;
        }
        timer = window.setTimeout(function () {
            fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    results.innerHTML = '';
                    (payload.students || []).forEach(function (student) {
                        var item = document.createElement('li');
                        var button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = student.name + (student.admission_number ? ' · ' + student.admission_number : '') + (student.class ? ' · ' + student.class : '');
                        button.addEventListener('click', function () {
                            studentId.value = String(student.id);
                            search.value = student.name;
                            results.hidden = true;
                        });
                        item.appendChild(button);
                        results.appendChild(item);
                    });
                    results.hidden = (payload.students || []).length === 0;
                });
        }, 200);
    });
})();
</script>
@endpush
