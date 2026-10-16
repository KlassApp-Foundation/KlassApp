{{-- First stat: real linked WhatsApp numbers. The whole card opens the WhatsApp page. --}}
@php
    $waLinked = (int) ($connectedTools['whatsappLinked'] ?? 0);
@endphp
<div class="dv2-kpi">
    <a class="dv2-kpi-link" href="{{ url('/admin/whatsapp/dashboard') }}" data-testid="dashboard-v2-kpi-whatsapp" aria-label="WhatsApp, {{ $waLinked }} {{ $waLinked === 1 ? 'parent' : 'parents' }} reachable, open WhatsApp">
        <span class="dv2-kpi-label"><x-brand.whatsapp width="18" height="18" />WhatsApp</span>
        <span class="dv2-kpi-value" data-testid="dashboard-connected-whatsapp-count">{{ $waLinked }}</span>
        <p class="dv2-kpi-sub">{{ $waLinked === 1 ? 'parent reachable' : 'parents reachable' }}</p>
    </a>
</div>
