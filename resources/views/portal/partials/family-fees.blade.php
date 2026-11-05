{{-- SPDX-License-Identifier: MIT --}}
@php
    $fees = $bundle['fees'] ?? [];
    $balance = (float) ($fees['total_balance'] ?? 0);
@endphp
<div class="family-page-head">
    <div>
        <h1>Fees</h1>
        <p>Paid against what was due, in UGX</p>
    </div>
</div>
@include('portal.partials.fees-chart', ['fees' => $fees])
<section class="family-table-card">
    <div class="family-fees-head">
        <h2>Payments</h2>
        <strong class="family-owed">UGX {{ number_format($balance, 0) }} still owed</strong>
    </div>
    @if(!empty($fees['categories']))
        <p class="family-kicker" data-testid="fee-structure">Structure</p>
        <ul class="family-fee-list">
            @foreach($fees['categories'] as $category)
                <li><span>{{ $category['name'] }}</span><strong>UGX {{ number_format($category['amount'], 0) }}</strong></li>
            @endforeach
        </ul>
    @endif
    <p class="family-fee-line"><span data-testid="fee-paid">Paid</span><strong>UGX {{ number_format($fees['total_paid'] ?? 0, 0) }}</strong></p>
    <p class="family-fee-line"><span data-testid="fee-balance">Balance</span><strong data-testid="parent-fees-total">UGX {{ number_format($balance, 0) }}</strong></p>
    @foreach($fees['payments'] ?? [] as $payment)
        <p class="family-fee-line"><span>{{ $payment['date'] }} · {{ $payment['method'] }}</span><strong>UGX {{ number_format($payment['amount'], 0) }}</strong></p>
    @endforeach
</section>
