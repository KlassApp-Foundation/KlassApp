{{-- First stat. Waiting link requests replace the reachable count until the queue is clear. The whole card opens the WhatsApp page. --}}
@php
    $waLinked = (int) ($connectedTools['whatsappLinked'] ?? 0);
    $waPending = (int) ($connectedTools['whatsappPending'] ?? 0);
    $waWaiting = $waPending > 0;
    $waNoun = $waPending === 1 ? 'request waiting' : 'requests waiting';
    $waReach = $waLinked === 1 ? 'parent reachable' : 'parents reachable';
    $waAria = $waWaiting
        ? "WhatsApp, {$waPending} {$waNoun}, open WhatsApp"
        : "WhatsApp, {$waLinked} {$waReach}, open WhatsApp";
@endphp
<div class="dv2-kpi{{ $waWaiting ? ' dv2-kpi--waiting' : '' }}" @if($waWaiting) data-testid="dashboard-whatsapp-waiting" @endif>
    <a class="dv2-kpi-link" href="{{ url('/admin/whatsapp/dashboard') }}" data-testid="dashboard-v2-kpi-whatsapp" aria-label="{{ $waAria }}">
        <span class="dv2-kpi-label"><x-brand.whatsapp width="18" height="18" />WhatsApp</span>
        @if($waWaiting)
            <span class="dv2-kpi-value" data-testid="dashboard-whatsapp-waiting-count">{{ $waPending }}</span>
            <p class="dv2-kpi-sub">{{ $waNoun }}</p>
        @else
            <span class="dv2-kpi-value" data-testid="dashboard-connected-whatsapp-count">{{ $waLinked }}</span>
            <p class="dv2-kpi-sub">{{ $waReach }}</p>
        @endif
    </a>
</div>
