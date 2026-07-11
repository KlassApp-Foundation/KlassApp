{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.alumni.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--alumni px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'My Marks History',
        'subtitle' => 'View all exam marks recorded during your time at school.',
    ])

    @if($marks->isEmpty())
        <div class="ds-card text-center py-12 mt-4">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            <p class="text-gray-400 text-sm">No exam records found for your account.</p>
            <a href="{{ url('/alumni/dashboard') }}" class="text-blue-600 text-sm mt-2 inline-block hover:underline">← Back to Dashboard</a>
        </div>
    @else
        <div class="ds-card mt-4">
            <div class="ds-table-wrap">
                <table class="ds-table ds-table-striped">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Exam</th>
                            <th>Score</th>
                            <th>Grade</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($marks as $mark)
                            <tr>
                                <td class="font-medium">{{ $mark->subject->name ?? '—' }}</td>
                                <td>{{ $mark->exam->name ?? '—' }}</td>
                                <td>
                                    <span class="ds-badge ds-badge-sm {{ ($mark->marks ?? 0) >= 50 ? 'ds-badge-active' : 'ds-badge-warning' }}">
                                        {{ $mark->marks ?? 0 }}
                                    </span>
                                </td>
                                <td>{{ $mark->grade ?? '—' }}</td>
                                <td class="text-xs" style="color: var(--d-muted);">{{ $mark->created_at ? $mark->created_at->format('d M Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">
            {{ $marks->links() }}
        </div>
    @endif
</div>
@endsection
