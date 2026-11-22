{{-- "This term's results". Every number comes from DashboardV2DataService::marksDesk(). --}}
@php
    $desk = $v2['marks'] ?? [
        'waiting' => null, 'waiting_note' => 'This count is not available for this school.',
        'ready' => null, 'ready_note' => 'This count is not available for this school.',
        'cards_released' => null, 'cards_total' => 0,
        'next_step' => 'Next step: enter and approve marks, then release a class.',
    ];
    $cardsReleased = $desk['cards_released'] ?? null;
    $cardsOn = $cardsReleased !== null && (int) $cardsReleased > 0;
@endphp
<section class="dv2-card dv2-surface dv2-results" data-testid="admin-marks-desk" aria-label="This term's results">
    <h2>This term's results</h2>
    <div class="dv2-results-tiles">
        <a class="dv2-rtile" href="{{ route('admin.marks.submissions') }}" data-testid="admin-marks-waiting">
            <span class="dv2-rtile-label">Waiting for approval</span>
            @if($desk['waiting'] === null)
                <span class="dv2-rtile-value" data-testid="admin-marks-waiting-empty">–</span>
                <span class="dv2-rtile-note">{{ $desk['waiting_note'] }}</span>
            @else
                <span class="dv2-rtile-value" data-testid="admin-marks-waiting-count">{{ $desk['waiting'] }}</span>
            @endif
        </a>
        <a class="dv2-rtile" href="{{ route('admin.marks.releases.create') }}" data-testid="admin-marks-ready">
            <span class="dv2-rtile-label">Classes ready to release</span>
            @if($desk['ready'] === null)
                <span class="dv2-rtile-value" data-testid="admin-marks-ready-empty">–</span>
                <span class="dv2-rtile-note">{{ $desk['ready_note'] }}</span>
            @else
                <span class="dv2-rtile-value" data-testid="admin-marks-ready-count">{{ $desk['ready'] }}</span>
            @endif
        </a>
        <a class="dv2-rtile{{ $cardsOn ? ' dv2-rtile--on' : '' }}" href="{{ route('admin.reports.cards.index') }}" data-testid="admin-marks-cards">
            <span class="dv2-rtile-label">Report cards ready</span>
            @if($cardsReleased === null)
                <span class="dv2-rtile-value" data-testid="admin-marks-cards-empty">–</span>
                <span class="dv2-rtile-note">This count is not available for this school.</span>
            @else
                <span class="dv2-rtile-value" data-testid="admin-marks-cards-count">{{ $cardsReleased }} <small>of {{ (int) ($desk['cards_total'] ?? 0) }}</small></span>
            @endif
        </a>
    </div>
    <p class="dv2-results-next" data-testid="admin-marks-next">{{ $desk['next_step'] ?? 'Next step: enter and approve marks, then release a class.' }}</p>
</section>
