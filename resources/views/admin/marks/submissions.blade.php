@extends('layouts.admin.layout')
@section('content')

@include('partials.ka-screen')
<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
<main class="ka-main">
    <h1 class="ka-h1">Marks</h1>
    <p class="ka-sub">Check what teachers submit, then release it to families.</p>
    @include('partials.ka-marks-nav', ['marksNav' => 'approvals'])
    @include('partials.ka-lifecycle', ['current' => 'submitted'])

    <p class="mb-4 text-sm text-gray-700" data-testid="marks-submissions-count">{{ $pendingCount }} awaiting review</p>

    <form method="POST" action="{{ route('admin.marks.submissions.approve-selected') }}" data-testid="approve-selected-form">
        @csrf
        <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
            <button type="submit" class="ds-btn ds-btn-primary" style="min-height:44px" data-testid="approve-selected">Approve <span data-approve-n>0</span></button>
        </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="ds-table w-full">
            <thead>
                <tr>
                    <th></th>
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
                        <td><input type="checkbox" name="submission_ids[]" value="{{ $submission->id }}" aria-label="Select {{ $submission->subject->name ?? 'subject' }}" data-approve-box style="width:20px;height:20px"></td>
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
                        <td colspan="9">
                            <div class="ka-empty" data-testid="approvals-empty">
                                <h2 class="ka-h2">Nothing waiting for approval</h2>
                                <p class="ka-sub">No submitted marks found. When teachers submit, their subjects appear here.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </form>
    <script>
    document.querySelectorAll('[data-approve-box]').forEach(function (box) {
        box.addEventListener('change', function () {
            var n = document.querySelectorAll('[data-approve-box]:checked').length;
            var label = document.querySelector('[data-approve-n]');
            if (label) label.textContent = String(n);
        });
    });
    </script>

    <div class="mt-4">
        {{ $submissions->links() }}
    </div>
</main>
</div>

@endsection
