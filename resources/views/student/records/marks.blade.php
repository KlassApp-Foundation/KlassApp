{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
<div class="dashboard-shell px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'My marks',
        'subtitle' => 'Results published for you. Only your own marks are shown here.',
    ])

    @if(empty($groups))
        <div class="ds-card ds-card-padding-default mt-6">
            <div class="py-14 text-center">
                <p class="text-lg font-semibold" style="color: var(--d-text);">No marks published yet</p>
                <p class="mt-2 text-sm" style="color: var(--d-muted);">Your results will appear here once your teachers publish them.</p>
            </div>
        </div>
    @else
        @foreach($groups as $examType => $subjects)
            <div class="ds-card ds-card-padding-default mt-6 overflow-hidden">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ $examType }}</h2>
                <div class="overflow-x-auto mt-3">
                    <table class="w-full min-w-[520px] text-left">
                        <thead class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr><th class="px-3 py-3">Subject</th><th class="px-3 py-3">Score</th><th class="px-3 py-3">Grade</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($subjects as $subject)
                                <tr class="hover:bg-emerald-50/40">
                                    <td class="px-3 py-4 text-sm font-semibold text-slate-900">{{ $subject['name'] }}</td>
                                    <td class="px-3 py-4 text-sm text-slate-600">{{ rtrim(rtrim(number_format($subject['score'], 1), '0'), '.') }} / 100</td>
                                    <td class="px-3 py-4"><span class="ds-badge ds-badge-sm ds-badge-success">{{ $subject['grade'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
