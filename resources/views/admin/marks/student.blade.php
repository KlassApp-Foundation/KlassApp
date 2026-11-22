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

    <div class="ds-table-wrap mt-4 ka-sheet-table">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    @foreach ($sittings as $sitting)
                        <th data-testid="student-sitting-{{ $sitting->periodKey !== '' ? $sitting->periodKey : $sitting->code }}">
                            {{ $sitting->name }}
                        </th>
                    @endforeach
                    <th data-testid="student-combined">Combined</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subjects as $subjectId => $subjectName)
                    <tr>
                        <td>{{ $subjectName }}</td>
                        @foreach ($sittings as $sitting)
                            @php
                                $cell = $sitting->student($student->studentId)?->cell($subjectId);
$mark = $cell ? $cell->sheetLabel() : '—';
                            @endphp
                            <td>{{ $mark }}</td>
                        @endforeach
                        @php
                            $combined = $student->cell((int) $subjectId);
                        @endphp
                        <td data-testid="student-combined-mark">{{ $combined ? \App\Services\Results\ResultCell::printedMark($combined) : '—' }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td>Total</td>
                    @foreach ($sittings as $sitting)
                        @php
                            $sittingTotal = $sitting->student($student->studentId)?->total;
                            $sittingKey = $sitting->periodKey !== '' ? $sitting->periodKey : $sitting->code;
                        @endphp
                        <td data-testid="student-sitting-total-{{ $sittingKey }}">{{ $sittingTotal === null ? '—' : $sittingTotal }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    <div class="ka-phone-cards" data-testid="student-phone-cards">
        @foreach ($subjects as $subjectId => $subjectName)
            <section class="ka-learner-card">
                <strong>{{ $subjectName }}</strong>
                @foreach ($sittings as $sitting)
                    <div class="ka-mark-row">
                        <span>{{ $sitting->name }}</span>
                        @php
                            $cell = $sitting->student($student->studentId)?->cell($subjectId);
                            $mark = $cell ? $cell->sheetLabel() : '—';
                        @endphp
                        <span>{{ $mark }}</span>
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
                <td data-testid="student-result-total">{{ $student->total === null ? '—' : $student->total }}</td>
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
