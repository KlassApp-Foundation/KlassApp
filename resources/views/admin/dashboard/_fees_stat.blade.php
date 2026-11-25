{{-- Fees collected. No fee structure is an honest empty state: never a percent, never "0 of 0". --}}
@php
    $feeTile = $kpis['fees'] ?? [];
    $feeOk = ($feeTile['state'] ?? 'no_structure') === 'ok' && ($feeTile['percent'] ?? null) !== null;
    $feesSetupUrl = $setup['routes']['fees'] ?? url('/admin/fees-categories');
@endphp
<div class="dv2-kpi" data-testid="dashboard-v2-kpi-fees-tile">
    @if($feeOk)
        <a class="dv2-kpi-link" href="{{ url('/admin/fees/payments') }}" aria-label="Fees collected, open fees">
            <span class="dv2-kpi-label"><x-ka-icon name="wallet" :size="16" tone="muted" />Fees collected</span>
            <span class="dv2-kpi-value" data-testid="dashboard-v2-kpi-fees">{{ $feeTile['percent'] }}%</span>
            <span class="dv2-meter" role="img" aria-label="{{ $feeTile['percent'] }}% collected"><i style="width:{{ $feeTile['percent'] }}%"></i></span>
            <p class="dv2-kpi-sub">{{ $feeTile['collected_label'] }} of {{ $feeTile['expected_label'] }}</p>
            @if(($currency ?? null) === null)
                <p class="dv2-kpi-sub dv2-currency-hint" data-testid="dashboard-v2-currency-hint">Set your currency</p>
            @endif
        </a>
    @else
        <span class="dv2-kpi-label"><x-ka-icon name="wallet" :size="16" tone="muted" />Fees collected</span>
        <span class="dv2-kpi-value dv2-kpi-value--text" data-testid="dashboard-v2-kpi-fees-empty">Not set up yet</span>
        <a class="dv2-kpi-extra" href="{{ $feesSetupUrl }}" data-testid="dashboard-v2-set-up-fees">Set up fees</a>
    @endif
</div>
