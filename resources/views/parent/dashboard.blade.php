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
                    <div class="px-4 md:px-5 py-4">
                        @if ($fees && !empty($fees['categories']))
                            <ul class="space-y-3">
                                @foreach ($fees['categories'] as $category)
                                    <li class="flex items-start justify-between gap-3 text-sm">
                                        <span style="color: var(--d-text);">{{ $category['name'] }}</span>
                                        <span class="font-semibold whitespace-nowrap" style="color: var(--d-text);">
                                            UGX {{ number_format($category['amount'], 0) }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between text-sm font-semibold">
                                <span>Total outstanding</span>
                                <span>UGX {{ number_format($fees['total_balance'] ?? 0, 0) }}</span>
                            </div>
                            <p class="text-xs mt-3" style="color: var(--d-muted);">
                                Paid to date: UGX {{ number_format($fees['total_paid'] ?? 0, 0) }}. Contact the school office for payment status.
                            </p>
                        @else
                            <div class="ds-empty-state py-6">
                                <p class="ds-empty-state-title">No fee structure found</p>
                                <p class="ds-empty-state-desc">Contact the school office for details.</p>
                            </div>
                        @endif
                    </div>
                </x-card>

                {{-- Attendance — mirrors WhatsApp attendance --}}
                <x-card title="Attendance" padding="none" class="parent-panel" data-testid="parent-attendance-panel">
                    <div class="px-4 md:px-5 py-4">
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
                </x-card>
            </div>

            {{-- Grades — mirrors WhatsApp grades --}}
            <x-card title="Grades" padding="none" class="parent-panel mt-4 md:mt-6" data-testid="parent-grades-panel">
                <div class="px-4 md:px-5 py-4">
                    @if ($grades && !empty($grades['exam_groups']))
                        @foreach ($grades['exam_groups'] as $group)
                            <div class="mb-5 last:mb-0">
                                <h4 class="text-sm font-semibold mb-2" style="font-family: Sora, sans-serif; color: var(--d-text);">
                                    {{ $group['exam_type'] }}
                                </h4>
                                <div class="ds-table-wrap overflow-x-auto">
                                    <table class="ds-table w-full text-sm">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Score</th>
                                                <th>Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($group['subjects'] as $subject)
                                                <tr>
                                                    <td>{{ $subject['name'] }}</td>
                                                    <td>{{ $subject['score'] }}/{{ $subject['total'] }}</td>
                                                    <td>{{ $subject['grade'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="ds-empty-state py-6">
                            <p class="ds-empty-state-title">No results published</p>
                            <p class="ds-empty-state-desc">
                                {{ $panel['grades_message'] ?? 'Results will appear here once the school publishes marks.' }}
                            </p>
                        </div>
                    @endif
                </div>
            </x-card>
        @endif
    @endif
</div>
@endsection
