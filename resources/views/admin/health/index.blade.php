{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Health overview',
        'subtitle' => 'School-level summary of the per-student health records. Open a student to see or edit their full record.',
    ])

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="ds-card ds-card-padding-default">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Health profiles</p>
            <p class="mt-2 text-3xl font-bold" style="color: var(--d-text);">{{ $profiles }}</p>
            <p class="mt-1 text-xs" style="color: var(--d-muted);">students with a record on file</p>
        </div>
        <div class="ds-card ds-card-padding-default">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Immunisations</p>
            <p class="mt-2 text-3xl font-bold" style="color: var(--d-text);">{{ $immunizations }}</p>
            <p class="mt-1 text-xs {{ $overdue > 0 ? 'font-semibold' : '' }}" style="color: {{ $overdue > 0 ? 'var(--d-red)' : 'var(--d-muted)' }};">
                {{ $overdue }} overdue
            </p>
        </div>
        <div class="ds-card ds-card-padding-default">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Incidents (30 days)</p>
            <p class="mt-2 text-3xl font-bold" style="color: var(--d-text);">{{ $incidents30 }}</p>
            <p class="mt-1 text-xs" style="color: var(--d-muted);">recorded in the last month</p>
        </div>
        <div class="ds-card ds-card-padding-default">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Medical flags</p>
            <p class="mt-2 text-3xl font-bold" style="color: var(--d-text);">{{ $allergies + $chronic }}</p>
            <p class="mt-1 text-xs" style="color: var(--d-muted);">{{ $allergies }} allergies, {{ $chronic }} chronic</p>
        </div>
    </div>

    @if($bySeverity->isNotEmpty())
        <div class="ds-card ds-card-padding-default mt-6">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Incidents by severity (30 days)</h2>
            <div class="mt-3 flex flex-wrap gap-3">
                @foreach($bySeverity as $severity => $total)
                    @php $tone = $severity === 'serious' ? 'ds-badge-danger' : ($severity === 'moderate' ? 'ds-badge-warning' : 'ds-badge-success'); @endphp
                    <span class="ds-badge {{ $tone }}">{{ ucfirst($severity ?: 'minor') }}: {{ $total }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="ds-card ds-card-padding-default mt-6 overflow-hidden">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Recent incidents</h2>
        @if(empty($recentRows))
            <div class="py-14 text-center">
                <p class="text-lg font-semibold" style="color: var(--d-text);">No incidents recorded</p>
                <p class="mt-2 text-sm" style="color: var(--d-muted);">Nothing has been logged for this school yet. Health records are added from a student's page.</p>
            </div>
        @else
            <div class="overflow-x-auto mt-3">
                <table class="w-full min-w-[720px] text-left">
                    <thead class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-3 py-3">Date</th>
                            <th class="px-3 py-3">Student</th>
                            <th class="px-3 py-3">Severity</th>
                            <th class="px-3 py-3">Description</th>
                            <th class="px-3 py-3">Action taken</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentRows as $row)
                            <tr class="hover:bg-emerald-50/40">
                                <td class="px-3 py-4 text-sm font-semibold text-slate-900">{{ $row['date'] }}</td>
                                <td class="px-3 py-4 text-sm text-slate-700">{{ $row['student'] }}</td>
                                <td class="px-3 py-4">
                                    @php $tone = $row['severity'] === 'serious' ? 'ds-badge-danger' : ($row['severity'] === 'moderate' ? 'ds-badge-warning' : 'ds-badge-success'); @endphp
                                    <span class="ds-badge ds-badge-sm {{ $tone }}">{{ ucfirst($row['severity']) }}</span>
                                </td>
                                <td class="px-3 py-4 text-sm text-slate-600">{{ $row['description'] ?: '-' }}</td>
                                <td class="px-3 py-4 text-sm text-slate-600">{{ $row['action'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
