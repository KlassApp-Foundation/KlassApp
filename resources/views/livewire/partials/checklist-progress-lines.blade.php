@php
    $paceDone = null;
    $paceTotal = null;
    if (preg_match('/(\d+) of (\d+)/', (string) ($line['result'] ?? ''), $paceMatch) === 1) {
        $paceDone = (int) $paceMatch[1];
        $paceTotal = (int) $paceMatch[2];
    }
@endphp
<div class="t-line t-line-{{ $line['status'] }}" data-testid="progress-line" data-progress-status="{{ $line['status'] }}" data-progress-id="{{ $line['line'] }}">
    <span class="t-mark" aria-hidden="true">
        @if($line['status'] === 'running')
            <span class="t-spin"></span>
        @elseif($line['status'] === 'problem')
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 4.8 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.8a2 2 0 0 0-3.4 0z"/></svg>
        @else
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        @endif
    </span>
    <span class="t-line-label">{{ $line['label'] }}@if($line['status'] === 'running')…@endif</span>
    <span class="t-line-result">@if($line['status'] !== 'running' && ($line['result'] ?? '') !== ''){{ $line['result'] }}@endif</span>
    @if($paceTotal)
        <span class="t-meter" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $paceTotal }}" aria-valuenow="{{ $paceDone }}"><i style="width: {{ (int) round(($paceDone / max(1, $paceTotal)) * 100) }}%"></i></span>
    @endif
</div>
