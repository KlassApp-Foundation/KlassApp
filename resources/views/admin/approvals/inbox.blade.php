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
            <div class="p-8 text-center text-gray-400 text-sm">
                No approval requests yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-5 py-3 font-semibold">Type</th>
                            <th class="px-5 py-3 font-semibold">Requester</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Comments</th>
                            <th class="px-5 py-3 font-semibold">Requested</th>
                            <th class="px-5 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
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
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 text-gray-700 font-medium">
                                    {{ $typeName }}
                                    @if($isParentLink)
                                        <span class="text-gray-400 text-xs block font-normal">
                                            {{ $approvable->phone }} · {{ $approvable->summaryLine() }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if($approval->requester)
                                        <span class="text-gray-700">{{ $approval->requester->name }}</span>
                                        <span class="text-gray-400 text-xs block">{{ $approval->requester->email }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          style="background:{{ $stateColor }}15;color:{{ $stateColor }};">
                                        {{ $stateLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-500 max-w-xs truncate">
                                    {{ $approval->comments ?: '—' }}
                                </td>
                                <td class="px-5 py-4 text-gray-400 text-xs whitespace-nowrap">
                                    {{ $approval->created_at->diffForHumans() }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($canAct)
                                        <div class="flex flex-col gap-2">
                                            <form method="POST" action="{{ route('admin.approvals.approve', $approval) }}" class="inline flex flex-wrap items-center gap-2">
                                                @csrf
                                                @if($isParentLink)
                                                    @if($candidateStudents->isNotEmpty())
                                                        <select name="matched_student_id" required
                                                                class="text-xs border rounded px-2 py-1 max-w-xs">
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
                                                <button type="submit"
                                                         class="px-3 py-1 text-xs font-medium rounded text-white border-0 bg-green-600 hover:bg-green-500"
                                                         onclick="return confirm('Approve this request?')">
                                                    Approve
                                                </button>
                                            </form>
                                            <button type="button"
                                                     class="px-3 py-1 text-xs font-medium rounded text-white border-0 bg-red-500 hover:bg-red-400"
                                                     onclick="document.getElementById('reject-form-{{ $approval->id }}').classList.toggle('hidden')">
                                                Reject
                                            </button>
                                            <form id="reject-form-{{ $approval->id }}"
                                                  method="POST" action="{{ route('admin.approvals.reject', $approval) }}"
                                                  class="hidden inline">
                                                @csrf
                                                <input type="text" name="comments" placeholder="Reason required..."
                                                       class="text-xs border rounded px-2 py-1 w-32" required>
                                                <button type="submit"
                                                         class="px-2 py-1 text-xs font-medium rounded text-white border-0 bg-red-500 hover:bg-red-400">
                                                    Confirm
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">
                                            {{ $approval->resolved_at ? $approval->resolved_at->diffForHumans() : '' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($approvals->hasPages())
                <div class="px-5 py-3 border-t border-gray-100">
                    {{ $approvals->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
