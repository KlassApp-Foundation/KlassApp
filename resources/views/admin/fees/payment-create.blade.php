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

        <x-form-group label="Student" name="user_id" type="select" required
            :options="$students->pluck('name', 'id')->prepend('Select student...', '')->toArray()"
            :error="$errors->first('user_id') ?? null" />

        <x-form-group label="Amount (UGX)" name="amount" type="number" required
            placeholder="e.g. 250000"
            :error="$errors->first('amount') ?? null" />

        <x-form-group label="Fee Category" name="fee_category_id" type="select" required
            :options="['' => 'Select fee category...'] + $feeCategories->mapWithKeys(function ($fee) {
                return [$fee->id => $fee->labeledName().' — '.number_format((float) $fee->amount, 0).' UGX'];
            })->toArray()"
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
