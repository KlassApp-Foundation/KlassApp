{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.parent.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--parent px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Children',
        'subtitle' => 'Linked student accounts across your schools.',
    ])

    @if (!empty($emptyMessage) || count($children) === 0)
        <div class="ds-empty-state mt-6" data-testid="parent-children-empty">
            <div class="ds-empty-state-icon" aria-hidden="true">👨‍👩‍👧</div>
            <p class="ds-empty-state-title">No children linked yet</p>
            <p class="ds-empty-state-desc">{{ $emptyMessage ?? 'No linked children yet.' }}</p>
        </div>
    @else
        <div class="space-y-6 mt-4">
            @foreach ($groupedBySchool as $group)
                <x-card :title="$group['school_name']" padding="default">
                    <ul class="space-y-3">
                        @foreach ($group['children'] as $child)
                            <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                                <div>
                                    <p class="font-semibold" style="color: var(--d-text);">{{ $child['name'] }}</p>
                                    <p class="text-sm" style="color: var(--d-muted);">{{ $child['class'] }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('parent.dashboard', ['child' => $child['student_id']]) }}" class="ds-btn ds-btn-sm ds-btn-primary">Dashboard</a>
                                    <a href="{{ route('parent.children.fees', $child['student_id']) }}" class="ds-btn ds-btn-sm ds-btn-outline">Fees</a>
                                    <a href="{{ route('parent.children.grades', $child['student_id']) }}" class="ds-btn ds-btn-sm ds-btn-outline">Grades</a>
                                    <a href="{{ route('parent.children.attendance', $child['student_id']) }}" class="ds-btn ds-btn-sm ds-btn-outline">Attendance</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endforeach
        </div>
    @endif
</div>
@endsection
