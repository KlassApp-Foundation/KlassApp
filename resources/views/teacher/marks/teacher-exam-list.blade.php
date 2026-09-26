@extends('layouts.teacher.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'My Exams',
        'subtitle' => 'Track exams assigned to you, open marks entry, and review class results.',
    ])

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        @include('partials.message')
    </div>

    <div class="ds-card ds-card-padding-default mt-6">
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 pb-4 mb-5">
            <div>
                <h2 class="text-lg font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">Exams to Mark</h2>
            </div>
        </div>

        @if($exams->isEmpty())
            <div class="text-center py-12">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M7 3h7l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/></svg>
                <p class="text-sm" style="color: var(--d-muted);">No exams assigned to you for marking yet.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($examsByClass as $sectionId => $classExams)
                    @php $stdLink = $assignedStdLinks->get($sectionId); $className = $classExams->first()->section->name ?? ('Class #' . $sectionId); @endphp
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-base font-semibold" style="color: var(--d-text);">{{ $className }}</h3>
                            @if ($stdLink)
                                <a href="{{ route('teacher.exam.combinedMarksheet', $stdLink) }}" class="ds-btn ds-btn-warning ds-btn-sm inline-flex items-center gap-2">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Combined Marksheet
                                </a>
                            @endif
                        </div>

                        <div class="space-y-4">
                            @foreach($classExams as $exam)
                                @php $termPosition = $exam->academicTerm?->positionLabel() ?? '-'; @endphp
                                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                        <div class="min-w-0">
                                            <div class="mb-2 flex items-center gap-2 flex-wrap">
                                                <span class="ds-badge ds-badge-sm ds-badge-info">{{ $exam->subject->name ?? 'Exam' }}</span>
                                                <span class="ds-badge ds-badge-sm ds-badge-warning">{{ $exam->status }}</span>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-3 text-xs" style="color: var(--d-muted);">
                                                <span>{{ $exam->section->name ?? '-' }}</span>
                                                <span>•</span>
                                                <span>{{ $exam->examType->name ?? '-' }}</span>
                                                <span>•</span>
                                                <span>{{ $termPosition }}</span>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('teacher.exams.edit', $exam) }}" class="ds-btn ds-btn-ghost ds-btn-sm">
                                                Edit Exam
                                            </a>
                                            <a href="{{ route('teacher.exam.marks.enter', $exam) }}" class="ds-btn ds-btn-primary ds-btn-sm">
                                                Enter / Edit Marks
                                            </a>
                                            <a href="{{ route('teacher.exam.marks.view', $exam) }}" class="ds-btn ds-btn-outline ds-btn-sm">
                                                View Marks
                                            </a>
                                            <a href="{{ route('teacher.exam.marksheet', $exam) }}" class="ds-btn ds-btn-secondary ds-btn-sm">
                                                Marksheet
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection