@extends('layouts.admin.layout')
@section('content')

@php
    $termPosition = $term?->positionLabel() ?? '-';
    $sittingLabel = function ($sitting): string {
        return $sitting->periodLabel !== '' ? $sitting->periodLabel : $sitting->name;
    };
    $markText = function ($cell): string {
        if ($cell && $cell->status === \App\Services\Results\ResultCell::ABSENT) {
            return 'ABS';
        }
        if ($cell && $cell->status === 'marked' && $cell->mark !== null) {
            return (string) $cell->mark;
        }

        return '—';
    };
@endphp

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" data-testid="student-results">
    <div class="border-b border-gray-200 pb-4">
        <h1 class="ds-section-title" data-testid="student-result-name">{{ $student->name }}</h1>
        <p class="ds-section-subtitle" data-testid="student-result-class">{{ $section->name }}</p>
        <p class="ds-section-subtitle" data-testid="student-result-term">{{ $term->name }}</p>
        <p class="ds-section-subtitle">{{ $termPosition }}</p>
    </div>

    <div class="ds-table-wrap mt-4 ka-sheet-table">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    @foreach ($sittings as $sitting)
                        <th data-testid="student-sitting-{{ $sitting->periodKey !== '' ? $sitting->periodKey : $sitting->code }}">
                            {{ $sittingLabel($sitting) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($subjects as $subjectId => $subjectName)
                    <tr>
                        <td>{{ $subjectName }}</td>
                        @foreach ($sittings as $sitting)
                            <td>{{ $markText($sitting->student($student->studentId)?->cell($subjectId)) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="ka-phone-cards" data-testid="student-phone-cards">
        @foreach ($subjects as $subjectId => $subjectName)
            <section class="ka-learner-card">
                <strong>{{ $subjectName }}</strong>
                @foreach ($sittings as $sitting)
                    <div class="ka-mark-row">
                        <span>{{ $sittingLabel($sitting) }}</span>
                        <span>{{ $markText($sitting->student($student->studentId)?->cell($subjectId)) }}</span>
                    </div>
                @endforeach
            </section>
        @endforeach
        <section class="ka-learner-card">
            <div class="ka-mark-row">
                <span>Total</span>
                <span>{{ $student->displayTotal ?? '—' }}</span>
            </div>
            <div class="ka-mark-row">
                <span>Aggregate</span>
                <span>{{ $student->aggregate ?? '—' }}</span>
            </div>
            <div class="ka-mark-row">
                <span>Division</span>
                <span>{{ $student->division ?? '—' }}</span>
            </div>
            <div class="ka-mark-row">
                <span>Position</span>
                <span>{{ $student->classPosition ?? '—' }}</span>
            </div>
            <div class="ka-mark-row">
                <span>Out of</span>
                <span>{{ $student->classOutOf }}</span>
            </div>
        </section>
    </div>

    @if (! empty($withheldSentence))
        <p data-kind="withheld">{{ $withheldSentence }}</p>
    @endif
    @if (! empty($incompleteSets))
        <p data-kind="incomplete">Incomplete: {{ implode(', ', $incompleteSets) }}.</p>
    @endif
    <div class="ka-sheet-table">
    <table class="ds-table w-full mt-6">
        <thead>
            <tr>
                <th>Total</th>
                <th>Aggregate</th>
                <th>Division</th>
                <th>Position</th>
                <th>Out of</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td data-testid="student-result-total">{{ $student->displayTotal ?? '—' }}</td>
                <td data-testid="student-result-aggregate">{{ $student->aggregate ?? '—' }}</td>
                <td data-testid="student-result-division">{{ $student->division ?? '—' }}</td>
                <td data-testid="student-result-position">{{ $student->classPosition ?? '—' }}</td>
                <td>{{ $student->classOutOf }}</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

@endsection
