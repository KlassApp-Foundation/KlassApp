@extends('layouts.admin.layout')
@section('content')

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

    @include('layouts.partials.page-header', [
        'title' => 'Submissions: ' . $exam->name,
        'subtitle' => 'Class: ' . ($exam->section->name ?? '-') . ' | Subject: ' . ($exam->subject->name ?? '-'),
    ])

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <a href="{{ route('admin.marks.submissions') }}"
               class="ds-btn ds-btn-secondary ds-btn-sm">
                &larr; Back to overview
            </a>
        </div>

        @php
            $canApproveAll = $submissions->contains(fn ($s) => $s->isApprovable())
                && $submissions->every(fn ($s) => $s->isApprovable() || $s->isApproved());
        @endphp
        @if($canApproveAll)
            <form action="{{ route('admin.marks.submissions.approve', $exam->id) }}"
                  method="POST"
                  data-testid="marks-approve-all-form">
                @csrf
                <button type="submit"
                        class="ds-btn ds-btn-sm ds-btn-success"
                        onclick="return confirm('Approve every subject that is waiting for review?')">
                    Approve All
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="ds-table w-full">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Status</th>
                    <th>Approval</th>
                    <th>Deadline</th>
                    <th>Submitted</th>
                    <th>Locked</th>
                    <th class="max-w-xs">Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @php $currentSubjectId = null; @endphp
                @forelse($submissions as $submission)
                    @php
                        $isPlaceholder = $submission->status === 'locked' && is_null($submission->submitted_at);
                        $subjectId = $submission->subject_id;
                    @endphp

                    @if($subjectId !== $currentSubjectId)
                        @php
                            $currentSubjectId = $subjectId;
                            $subjectRows = $submissions->where('subject_id', $subjectId);
                            $canApproveSubject = $subjectRows->contains(fn ($s) => $s->isApprovable())
                                && $subjectRows->every(fn ($s) => $s->isApprovable() || $s->isApproved());
                        @endphp
                        @if(!$loop->first)
                            </tbody>
                        @endif
                        <thead>
                            <tr class="bg-gray-100">
                                <th colspan="10" class="text-left text-sm font-semibold text-gray-700 px-4 py-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <span>{{ $submission->subject->name ?? 'Subject #' . $subjectId }}</span>
                                        @if($canApproveSubject)
                                            <form action="{{ route('admin.marks.submissions.approve', $exam->id) }}"
                                                  method="POST"
                                                  data-testid="marks-approve-subject-form">
                                                @csrf
                                                <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                                                <button type="submit" class="ds-btn ds-btn-sm ds-btn-success">
                                                    Approve Subject
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                    @endif

                    <tr class="{{ $isPlaceholder ? 'bg-red-50' : ($submission->isReturned() ? 'bg-orange-50' : '') }}">
                        <td>{{ $submission->class->name ?? '-' }}</td>
                        <td>{{ $submission->subject->name ?? $exam->subject->name ?? '-' }}</td>
                        <td>{{ $submission->teacher->name ?? '-' }}</td>
                        <td>
                            @php
                                if ($isPlaceholder) {
                                    $statusLabel = 'Locked (no submission)';
                                    $colorClass = 'bg-red-100 text-red-800 ring-1 ring-red-300';
                                } else {
                                    $statusLabel = $submission->reviewLabel();
                                    $colorClass = match ($statusLabel) {
                                        'Approved' => 'bg-green-100 text-green-800',
                                        'Returned' => 'bg-orange-100 text-orange-800',
                                        'Reopened' => 'bg-yellow-100 text-yellow-800',
                                        'Locked' => 'bg-red-100 text-red-800',
                                        'Submitted' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                }
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}" data-testid="marks-row-state">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td>
                            @php
                                if ($submission->isReturned()) {
                                    $appLabel = 'Returned';
                                    $appColor = 'bg-orange-100 text-orange-800';
                                } elseif ($submission->isApproved()) {
                                    $appLabel = 'Approved';
                                    $appColor = 'bg-green-100 text-green-800';
                                } else {
                                    $appLabel = 'Pending';
                                    $appColor = 'bg-yellow-100 text-yellow-800';
                                }
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appColor }}">
                                {{ $appLabel }}
                            </span>
                            @if($submission->isApproved() && $submission->approvedBy)
                                <span class="text-xs text-gray-400 block">
                                    by {{ $submission->approvedBy->name }}
                                </span>
                            @endif
                            @if($submission->isReturned() && $submission->rejectedBy)
                                <span class="text-xs text-gray-400 block">
                                    by {{ $submission->rejectedBy->name }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $submission->deadline ? $submission->deadline->format('d M Y H:i') : ($exam->default_deadline ? $exam->default_deadline->format('d M Y H:i') : '-') }}</td>
                        <td>{{ $submission->submitted_at ? $submission->submitted_at->format('d M Y H:i') : '-' }}</td>
                        <td>{{ $submission->locked_at ? $submission->locked_at->format('d M Y H:i') : '-' }}</td>
                        <td class="max-w-xs">
                            @if($submission->rejection_reason || $submission->reopen_reason)
                                <span class="text-sm text-gray-600" title="{{ e($submission->rejection_reason ?: $submission->reopen_reason) }}">
                                    {{ \Illuminate\Support\Str::limit($submission->rejection_reason ?: $submission->reopen_reason, 40) }}
                                </span>
                                <span class="text-xs text-gray-400 block">
                                    by {{ $submission->reopenedBy->name ?? $submission->rejectedBy->name ?? 'Unknown' }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="align-top">
                            <div class="flex flex-wrap items-start gap-1">
                                @if($submission->isApprovable())
                                    <form action="{{ route('admin.marks.submissions.approve', $exam->id) }}"
                                          method="POST"
                                          data-testid="marks-approve-form">
                                        @csrf
                                        <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                                        <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                                        <button type="submit" class="ds-btn ds-btn-sm ds-btn-success">
                                            Approve
                                        </button>
                                    </form>
                                @endif

                                @if(in_array($submission->status, ['submitted', 'locked'], true) || $submission->isApproved())
                                    <button type="button"
                                            class="ds-btn ds-btn-sm ds-btn-danger"
                                            onclick="document.getElementById('return-{{ $submission->id }}').classList.toggle('hidden')">
                                        Return
                                    </button>
                                @endif

                                @if(!$submission->isLocked() && !$submission->isApproved() && in_array($submission->status, ['draft', 'submitted', 'reopened'], true))
                                    <form action="{{ route('admin.marks.submissions.lock', $exam->id) }}"
                                          method="POST"
                                          data-testid="marks-lock-form">
                                        @csrf
                                        <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                                        <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                                        <button type="submit"
                                                class="ds-btn ds-btn-sm ds-btn-danger"
                                                onclick="return confirm('Lock this submission? Teachers will no longer be able to edit marks.')">
                                            Lock
                                        </button>
                                    </form>
                                @endif

                                @if($submission->isLocked() || $submission->isApproved())
                                    <button type="button"
                                            class="ds-btn ds-btn-sm ds-btn-warning"
                                            onclick="document.getElementById('reopen-{{ $submission->id }}').classList.toggle('hidden')">
                                        Reopen
                                    </button>
                                @endif
                            </div>

                            @if(in_array($submission->status, ['submitted', 'locked'], true) || $submission->isApproved())
                                <form id="return-{{ $submission->id }}"
                                      action="{{ route('admin.marks.submissions.reject', $exam->id) }}"
                                      method="POST"
                                      class="hidden mt-2 p-3 bg-gray-50 rounded border min-w-[250px]"
                                      data-testid="marks-return-form">
                                    @csrf
                                    <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                                    <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                                    <div class="mb-2">
                                        <label class="ds-label text-xs">Reason for return (required):</label>
                                        <textarea name="return_reason"
                                                  rows="2"
                                                  class="ds-input text-sm"
                                                  required
                                                  minlength="10"
                                                  maxlength="500"
                                                  placeholder="Tell the teacher what to change."></textarea>
                                    </div>
                                    <button type="submit" class="ds-btn ds-btn-sm ds-btn-danger">
                                        Confirm Return
                                    </button>
                                </form>
                            @endif

                            @if($submission->isLocked() || $submission->isApproved())
                                <form id="reopen-{{ $submission->id }}"
                                      action="{{ route('admin.marks.submissions.reopen', $exam->id) }}"
                                      method="POST"
                                      class="hidden mt-2 p-3 bg-gray-50 rounded border min-w-[250px]"
                                      data-testid="marks-reopen-form">
                                    @csrf
                                    <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                                    <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                                    <div class="mb-2">
                                        <label class="ds-label text-xs">Reason for reopening (required):</label>
                                        <textarea name="reopen_reason"
                                                  rows="2"
                                                  class="ds-input text-sm"
                                                  required
                                                  minlength="10"
                                                  maxlength="500"
                                                  placeholder="Who asked for this, and why."></textarea>
                                    </div>
                                    <button type="submit" class="ds-btn ds-btn-sm ds-btn-warning">
                                        Confirm Reopen
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-gray-500 py-8">
                            No submission records found for this exam.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
