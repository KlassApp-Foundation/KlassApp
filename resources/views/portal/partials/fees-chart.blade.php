{{-- SPDX-License-Identifier: MIT --}}
@php
    $paid = (float) ($fees['total_paid'] ?? 0);
    $due = (float) ($fees['total_balance'] ?? 0);
@endphp
<div class="mt-4" data-testid="fees-chart">
    <x-chart type="bar" :height="220"
             :labels="['Paid', 'Due']"
             :datasets="[[
                'label' => 'Fees',
                'data' => [$paid, $due],
                'backgroundColor' => ['#15803D', '#B45309'],
             ]]"
             aria-label="Fees paid versus due"
             empty-message="No fees recorded yet." />
</div>
