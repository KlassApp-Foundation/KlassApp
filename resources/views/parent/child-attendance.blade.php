{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.parent.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--parent px-4 md:px-6 py-4" data-testid="parent-child-attendance-page">
    @include('layouts.partials.page-header', [
        'title' => 'Attendance',
        'subtitle' => $childName.($studentId ? ' · Student #'.$studentId : ''),
    ])

    <p class="mt-2 mb-4">
        <a href="{{ route('parent.children') }}" class="ds-btn ds-btn-sm ds-btn-outline">← Children</a>
        @if ($studentId)
            <a href="{{ route('parent.dashboard', ['child' => $studentId]) }}" class="ds-btn ds-btn-sm ds-btn-outline">Dashboard</a>
        @endif
    </p>

    <x-card title="Attendance" padding="none" class="parent-panel" data-testid="parent-attendance-panel">
        @include('parent.partials.attendance-panel', ['attendance' => $attendance])
    </x-card>

    @if (!empty($attendance['weeks']))
        <x-card title="Weeks this term" padding="none" class="parent-panel mt-4" data-testid="parent-attendance-weeks">
            <div class="px-4 md:px-5 py-4">
                <p class="text-sm mb-4" style="color: var(--d-muted);">
                    {{ $attendance['term_label'] ?: 'This term' }} · days present each week, out of 5
                </p>
                <div style="display:flex;gap:8px;height:160px;align-items:flex-end;" role="img"
                     aria-label="Days present by week: {{ collect($attendance['weeks'])->map(fn ($w) => $w['label'].' '.$w['present'])->implode(', ') }}">
                    @foreach ($attendance['weeks'] as $week)
                        @php
                            $recorded = (int) ($week['recorded'] ?? 0);
                            $present = (int) ($week['present'] ?? 0);
                            $height = $recorded > 0 ? max(8, (int) round($present / 5 * 120)) : 8;
                            $colour = $recorded === 0 ? '#CBD5E1' : ($present >= 5 ? '#15803D' : '#B45309');
                        @endphp
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;justify-content:flex-end;height:100%;" data-testid="parent-attendance-week">
                            <span style="font-size:0.72rem;font-weight:700;">{{ $recorded > 0 ? $present : '—' }}</span>
                            <div style="width:100%;height:{{ $height }}px;background:{{ $colour }};border-radius:6px 6px 0 0;"></div>
                            <span style="font-size:0.72rem;color:#475569;">{{ $week['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div style="display:flex;gap:16px;font-size:0.8rem;color:#475569;margin-top:10px;flex-wrap:wrap;">
                    <span><span style="display:inline-block;width:10px;height:10px;background:#15803D;border-radius:2px;"></span> Full week</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:#B45309;border-radius:2px;"></span> Missed a day</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:#CBD5E1;border-radius:2px;"></span> No register saved</span>
                </div>
            </div>
        </x-card>

        <x-card title="Days missed" padding="none" class="parent-panel mt-4" data-testid="parent-attendance-missed">
            <div class="px-4 md:px-5 py-4">
                @forelse ($attendance['missed_days'] as $day)
                    <div style="font-size:0.88rem;padding:10px 0;border-bottom:1px solid #E2E8F0;" data-testid="parent-attendance-missed-day">
                        <strong>{{ $day['date'] }}</strong> · {{ $day['reason'] }}
                        @if (!empty($day['comment']))
                            · “{{ $day['comment'] }}”
                        @else
                            · no comment from the school
                        @endif
                    </div>
                @empty
                    <p class="text-sm" style="color: var(--d-muted);">No absences this term.</p>
                @endforelse
            </div>
        </x-card>
    @endif
</div>
@endsection
