{{-- Connectors card. Order and statuses come from DashboardV2DataService::connectors(). --}}
@php $connectors = $v2['connectors'] ?? []; @endphp
<div class="dv2-card dv2-surface dv2-connectors" data-testid="dashboard-connectors">
    <h2>Connectors</h2>
    <ul class="dv2-connector-list">
        @foreach($connectors as $c)
            <li class="dv2-connector{{ $c['core'] ? ' dv2-connector--core' : '' }}" data-testid="dashboard-connector-{{ $c['key'] }}" data-status="{{ $c['status'] }}">
                <span class="dv2-connector-main">
                    <b>{{ $c['name'] }}</b>@if($c['core']) <span class="dv2-connector-core">CORE</span>@endif
                    @if($c['sub'])<small>{{ $c['sub'] }}</small>@endif
                </span>
                <span class="dv2-pill dv2-pill--{{ $c['tone'] }}" @if($c['key'] === 'google-drive' && $c['status'] === 'Connected') data-testid="dashboard-connected-drive" @endif>{{ $c['status'] }}</span>
            </li>
        @endforeach
    </ul>
</div>
