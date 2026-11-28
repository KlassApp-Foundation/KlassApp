@extends('layouts.admin.layout')
@section('content')

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

@include('layouts.partials.page-header', [
    'title' => 'Exams',
    'subtitle' => 'Schedule and manage all school examinations.',
    'actions' => '<a href="' . route('admin.exams.create') . '" class="ds-btn ds-btn-primary ds-btn-sm"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Add Exam</a>'
])

<div class="relative mt-4"></div>

@include('partials.message')

<x-table :headers="$headers">
    @forelse ($exams as $exam)
    <tr>
        <td data-label="No">{{ ($exams->currentPage() - 1) * $exams->perPage() + $loop->iteration }}</td>
        <td data-label="Exam">
            <span class="font-medium">{{ $exam->subjects_list->isNotEmpty() ? $exam->subjects_list->implode(', ') : '—' }}</span>
            <span class="block text-xs text-gray-500 mt-0.5">{{ $exam->examType->name }} &middot; {{ $exam->academicTerm->name }}</span>
        </td>
        <td data-label="Class">{{ $exam->section->name ?? '—' }}</td>
        <td data-label="Status">
            <span class="dt-badge {{ ($exam->submission_label ?? 'Not started') === 'Not started' ? 'dt-badge-pending' : 'dt-badge-active' }}" data-testid="exam-submission-state">
                {{ $exam->submission_label ?? 'Not started' }}
            </span>
        </td>
        <td data-label="Teacher">{{ $exam->teachers_list->isNotEmpty() ? $exam->teachers_list->implode(', ') : '—' }}</td>
        <td data-label="Actions">
            <details class="exam-action-menu">
                <summary class="ds-btn ds-btn-outline ds-btn-sm" aria-label="Actions for this exam">
                    Actions
                    <svg class="w-3 h-3 ml-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                </summary>
                <div class="exam-action-panel">
                    <a href="{{ route('admin.exams.marksheet', $exam) }}" class="exam-action-item">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download marksheet
                    </a>
                    <a href="{{ route('admin.exams.marks.import.page', $exam) }}" class="exam-action-item" data-testid="marks-import-button">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Import from spreadsheet
                    </a>
                    <a href="{{ route('admin.exams.edit', $exam) }}" class="exam-action-item">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit exam
                    </a>
                    <form action="{{ route('admin.exams.archive', $exam->id) }}" method="POST" class="inline m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="exam-action-item exam-action-item--danger w-full text-left" onclick="return confirm('Archive this exam?')">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Archive
                        </button>
                    </form>
                </div>
            </details>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="6" class="text-center py-12 text-gray-500">No exams found.</td>
    </tr>
    @endforelse
</x-table>
@if($exams->hasPages())
    <div class="mt-4">{{ $exams->links() }}</div>
@endif

</div>

@push('styles')
<style>
/* ── Exam action dropdown (pure HTML <details>/<summary>) ── */
.exam-action-menu {
    position: relative;
    display: inline-block;
}
.exam-action-menu[open] > summary {
    background: var(--ds-color-primary, #15803D);
    color: #fff;
    border-color: var(--ds-color-primary, #15803D);
}
.exam-action-panel {
    position: absolute;
    right: 0;
    top: calc(100% + 4px);
    z-index: 50;
    min-width: 200px;
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
    padding: 4px 0;
}
.exam-action-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    font-size: .8125rem;
    color: #374151;
    text-decoration: none;
    white-space: nowrap;
    background: none;
    border: none;
    cursor: pointer;
    min-height: 44px;
}
.exam-action-item:hover { background: #F8FAFC; color: #111827; }
.exam-action-item--danger { color: #DC2626; }
.exam-action-item--danger:hover { background: #FEF2F2; color: #991B1B; }
/* Close dropdown when clicking elsewhere */
.exam-action-menu:not([open]) .exam-action-panel { display: none; }
</style>
@endpush

@endsection
