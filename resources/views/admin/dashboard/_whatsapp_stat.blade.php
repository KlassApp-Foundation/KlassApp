{{-- First stat: parents reachable (real linked WhatsApp numbers). Waiting link requests show as a separate link when above 0. --}}
@php
    $waLinked = (int) ($connectedTools['whatsappLinked'] ?? 0);
    $waPending = (int) ($connectedTools['whatsappPending'] ?? 0);
    $waReach = $waLinked === 1 ? 'parent reachable' : 'parents reachable';
@endphp
<div class="dv2-kpi dv2-kpi--whatsapp" data-testid="dashboard-v2-kpi-whatsapp-tile">
    <a class="dv2-kpi-link" href="{{ url('/admin/whatsapp/dashboard') }}" data-testid="dashboard-v2-kpi-whatsapp" aria-label="WhatsApp, {{ $waLinked }} {{ $waReach }}, open WhatsApp">
        <span class="dv2-kpi-label"><x-brand.whatsapp width="18" height="18" />WhatsApp</span>
        <span class="dv2-kpi-value" data-testid="dashboard-connected-whatsapp-count">{{ $waLinked }}</span>
        <p class="dv2-kpi-sub">{{ $waReach }}</p>
    </a>
    @if($waPending > 0)
        <a class="dv2-kpi-extra" href="{{ url('/admin/whatsapp/dashboard') }}" data-testid="dashboard-whatsapp-waiting">
            <span data-testid="dashboard-whatsapp-waiting-count">{{ $waPending }}</span> waiting
        </a>
    @endif
</div>
