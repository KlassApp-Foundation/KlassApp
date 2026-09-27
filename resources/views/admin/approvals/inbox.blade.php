{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Approvals</h1>
            <p class="dashboard-subtitle">Review and manage pending requests from staff.</p>
        </div>
    </div>

    @include('partials.message')

    {{-- Summary KPI cards --}}
    <div class="dashboard-kpi-grid mb-6" data-testid="approvals-kpi-grid">
        <x-ds-kpi-card icon="calendar" :value="(string) $pendingCount" label="Pending" color="amber" />
        <x-ds-kpi-card icon="check" :value="(string) $approvedCount" label="Approved" color="green" />
        <x-ds-kpi-card icon="bell" :value="(string) $rejectedCount" label="Rejected" color="red" />
    </div>

    {{-- Approval list --}}
    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-800">All Requests</h2>
        </div>

        @if($approvals->count() === 0)
            <div class="ds-empty-state" data-testid="approvals-empty-state">
                <p class="ds-empty-state-title">No approval requests yet</p>
                {{-- --d-text-secondary, not the component's --d-muted (#94A3B8, 2.56:1): AA on white. --}}
                <p class="ds-empty-state-desc" style="color: var(--d-text-secondary, #64748B);">Staff leave, parent-link and marks requests you need to review will appear here.</p>
            </div>
        @else
            <x-table :headers="['Type', 'Requester', 'Status', 'Comments', 'Requested', 'Actions']">
                        @foreach($approvals as $approval)
                            @php
                                $approvable = $approval->approvable;
                                $typeName = ($approvable && method_exists($approvable, 'displayType'))
                                    ? $approvable->displayType()
                                    : ($approvable ? class_basename($approvable) : 'Unknown');
                                $stateLabel = $approval->state instanceof \App\States\Approval\ApprovalState
                                    ? $approval->state->label()
                                    : 'Unknown';
                                $stateColor = $approval->state instanceof \App\States\Approval\ApprovalState
                                    ? $approval->state->color()
                                    : '#6B7280';
                                $canAct = $approval->state instanceof \App\States\Approval\Pending;
                                $isParentLink = $approvable instanceof \App\Models\ParentLinkRequest;
                                $candidateStudents = collect();
                                if ($isParentLink && ! empty($approvable->candidate_student_ids)) {
                                    $candidateStudents = \App\Models\User::query()
                                        ->whereIn('id', $approvable->candidate_student_ids)
                                        ->with(['studentAcademicLatest.standardLink.section'])
                                        ->get();
                                }
                            @endphp
                            <tr>
                                <td data-label="Type" class="font-medium" style="color: var(--d-text, #1E293B);">
                                    <span>
                                        {{ $typeName }}
                                        @if($isParentLink)
                                            <span class="text-xs block font-normal" style="color: var(--d-text-secondary, #64748B);">
                                                {{ $approvable->phone }} · {{ $approvable->summaryLine() }}
                                            </span>
                                        @endif
                                    </span>
                                </td>
                                <td data-label="Requester">
                                    @if($approval->requester)
                                        <span>
                                            <span style="color: var(--d-text, #1E293B);">{{ $approval->requester->name }}</span>
                                            <span class="text-xs block" style="color: var(--d-text-secondary, #64748B);">{{ $approval->requester->email }}</span>
                                        </span>
                                    @else
                                        <span style="color: var(--d-text-secondary, #64748B);">—</span>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          style="background:{{ $stateColor }}15;color:{{ $stateColor }};">
                                        {{ $stateLabel }}
                                    </span>
                                </td>
                                <td data-label="Comments" class="max-w-xs truncate" style="color: var(--d-text-secondary, #64748B);">
                                    {{ $approval->comments ?: '—' }}
                                </td>
                                <td data-label="Requested" class="text-xs whitespace-nowrap" style="color: var(--d-text-secondary, #64748B);">
                                    {{ $approval->created_at->diffForHumans() }}
                                </td>
                                <td data-label="Actions">
                                    @if($canAct)
                                        <div class="flex flex-col gap-2">
                                            <form method="POST" action="{{ route('admin.approvals.approve', $approval) }}" class="inline flex flex-wrap items-center gap-2">
                                                @csrf
                                                @if($isParentLink)
                                                    @if($candidateStudents->isNotEmpty())
                                                        <select name="matched_student_id" required
                                                                class="ds-form-select max-w-xs" style="min-height: var(--d-touch-target-min, 44px);">
                                                            @foreach($candidateStudents as $candidate)
                                                                <option value="{{ $candidate->id }}"
                                                                    @selected($candidate->id == $approvable->suggested_student_id)>
                                                                    {{ $candidate->name }}
                                                                    ({{ $candidate->studentAcademicLatest?->standardLink?->StandardSection ?? 'class n/a' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <livewire:admin.parent-link-student-picker
                                                            :school-id="$approvable->school_id"
                                                            :initial-query="$approvable->child_name"
                                                            :key="'plp-'.$approval->id"
                                                        />
                                                    @endif
                                                @endif
                                                <input type="hidden" name="comments" value="">
                                                <x-button type="submit" variant="primary" size="sm"
                                                          onclick="return confirm('Approve this request?')">
                                                    Approve
                                                </x-button>
                                            </form>
                                            <x-button variant="danger" size="sm"
                                                      onclick="document.getElementById('reject-form-{{ $approval->id }}').classList.toggle('hidden')">
                                                Reject
                                            </x-button>
                                            <form id="reject-form-{{ $approval->id }}"
                                                  method="POST" action="{{ route('admin.approvals.reject', $approval) }}"
                                                  class="hidden">
                                                @csrf
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <input type="text" name="comments" placeholder="Reason required..."
                                                           class="ds-form-input w-40" style="min-height: var(--d-touch-target-min, 44px);" required>
                                                    <x-button type="submit" variant="danger" size="sm">
                                                        Confirm
                                                    </x-button>
                                                </div>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs" style="color: var(--d-text-secondary, #64748B);">
                                            {{ $approval->resolved_at ? $approval->resolved_at->diffForHumans() : '' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
            </x-table>

            @if($approvals->hasPages())
                <div class="px-5 py-3 border-t border-gray-100">
                    {{ $approvals->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
