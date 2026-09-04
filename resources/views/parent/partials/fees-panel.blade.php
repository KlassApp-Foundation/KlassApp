{{-- SPDX-License-Identifier: MIT --}}
@props(['fees'])

<div class="px-4 md:px-5 py-4" data-testid="parent-fees-panel-body">
    @if ($fees && !empty($fees['categories']))
        <ul class="space-y-3">
            @foreach ($fees['categories'] as $category)
                <li class="flex items-start justify-between gap-3 text-sm">
                    <span style="color: var(--d-text);">{{ $category['name'] }}</span>
                    <span class="font-semibold whitespace-nowrap" style="color: var(--d-text);">
                        UGX {{ number_format($category['amount'], 0) }}
                    </span>
                </li>
            @endforeach
        </ul>
        <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between text-sm font-semibold">
            <span>Total outstanding</span>
            <span data-testid="parent-fees-total">UGX {{ number_format($fees['total_balance'] ?? 0, 0) }}</span>
        </div>
        <p class="text-xs mt-3" style="color: var(--d-muted);">
            Paid to date: UGX {{ number_format($fees['total_paid'] ?? 0, 0) }}. Contact the school office for payment status.
        </p>
    @else
        <div class="ds-empty-state py-6">
            <p class="ds-empty-state-title">No fee structure found</p>
            <p class="ds-empty-state-desc">Contact the school office for details.</p>
        </div>
    @endif
</div>
