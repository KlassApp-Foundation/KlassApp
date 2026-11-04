{{-- SPDX-License-Identifier: MIT --}}
@php
    $chart = $grades['chart'] ?? ['labels' => [], 'scores' => []];
@endphp
<div class="mt-4" data-testid="results-performance-chart">
    <x-chart type="line" :height="220"
             :labels="$chart['labels'] ?? []"
             :datasets="[[
                'label' => 'Average',
                'data' => $chart['scores'] ?? [],
                'borderColor' => '#1E6FD9',
                'backgroundColor' => 'rgba(30,111,217,0.12)',
                'fill' => true,
                'tension' => 0.25,
             ]]"
             aria-label="Performance over time"
             empty-message="No released results to chart yet." />
</div>
