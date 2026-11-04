{{-- SPDX-License-Identifier: MIT --}}
@php
    $chart = $chart ?? ['labels' => [], 'present' => [], 'absent' => []];
@endphp
<div class="mt-4" data-testid="attendance-chart">
    <x-chart type="bar" :height="220"
             :labels="$chart['labels'] ?? []"
             :datasets="[
                ['label' => 'Present', 'data' => $chart['present'] ?? [], 'backgroundColor' => '#15803D'],
                ['label' => 'Absent', 'data' => $chart['absent'] ?? [], 'backgroundColor' => '#B45309'],
             ]"
             :options="['plugins' => ['legend' => ['display' => true]]]"
             aria-label="Attendance, present and absent"
             empty-message="No attendance recorded in the last month." />
</div>
