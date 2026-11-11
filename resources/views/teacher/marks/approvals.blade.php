{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4" data-testid="teacher-approvals">
    @include('layouts.partials.page-header', [
        'title' => 'Marks to review',
        'subtitle' => 'Marks submitted for the classes you head. Approve them, or send them back with a reason.',
    ])

    @include('partials.message')

    <p class="text-sm text-gray-700 mb-4" data-testid="teacher-approvals-count">{{ $pendingCount }} awaiting your review</p>

    @if($submissions->isEmpty())
        <div class="ds-card ds-card-padding-default" data-testid="teacher-approvals-empty">
            <p class="font-semibold text-slate-900">Nothing to review</p>
            <p class="text-sm text-slate-600 mt-1">When a teacher submits marks for your class, they appear here.</p>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach($submissions as $submission)
                <div class="ds-card ds-card-padding-default" data-testid="teacher-approval-row-{{ $submission->id }}">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $submission->subject?->name ?? 'Subject' }}</p>
                            <p class="text-sm text-slate-600">
                                {{ $submission->class?->name ?? 'Class' }}
                                @if($submission->exam?->section?->name && $submission->exam?->section?->name !== $submission->class?->name)
                                    · {{ $submission->exam->section->name }}
                                @endif
                                · {{ $submission->teacher?->name ?? 'Teacher' }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $submission->exam?->examType?->name ?? 'Exam' }}
                                · {{ $submission->exam?->academicTerm?->name ?? 'Term' }}
                                · submitted {{ optional($submission->submitted_at)->format('j M Y, H:i') ?? '—' }}
                            </p>
                        </div>
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-700">{{ $submission->status }}</span>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <form method="POST" action="{{ route('teacher.marks.approvals.approve', $submission->exam_id) }}">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                            <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                            <button type="submit" class="ds-btn ds-btn-primary w-full" style="min-height:44px" data-testid="approve-{{ $submission->id }}">
                                Approve
                            </button>
                        </form>

                        <form method="POST" action="{{ route('teacher.marks.approvals.reject', $submission->exam_id) }}" class="flex flex-col gap-2">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                            <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                            <label class="text-sm font-semibold text-slate-700" for="return-reason-{{ $submission->id }}">Reason to send back</label>
                            <textarea id="return-reason-{{ $submission->id }}" name="return_reason" rows="2" required minlength="10"
                                      class="ds-form-input w-full" data-testid="return-reason-{{ $submission->id }}"></textarea>
                            <button type="submit" class="ds-btn ds-btn-outline w-full" style="min-height:44px" data-testid="return-{{ $submission->id }}">
                                Send back
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $submissions->links() }}</div>
    @endif
</div>
@endsection
