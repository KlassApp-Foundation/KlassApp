{{-- SPDX-License-Identifier: MIT --}}
@php
    $weeks = $attendance['weeks'] ?? ($weeks ?? []);
    if ($weeks === [] && ! empty($chart['labels'])) {
        foreach ($chart['labels'] as $i => $label) {
            $weeks[] = [
                'label' => $label,
                'present' => (int) round((float) ($chart['present'][$i] ?? 0)),
                'full' => true,
            ];
        }
    }
    $max = 5;
    foreach ($weeks as $week) {
        $max = max($max, (int) $week['present']);
    }
@endphp
<div class="family-chart-card" data-testid="attendance-chart">
    @if($weeks === [])
        <p class="family-chart-empty">No attendance recorded for this period.</p>
    @else
        <div class="family-bars" role="img" aria-label="Days present by week">
            @foreach($weeks as $week)
                @php $present = (int) $week['present']; @endphp
                <div class="family-bar">
                    <span class="family-bar__value">{{ $present }}</span>
                    <span class="family-bar__fill {{ !empty($week['full']) ? 'is-full' : 'is-short' }}" style="height: {{ (int) round(($present / $max) * 120) }}px;"></span>
                    <span class="family-bar__label">{{ $week['label'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="family-legend">
            <span><i class="is-full"></i> Full week</span>
            <span><i class="is-short"></i> Missed a day</span>
        </div>
    @endif
</div>
