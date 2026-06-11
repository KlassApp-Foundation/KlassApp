@extends('layouts.admin.layout')

@section('content')
<div class="min-h-screen bg-slate-50">

    <form action="{{ isset($subscription)
            ? route('admin.subscriptions.update', $subscription)
            : route('admin.subscriptions.store') }}"
          method="POST">

        @csrf

        @isset($subscription)
            @method('PUT')
        @endisset

        <div class="mx-auto max-w-7xl px-6 py-8">

            {{-- Header --}}
            <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-700">
                        {{ isset($subscription)
                            ? 'Edit Subscription'
                            : 'Create Subscription' }}
                    </h1>

                    <p class="mt-2 text-sm text-gray-600">
                        Manage subscription details, billing information and lifecycle.
                    </p>
                </div>

                <a href="{{ route('admin.subscriptions.index') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-100">

                    <i class="fas fa-arrow-left"></i>
                    Back to Subscriptions
                </a>

            </div>
                    {{-- Payment Information --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

                        <div class="mb-6 flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                                <i class="fas fa-credit-card"></i>
                            </div>

                            <div>
                                <h3 class="font-semibold text-slate-900">
                                    Payment Information
                                </h3>

                                <p class="text-sm text-slate-500">
                                    Billing and transaction details.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">

                            {{-- Amount --}}
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">
                                    Amount Paid
                                </label>

                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                        💰
                                    </span>

                                   <select
                                    name="plan_id"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                    <option value="__" >Select Amount</option>
                                    @foreach($plans as $plan)
                                        @if ($plan->amount > 0)
                                        <option value="{{ $plan->id }}"
                                            {{ old('plan_id', $subscription->plan_id ?? '') == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name . " UGX—" . $plan->amount }}
                                        </option>    
                                        @endif
                                    @endforeach

                                </select>

                                
                                </div>
                            </div>

                            {{-- Payment Method --}}
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">
                                    Payment Method
                                </label>

                                <input
                                    type="text"
                                    name="payment_method"
                                    value="{{ old('payment_method', $subscription->payment_method ?? '') }}"
                                    placeholder="Paystack, Flutterwave..."
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                            </div>

                            {{-- Reference --}}
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700">
                                    Payment Reference
                                </label>

                                <input
                                    type="text"
                                    name="payment_reference"
                                    value="{{ old('payment_reference', $subscription->payment_reference ?? '') }}"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                            </div>

                        </div>

                    </div>

                </div>

                {{-- Sidebar --}}
                <div class="lg:col-span-4">

                    <div class="sticky top-6 space-y-6">

                        {{-- Subscription Period --}}
                        

                        {{-- Save Card --}}
                        <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-blue-600 to-cyan-500 p-[1px] shadow-xl">

                            <div class="rounded-3xl bg-slate-900 p-6 ">

                                <div class="mb-5">
                                    <h3 class="text-xl font-bold">
                                        Ready to Save?
                                    </h3>

                                    <p class="mt-2 text-sm text-slate-300">
                                        Double-check the information before submitting.
                                    </p>
                                </div>

                                <button
                                    type="submit"
                                    class="flex  items-center justify-center gap-2 rounded bg-green-600 px-5 py-4 font-semibold text-slate-900 transition hover:scale-[1.02] hover:bg-slate-100 text-white">

                                    <i class="fas fa-save"></i>

                                    {{ isset($subscription)
                                        ? 'Update Subscription'
                                        : 'Create Subscription' }}

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>
@endsection