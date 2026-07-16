@extends('layouts.admin.layout')
@section('content')

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

    @include('layouts.partials.page-header', [
        'title' => 'Marks Submissions',
        'subtitle' => 'Overview of all exam marks submissions, lock status, and approval status.',
    ])

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="ds-table w-full">
            <thead>
                <tr>
                    <th>Exam</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Term</th>
                    <th>Type</th>
                    <th>Submissions</th>
                    <th>Locked</th>
                    <th>Approved</th>
                    <th>Pending</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exams as $exam)
                    <tr>
                        <td class="font-medium">{{ $exam->name }}</td>
                        <td>{{ $exam->standard->name ?? $exam->section->name ?? '-' }}</td>
                        <td>{{ $exam->subject->name ?? '-' }}</td>
                        <td>{{ $exam->academicTerm->name ?? '-' }}</td>
                        <td>{{ $exam->examType->name ?? '-' }}</td>
                        <td class="text-center">{{ $stats[$exam->id]['total'] ?? 0 }}</td>
                        <td class="text-center">
                            @if(($stats[$exam->id]['locked'] ?? 0) > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    {{ $stats[$exam->id]['locked'] }}
                                </span>
                            @else
                                0
                            @endif
                        </td>
                        <td class="text-center">
                            @if(($stats[$exam->id]['approved'] ?? 0) > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ $stats[$exam->id]['approved'] }}
                                </span>
                            @else
                                0
                            @endif
                        </td>
                        <td class="text-center">
                            @if(($stats[$exam->id]['pending'] ?? 0) > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ $stats[$exam->id]['pending'] }}
                                </span>
                            @else
                                0
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.marks.submissions.show', $exam->id) }}"
                               class="ds-btn ds-btn-sm ds-btn-primary">
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-gray-500 py-8">
                            No submitted exams found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $exams->links() }}
    </div>
</div>

@endsection
