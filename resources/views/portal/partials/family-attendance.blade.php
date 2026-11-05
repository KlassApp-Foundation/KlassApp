{{-- SPDX-License-Identifier: MIT --}}
@php
    $attendance = $bundle['attendance'] ?? [];
    $label = (string) ($attendance['term_label'] ?? '');
    $window = str_contains(mb_strtolower($label), 'term') ? $label : ($label !== '' ? $label : 'This year');
@endphp
<div class="family-page-head">
    <div>
        <h1>Attendance</h1>
        <p data-testid="attendance-days-present">{{ $attendance['days_present_label'] ?? \App\Support\AttendanceDays::phrase((float) ($attendance['days_present'] ?? 0)) }} · {{ $window }}</p>
    </div>
</div>
@include('portal.partials.attendance-chart', ['attendance' => $attendance])
<section class="family-table-card">
    <h2>Days missed</h2>
    @forelse($attendance['missed_days'] ?? [] as $day)
        <p class="family-missed"><strong>{{ $day['date'] }}</strong> · {{ $day['comment'] }}</p>
    @empty
        <p class="family-chart-empty">No days missed in this period.</p>
    @endforelse
</section>
