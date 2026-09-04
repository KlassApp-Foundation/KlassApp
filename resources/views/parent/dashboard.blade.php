{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.parent.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--parent px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Parent Portal',
        'subtitle' => $selectedChild
            ? ($selectedChild['name'].' · '.$selectedChild['class'].' · '.($selectedChild['school_name'] ?? ''))
            : 'Fees, grades, and attendance for your linked children.',
    ])

    @include('partials.message')

    @if ($linkedChildCount === 0)
        <div class="ds-empty-state mt-6" data-testid="parent-empty-children">
            <div class="ds-empty-state-icon" aria-hidden="true">👨‍👩‍👧</div>
            <p class="ds-empty-state-title">No children linked yet</p>
            <p class="ds-empty-state-desc">
                {{ $emptyMessage ?? 'Link a child via WhatsApp with your school’s KLS code, or contact the school office.' }}
            </p>
        </div>
    @else
        {{-- Child selector: grouped by school (cross-school parents) --}}
        <nav class="parent-child-selector mt-4" aria-label="Select child" data-testid="parent-child-selector">
            @foreach ($groupedBySchool as $group)
                <div class="parent-child-selector__school mb-3">
                    <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--d-muted);">
                        {{ $group['school_name'] }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($group['children'] as $child)
                            @php
                                $isActive = $selectedChild && (int) $selectedChild['student_id'] === (int) $child['student_id'];
                            @endphp
                            <a
                                href="{{ route('parent.dashboard', ['child' => $child['student_id']]) }}"
                                class="ds-btn ds-btn-sm {{ $isActive ? 'ds-btn-primary' : 'ds-btn-outline' }}"
                                @if($isActive) aria-current="page" @endif
                                data-testid="child-chip-{{ $child['student_id'] }}"
                            >
                                {{ $child['name'] }}
                                <span class="opacity-70 font-normal"> · {{ $child['class'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        @if ($selectedChild && $panel && !($panel['denied'] ?? false))
            @php
                $fees = $panel['fees'] ?? null;
                $grades = $panel['grades'] ?? null;
                $attendance = $panel['attendance'] ?? null;
                $balance = $fees['total_balance'] ?? null;
                $present = $attendance['present'] ?? 0;
                $attTotal = $attendance['total'] ?? 0;
                $attPct = $attTotal > 0 ? round(($present / $attTotal) * 100) : null;
                $gradeSubjects = collect($grades['exam_groups'] ?? [])->flatMap(fn ($g) => $g['subjects'] ?? [])->count();
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4 mt-4" data-testid="parent-kpi-row">
                <x-ds-kpi-card
                    icon="dollar"
                    value="{{ $balance !== null ? 'UGX '.number_format($balance, 0) : '—' }}"
                    label="Fee Balance"
                    color="{{ ($balance ?? 0) > 0 ? 'amber' : 'green' }}"
                />
                <x-ds-kpi-card
                    icon="check"
                    value="{{ $attPct !== null ? $attPct.'%' : '—' }}"
                    label="Attendance (30 days)"
                    color="blue"
                />
                <x-ds-kpi-card
                    icon="exam"
                    value="{{ $gradeSubjects > 0 ? $gradeSubjects : '—' }}"
                    label="Recent Marks"
                    color="green"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mt-6">
                {{-- Fees — mirrors WhatsApp fee balance --}}
                <x-card title="Fee Balance" padding="none" class="parent-panel" data-testid="parent-fees-panel">
                    @include('parent.partials.fees-panel', ['fees' => $fees])
                </x-card>

                {{-- Attendance — mirrors WhatsApp attendance --}}
                <x-card title="Attendance" padding="none" class="parent-panel" data-testid="parent-attendance-panel">
                    @include('parent.partials.attendance-panel', ['attendance' => $attendance])
                </x-card>
            </div>

            {{-- Grades — mirrors WhatsApp grades --}}
            <x-card title="Grades" padding="none" class="parent-panel mt-4 md:mt-6" data-testid="parent-grades-panel">
                @include('parent.partials.grades-panel', [
                    'grades' => $grades,
                    'emptyMessage' => $panel['grades_message'] ?? null,
                ])
            </x-card>
        @endif
    @endif
</div>
@endsection
