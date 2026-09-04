{{-- SPDX-License-Identifier: MIT --}}
@props(['attendance'])

<div class="px-4 md:px-5 py-4" data-testid="parent-attendance-panel-body">
    @if ($attendance && ($attendance['total'] ?? 0) > 0)
        <div class="flex flex-wrap gap-3 mb-4 text-sm">
            <span class="ds-badge ds-badge-sm ds-badge-active">Present {{ $attendance['present'] }}</span>
            <span class="ds-badge ds-badge-sm ds-badge-unpaid">Absent {{ $attendance['absent'] }}</span>
            <span class="ds-badge ds-badge-sm ds-badge-info">Records {{ $attendance['total'] }}</span>
        </div>
        @if (!empty($attendance['recent_absences']))
            <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--d-muted);">Recent absences</p>
            <ul class="space-y-2">
                @foreach ($attendance['recent_absences'] as $row)
                    <li class="flex justify-between text-sm">
                        <span>{{ $row['date'] }}</span>
                        <span style="color: var(--d-muted);">{{ $row['reason'] }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm" style="color: var(--d-muted);">No recent absences.</p>
        @endif
    @else
        <div class="ds-empty-state py-6">
            <p class="ds-empty-state-title">No attendance yet</p>
            <p class="ds-empty-state-desc">Attendance for the last 30 days will appear here once recorded.</p>
        </div>
    @endif
</div>
