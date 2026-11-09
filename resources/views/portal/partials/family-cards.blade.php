{{-- SPDX-License-Identifier: MIT --}}
@php
    $headline = $bundle['headline'] ?? [];
    $attendance = $bundle['attendance'] ?? [];
    $fees = $bundle['fees'] ?? [];
    $average = $headline['average'] ?? null;
    $delta = $headline['delta'] ?? null;
    $presentLabel = $attendance['days_present_label'] ?? \App\Support\AttendanceDays::phrase((float) ($attendance['days_present'] ?? 0));
    $percent = $attendance['days_percent'] ?? null;
    $weekLabel = $attendance['this_week_label'] ?? null;
    $weekTotal = (int) ($attendance['this_week_total'] ?? 0);
    $window = str_contains(mb_strtolower((string) ($attendance['term_label'] ?? '')), 'term') ? 'this term' : 'this year';
    $balance = (float) ($fees['total_balance'] ?? 0);
    $paid = (float) ($fees['total_paid'] ?? 0);
    $due = (float) ($fees['total_fees'] ?? 0);
    $progress = $due > 0 ? (int) min(100, round(($paid / $due) * 100)) : 0;
    $marksNote = ! empty($headline['has_unreleased']);
@endphp
<div class="family-cards" data-testid="parent-kpi-row">
    <a class="family-card" href="{{ $links['marks'] }}" data-testid="family-marks-card">
        <span class="family-card__head">
            <span class="family-card__icon family-card__icon--marks" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </span>
            <span class="family-card__title">Marks</span>
            <span class="family-card__chevron" aria-hidden="true">›</span>
        </span>
        <span class="family-card__value">{{ $average === null ? '—' : $average }}</span>
        <span class="family-card__meta">
            @if($average === null)
                No released results yet
            @else
                average · {{ $headline['label'] }}
                @if($delta !== null && $delta > 0)
                    · up {{ (int) $delta }} since {{ $headline['since'] }}
                @elseif($delta !== null && $delta < 0)
                    · down {{ (int) abs($delta) }} since {{ $headline['since'] }}
                @endif
            @endif
        </span>
        @if($marksNote)
            <span class="family-card__note">More results come here once the school releases them.</span>
        @endif
    </a>

    <a class="family-card" href="{{ $links['attendance'] }}" data-testid="family-attendance-card">
        <span class="family-card__head">
            <span class="family-card__icon family-card__icon--attendance" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            </span>
            <span class="family-card__title">Attendance</span>
            <span class="family-card__chevron" aria-hidden="true">›</span>
        </span>
        <span class="family-card__value">{{ $percent === null ? '—' : ((int) $percent).'%' }}</span>
        <span class="family-card__meta" data-testid="family-days-present">
            {{ $presentLabel }} {{ $window }}
            @if($weekTotal > 0 && $weekLabel)
                · this week {{ $weekLabel }}
            @endif
        </span>
    </a>

    <a class="family-card" href="{{ $links['fees'] }}" data-testid="family-fees-card">
        <span class="family-card__head">
            <span class="family-card__icon family-card__icon--fees" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </span>
            <span class="family-card__title">Fees</span>
            <span class="family-card__chevron" aria-hidden="true">›</span>
        </span>
        <span class="family-card__value">UGX {{ number_format($balance, 0) }}</span>
        <span class="family-card__meta">still owed · UGX {{ number_format($paid, 0) }} paid of UGX {{ number_format($due, 0) }}</span>
        <span class="family-progress" aria-hidden="true"><span style="width: {{ $progress }}%;"></span></span>
    </a>
</div>
