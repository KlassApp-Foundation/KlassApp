@extends('layouts.admin.layout')
@section('content')

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">

    @include('layouts.partials.page-header', [
        'title' => 'Marks Submissions',
        'subtitle' => 'Overview of all exam marks submissions, lock status, and approval status.',
    ])

    <p class="mb-4 text-sm text-gray-700" data-testid="marks-submissions-count">{{ $pendingCount }} awaiting review</p>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="ds-table w-full">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Class</th>
                    <th>Teacher</th>
                    <th>Term</th>
                    <th>Type</th>
                    <th>Submitted</th>
                    <th>Approval</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                    <tr data-testid="marks-submission-row">
                        <td class="font-medium">{{ $submission->subject->name ?? '-' }}</td>
                        <td>{{ $submission->class->name ?? '-' }}</td>
                        <td>{{ $submission->teacher->name ?? '-' }}</td>
                        <td>{{ $submission->exam->academicTerm->name ?? '-' }}</td>
                        <td>{{ $submission->exam->examType->name ?? '-' }}</td>
                        <td>{{ $submission->submitted_at?->format('j M Y H:i') ?? '-' }}</td>
                        <td>{{ $submission->approval_status ?? 'pending' }}</td>
                        <td>
                            <a href="{{ route('admin.marks.submissions.show', $submission->exam_id) }}"
                               class="ds-btn ds-btn-sm ds-btn-primary">
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-gray-500 py-8">
                            No submitted marks found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $submissions->links() }}
    </div>
</div>

@endsection
