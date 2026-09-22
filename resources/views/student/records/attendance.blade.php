{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
<div class="dashboard-shell px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'My attendance',
        'subtitle' => 'Your own attendance record for the last month, since '.$since.'.',
    ])

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([['Present', $present], ['Absent', $absent], ['Late', $late], ['Records', $total]] as $card)
            <div class="ds-card ds-card-padding-default">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $card[0] }}</p>
                <p class="mt-2 text-3xl font-bold" style="color: var(--d-text);">{{ $card[1] }}</p>
            </div>
        @endforeach
    </div>

    <div class="ds-card ds-card-padding-default mt-6 overflow-hidden">
        @if(empty($recent))
            <div class="py-14 text-center">
                <p class="text-lg font-semibold" style="color: var(--d-text);">No attendance recorded</p>
                <p class="mt-2 text-sm" style="color: var(--d-muted);">Nothing has been recorded for you in the last month.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[480px] text-left">
                    <thead class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <tr><th class="px-3 py-3">Date</th><th class="px-3 py-3">Session</th><th class="px-3 py-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recent as $row)
                            <tr class="hover:bg-emerald-50/40">
                                <td class="px-3 py-4 text-sm font-semibold text-slate-900">{{ $row['date'] }}</td>
                                <td class="px-3 py-4 text-sm text-slate-600">{{ $row['session'] }}</td>
                                <td class="px-3 py-4">
                                    <span class="ds-badge ds-badge-sm {{ $row['status'] === 'Present' ? 'ds-badge-success' : 'ds-badge-danger' }}">{{ $row['status'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
