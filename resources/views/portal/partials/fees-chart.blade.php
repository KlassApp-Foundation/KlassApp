{{-- SPDX-License-Identifier: MIT --}}
@php
    $terms = $fees['terms'] ?? [];
    $max = 1;
    foreach ($terms as $term) {
        $max = max($max, (float) ($term['due'] ?? 0), (float) ($term['paid'] ?? 0));
    }
@endphp
<div class="family-chart-card" data-testid="fees-chart">
    @if($terms === [])
        <p class="family-chart-empty">No fees recorded yet.</p>
    @else
        <div class="family-fee-bars" role="img" aria-label="Fees paid against what was due">
            @foreach($terms as $term)
                @php
                    $due = (float) ($term['due'] ?? 0);
                    $paid = (float) ($term['paid'] ?? 0);
                    $dueH = (int) max(8, round(($due / $max) * 140));
                    $paidH = (int) max(8, round(($paid / $max) * 140));
                @endphp
                <div class="family-fee-col">
                    <div class="family-fee-pair">
                        <span class="family-fee-due" style="height: {{ $dueH }}px;" title="Due"></span>
                        <span class="family-fee-paid" style="height: {{ $paidH }}px;" title="Paid"></span>
                    </div>
                    <strong>{{ $term['name'] }}</strong>
                    <span>{{ number_format($paid, 0) }} of {{ number_format($due, 0) }}</span>
                </div>
            @endforeach
        </div>
        <div class="family-legend">
            <span><i class="is-due"></i> Due</span>
            <span><i class="is-paid"></i> Paid</span>
        </div>
    @endif
</div>
