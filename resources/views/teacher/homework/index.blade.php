{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Homework',
        'subtitle' => 'Create and manage homework assignments for your classes.',
    ])

    @include('partials.message')

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <input type="checkbox" id="showPast" class="rounded border-gray-300" onchange="togglePastHomework(this)">
            <label for="showPast" class="text-sm" style="color: var(--d-text-secondary);">Show Past Homework</label>
        </div>
        <a href="{{ url('/teacher/homework/add') }}" class="ds-btn ds-btn-primary" data-testid="add-homework-btn">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Add Homework
        </a>
    </div>

    <div class="ds-card ds-card-padding-none mt-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm" data-testid="homework-table">
                <thead>
                    <tr style="background: var(--d-surface);">
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--d-text);">Class</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--d-text);">Subject</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--d-text);">Description</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--d-text);">Date</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--d-text);">Pending</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--d-text);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($homeworks as $homework)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3" style="color: var(--d-text);">
                                {{ $homework->standardLink->StandardSection ?? '—' }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--d-text);">
                                {{ $homework->subject->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--d-text);">
                                {{ \Illuminate\Support\Str::limit(strip_tags($homework->description), 60) }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--d-muted);">
                                {{ $homework->date ? date('d M Y', strtotime($homework->date)) : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="ds-badge ds-badge-sm ds-badge-warning">{{ $homework->pending_count ?? 0 }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ url('/teacher/homework/edit/'.$homework->id) }}" class="ds-btn ds-btn-sm ds-btn-outline" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    <a href="{{ url('/teacher/homework/show/'.$homework->id) }}" class="ds-btn ds-btn-sm ds-btn-outline" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.574 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                <p class="text-sm" style="color: var(--d-muted);">No homework assigned yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function togglePastHomework(checkbox) {
    if (checkbox.checked) {
        window.location.href = '{{ url("/teacher/homeworks") }}?showPast=true';
    } else {
        window.location.href = '{{ url("/teacher/homeworks") }}';
    }
}
</script>
@endsection
