{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.parent.layout')

@section('content')
<div class="dashboard-shell dashboard-shell--parent px-4 md:px-6 py-4">
    @include('layouts.partials.page-header', [
        'title' => 'Children',
        'subtitle' => 'Linked student accounts across your schools.',
    ])

    <div class="ds-card ds-card-padding">
        @if (!empty($emptyMessage))
            <p class="text-sm" style="color: var(--d-muted);">{{ $emptyMessage }}</p>
        @elseif (count($children) === 0)
            <p class="text-sm" style="color: var(--d-muted);">No linked children yet.</p>
        @else
            <ul class="space-y-3">
                @foreach ($children as $child)
                    <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-gray-200 pb-3 last:border-0 last:pb-0">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $child['name'] }}</p>
                            <p class="text-sm" style="color: var(--d-muted);">{{ $child['class'] }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-sm">
                            <a href="{{ route('parent.children.fees', $child['student_id']) }}" class="ds-btn ds-btn-sm">Fees</a>
                            <a href="{{ route('parent.children.grades', $child['student_id']) }}" class="ds-btn ds-btn-sm">Grades</a>
                            <a href="{{ route('parent.children.attendance', $child['student_id']) }}" class="ds-btn ds-btn-sm">Attendance</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
