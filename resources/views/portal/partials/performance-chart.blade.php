{{-- SPDX-License-Identifier: MIT --}}
@php
    $chart = $grades['chart'] ?? ['labels' => [], 'scores' => []];
    $labels = array_values($chart['labels'] ?? []);
    $scores = array_values($chart['scores'] ?? []);
    $count = count($scores);
    $width = 640;
    $height = 200;
    $left = 36;
    $right = 16;
    $top = 28;
    $bottom = 36;
    $plotW = $width - $left - $right;
    $plotH = $height - $top - $bottom;
    $points = [];
    foreach ($scores as $i => $score) {
        $x = $count === 1 ? $left + ($plotW / 2) : $left + ($plotW * ($i / max(1, $count - 1)));
        $y = $top + $plotH - ((max(0, min(100, (float) $score)) / 100) * $plotH);
        $points[] = [round($x, 1), round($y, 1), (int) round((float) $score), (string) ($labels[$i] ?? '')];
    }
    $poly = implode(' ', array_map(fn ($point) => $point[0].','.$point[1], $points));
    $described = implode(', ', array_map(fn ($point) => $point[3].' '.$point[2], $points));
@endphp
<div class="family-chart-card" data-testid="results-performance-chart">
    @if($points === [])
        <p class="family-chart-empty">No released results to chart yet.</p>
    @else
        <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="Released averages: {{ $described }}" class="family-line-chart">
            <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $top + $plotH }}" y2="{{ $top + $plotH }}" stroke="#E2E8F0"></line>
            <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $top + ($plotH / 2) }}" y2="{{ $top + ($plotH / 2) }}" stroke="#E2E8F0"></line>
            <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $top }}" y2="{{ $top }}" stroke="#E2E8F0"></line>
            <polyline points="{{ $poly }}" fill="none" stroke="#1E6FD9" stroke-width="3"></polyline>
            @foreach($points as $point)
                <circle cx="{{ $point[0] }}" cy="{{ $point[1] }}" r="5" fill="#1E6FD9"></circle>
                <text x="{{ $point[0] }}" y="{{ $point[1] - 10 }}" text-anchor="middle" font-size="12" font-weight="700" fill="#1E293B">{{ $point[2] }}</text>
                <text x="{{ $point[0] }}" y="{{ $height - 8 }}" text-anchor="middle" font-size="10" fill="#475569">{{ $point[3] }}</text>
            @endforeach
        </svg>
    @endif
</div>
