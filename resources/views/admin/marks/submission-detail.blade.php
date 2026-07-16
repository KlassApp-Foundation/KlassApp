@extends('layouts.admin.layout')
@section('content')

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

    @include('layouts.partials.page-header', [
        'title' => 'Submissions: ' . $exam->name,
        'subtitle' => 'Class: ' . ($exam->standard->name ?? $exam->section->name ?? '-') . ' | Subject: ' . ($exam->subject->name ?? '-'),
    ])

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <a href="{{ route('admin.marks.submissions') }}"
               class="ds-btn ds-btn-secondary ds-btn-sm">
                &larr; Back to overview
            </a>
        </div>

        {{-- Approve All (all-at-once granularity) --}}
        @php
            $allLocked = $submissions->every(fn($s) => $s->isLocked());
            $hasPending = $submissions->contains(fn($s) => $s->isLocked() && $s->isApprovalPending());
        @endphp
        @if($hasPending)
            <form action="{{ route('admin.marks.submissions.approve', $exam->id) }}"
                  method="POST" class="inline-block">
                @csrf
                <button type="submit"
                        class="ds-btn ds-btn-sm {{ $allLocked ? 'ds-btn-success' : 'ds-btn-secondary' }}"
                        @if(!$allLocked) disabled @endif
                        onclick="return confirm('Approve ALL submissions for this exam?')">
                    Approve All
                </button>
            </form>
            @if(!$allLocked)
                <p class="text-xs text-red-600 mt-1">
                    Some submissions are not yet locked. All must be locked before a bulk approval.
                </p>
            @endif
        @endif
    </div>

    {{-- Approve Selected form wraps the table --}}
    <form id="bulk-approve-form"
          action="{{ route('admin.marks.submissions.approve', $exam->id) }}"
          method="POST">
        @csrf

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="ds-table w-full">
                <thead>
                    <tr>
                        <th class="w-10">
                            <input type="checkbox" id="select-all"
                                   class="rounded border-gray-300"
                                   onclick="document.querySelectorAll('.row-select').forEach(c => c.checked = this.checked)">
                        </th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th>Deadline</th>
                        <th>Submitted</th>
                        <th>Locked</th>
                        <th class="max-w-xs">Reopen Reason</th>
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

                        {{-- Subject group separator — Approve Subject (per-subject-across-classes) --}}
                        @if($subjectId !== $currentSubjectId)
                            @php $currentSubjectId = $subjectId; @endphp
                            @if(!$loop->first)
                                </tbody>
                            @endif
                            <thead>
                                <tr class="bg-gray-100">
                                    <th colspan="11" class="text-left text-sm font-semibold text-gray-700 px-4 py-2">
                                        <div class="flex items-center justify-between">
                                            <span>{{ $submission->subject->name ?? 'Subject #' . $subjectId }}</span>
                                            @if($submissions->where('subject_id', $subjectId)->contains(fn($s) => $s->isLocked() && $s->isApprovalPending()))
                                                @php
                                                    $subjAllLocked = $submissions->where('subject_id', $subjectId)->every(fn($s) => $s->isLocked());
                                                @endphp
                                                <form action="{{ route('admin.marks.submissions.approve', $exam->id) }}"
                                                      method="POST" class="inline-block">
                                                    @csrf
                                                    <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                                                    <button type="submit"
                                                            class="ds-btn ds-btn-sm {{ $subjAllLocked ? 'ds-btn-success' : 'ds-btn-secondary' }}"
                                                            @if(!$subjAllLocked) disabled @endif
                                                            onclick="return confirm('Approve all classes for {{ $submission->subject->name }}?')">
                                                        Approve Subject
                                                    </button>
                                                </form>
                                                @if(!$subjAllLocked)
                                                    <span class="text-xs text-red-600 ml-2">Some classes not locked yet</span>
                                                @endif
                                            @endif
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                        @endif

                        <tr class="{{ $isPlaceholder ? 'bg-red-50' : ($submission->isRejected() ? 'bg-orange-50' : '') }}">
                            <td>
                                @if($submission->isLocked() && $submission->isApprovalPending())
                                    <input type="checkbox" name="selected[]"
                                           value="{{ $submission->class_id }}_{{ $submission->subject_id }}"
                                           class="row-select rounded border-gray-300">
                                @endif
                            </td>
                            <td>{{ $submission->class->name ?? $exam->standard->name ?? '-' }}</td>
                            <td>{{ $submission->subject->name ?? $exam->subject->name ?? '-' }}</td>
                            <td>{{ $submission->teacher->name ?? '-' }}</td>
                            <td>
                                @php
                                    if ($isPlaceholder) {
                                        $statusLabel = 'Locked (no submission)';
                                        $colorClass = 'bg-red-100 text-red-800 ring-1 ring-red-300';
                                    } elseif ($submission->status === 'locked') {
                                        $statusLabel = 'Locked';
                                        $colorClass = 'bg-red-100 text-red-800';
                                    } else {
                                        $statusMap = [
                                            'draft' => ['Draft', 'bg-gray-100 text-gray-800'],
                                            'submitted' => ['Submitted', 'bg-blue-100 text-blue-800'],
                                            'reopened' => ['Reopened', 'bg-yellow-100 text-yellow-800'],
                                        ];
                                        $statusLabel = $statusMap[$submission->status][0] ?? ucfirst($submission->status);
                                        $colorClass = $statusMap[$submission->status][1] ?? 'bg-gray-100 text-gray-800';
                                    }
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $approvalColors = [
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'pending'  => 'bg-yellow-100 text-yellow-800',
                                    ];
                                    $appColor = $approvalColors[$submission->approval_status] ?? 'bg-yellow-100 text-yellow-800';
                                    $appLabel = $submission->approval_status ? ucfirst($submission->approval_status) : 'Pending';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appColor }}">
                                    {{ $appLabel }}
                                </span>
                                @if($submission->isApproved() && $submission->approvedBy)
                                    <span class="text-xs text-gray-400 block">
                                        by {{ $submission->approvedBy->name }}
                                    </span>
                                @endif
                                @if($submission->isRejected() && $submission->rejectedBy)
                                    <span class="text-xs text-gray-400 block" title="{{ e($submission->rejection_reason) }}">
                                        by {{ $submission->rejectedBy->name }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $submission->deadline ? $submission->deadline->format('d M Y H:i') : ($exam->default_deadline ? $exam->default_deadline->format('d M Y H:i') : '-') }}</td>
                            <td>{{ $submission->submitted_at ? $submission->submitted_at->format('d M Y H:i') : '-' }}</td>
                            <td>{{ $submission->locked_at ? $submission->locked_at->format('d M Y H:i') : '-' }}</td>
                            <td class="max-w-xs">
                                @if($submission->reopen_reason)
                                    <span class="text-sm text-gray-600" title="{{ e($submission->reopen_reason) }}">
                                        {{ \Illuminate\Support\Str::limit($submission->reopen_reason, 40) }}
                                    </span>
                                    <span class="text-xs text-gray-400 block">
                                        by {{ $submission->reopenedBy->name ?? 'Unknown' }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="space-x-1 align-top">
                                {{-- Approve (single row) — only when locked + not already approved --}}
                                @if($submission->isLocked() && $submission->isApprovalPending())
                                    <form action="{{ route('admin.marks.submissions.approve', $exam->id) }}"
                                          method="POST" class="inline-block">
                                        @csrf
                                        <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                                        <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                                        <button type="submit" class="ds-btn ds-btn-sm ds-btn-success">
                                            Approve
                                        </button>
                                    </form>
                                @endif

                                {{-- Reject — only when locked --}}
                                @if($submission->isLocked())
                                    <button type="button"
                                            class="ds-btn ds-btn-sm ds-btn-danger"
                                            onclick="document.getElementById('reject-{{ $submission->id }}').classList.toggle('hidden')">
                                        Reject
                                    </button>
                                    <form id="reject-{{ $submission->id }}"
                                          action="{{ route('admin.marks.submissions.reject', $exam->id) }}"
                                          method="POST"
                                          class="hidden mt-2 p-3 bg-gray-50 rounded border min-w-[250px]">
                                        @csrf
                                        <input type="hidden" name="class_id" value="{{ $submission->class_id }}">
                                        <input type="hidden" name="subject_id" value="{{ $submission->subject_id }}">
                                        <div class="mb-2">
                                            <label class="ds-label text-xs">Reason for rejection (required):</label>
                                            <textarea name="rejection_reason"
                                                      rows="2"
                                                      class="ds-input text-sm"
                                                      required
                                                      minlength="10"
                                                      placeholder="Why is this being rejected?"></textarea>
                                        </div>
                                        <button type="submit" class="ds-btn ds-btn-sm ds-btn-danger">
                                            Confirm Reject
                                        </button>
                                    </form>
                                @endif

                                {{-- Lock — only when NOT locked --}}
                                @if(!$submission->isLocked())
                                    <form action="{{ route('admin.marks.submissions.lock', $exam->id) }}"
                                          method="POST" class="inline-block">
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

                                {{-- Reopen — only when locked (separate from reject) --}}
                                @if($submission->isLocked())
                                    <button type="button"
                                            class="ds-btn ds-btn-sm ds-btn-warning"
                                            onclick="document.getElementById('reopen-{{ $submission->id }}').classList.toggle('hidden')">
                                        Reopen
                                    </button>
                                    <form id="reopen-{{ $submission->id }}"
                                          action="{{ route('admin.marks.submissions.reopen', $exam->id) }}"
                                          method="POST"
                                          class="hidden mt-2 p-3 bg-gray-50 rounded border min-w-[250px]">
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
                                                      placeholder="Explain why these marks need to be reopened..."></textarea>
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
                            <td colspan="11" class="text-center text-gray-500 py-8">
                                No submission records found for this exam.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Bulk approve button (per class/subject via checkboxes) --}}
        <div class="mt-4 flex items-center gap-2">
            <button type="submit"
                    class="ds-btn ds-btn-sm ds-btn-success"
                    onclick="return document.querySelectorAll('.row-select:checked').length > 0 || (alert('Select at least one submission.'), false);">
                Approve Selected
            </button>
            <span class="text-sm text-gray-500">
                (<span id="selected-count">0</span> selected)
            </span>
        </div>
    </form>

    {{-- JavaScript for select-all counter --}}
    <script>
        document.getElementById('select-all')?.addEventListener('change', function() {
            updateSelectedCount();
        });
        document.querySelectorAll('.row-select').forEach(cb => {
            cb.addEventListener('change', updateSelectedCount);
        });
        function updateSelectedCount() {
            const count = document.querySelectorAll('.row-select:checked').length;
            const el = document.getElementById('selected-count');
            if (el) el.textContent = count;
        }
    </script>
</div>

@endsection
