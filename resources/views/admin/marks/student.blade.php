@extends('layouts.admin.layout')
@section('content')

@php
    $termPosition = $term?->positionLabel() ?? '-';
@endphp

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" data-testid="student-results">
    <div class="border-b border-gray-200 pb-4">
        <h1 class="ds-section-title" data-testid="student-result-name">{{ $student->name }}</h1>
        <p class="ds-section-subtitle" data-testid="student-result-class">{{ $section->name }}</p>
        <p class="ds-section-subtitle" data-testid="student-result-term">{{ $term->name }}</p>
        <p class="ds-section-subtitle">{{ $termPosition }}</p>
    </div>

    <div class="ds-table-wrap mt-4">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    @foreach ($sittings as $sitting)
                        <th data-testid="student-sitting-{{ $sitting->periodKey !== '' ? $sitting->periodKey : $sitting->code }}">
                            {{ $sitting->periodLabel !== '' ? $sitting->periodLabel : $sitting->name }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($subjects as $subjectId => $subjectName)
                    <tr>
                        <td>{{ $subjectName }}</td>
                        @foreach ($sittings as $sitting)
                            @php
                                $cell = $sitting->student($student->studentId)?->cell($subjectId);
                                $mark = ($cell && $cell->status === 'marked' && $cell->mark !== null) ? $cell->mark : null;
                            @endphp
                            <td>{{ $mark === null ? '—' : $mark }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <table class="ds-table w-full mt-6">
        <thead>
            <tr>
                <th>Total</th>
                <th>Aggregate</th>
                <th>Position</th>
                <th>Out of</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td data-testid="student-result-total">{{ $student->displayTotal ?? '—' }}</td>
                <td data-testid="student-result-aggregate">{{ $student->aggregate ?? '—' }}</td>
                <td data-testid="student-result-position">{{ $student->classPosition ?? '—' }}</td>
                <td>{{ $student->classOutOf }}</td>
            </tr>
        </tbody>
    </table>
</div>

@endsection
