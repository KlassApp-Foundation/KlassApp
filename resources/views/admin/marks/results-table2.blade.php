<div class="ds-table-wrap ka-sheet-table">
    <table class="ds-grid-marks" data-testid="ds-grid-marks">
        <thead>
            <tr>
                <th class="ka-th" scope="col">Position</th>
                <th class="ka-th" scope="col">Learner</th>
                <th class="ka-th" scope="col">KLS number</th>
                <th class="ka-th" scope="col">Stream</th>
                @foreach ($subjects as $subject)
                    <th class="ka-th ka-num" scope="col">
                        {{ $subject['name'] }}
                        <span class="gm-subject-code">/100</span>
                    </th>
                @endforeach
                <th class="ka-th ka-num" scope="col">Total</th>
                <th class="ka-th ka-num" scope="col">Average</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
                <tr data-testid="marks-sheet-row">
                    <td class="gm-pos" data-testid="marks-position">{{ $student->placeLabel($streamFiltered) }}</td>
                    <th scope="row" class="ka-learner-name">
                        @if($showReportLink && $exam && $class)
                            <a class="ka-learner-name" href="{{ route('admin.marks.student.class', ['user' => $student->studentId, 'class' => $class->id, 'exam' => $exam->id]) }}">{{ $student->name }}</a>
                        @else
                            {{ $student->name }}
                        @endif
                        <span class="gm-agg ka-sr">{{ $student->aggregate ?? '' }}</span>
                    </th>
                    <td data-testid="marks-kls-col">{{ $student->registrationNumber !== '' ? $student->registrationNumber : '—' }}</td>
                    <td data-testid="marks-stream">{{ $student->stream !== '' ? $student->stream : '—' }}</td>
                    @foreach ($subjects as $subject)
                        @php
                            $cell = $student->cell((int) $subject['id']);
                            $absent = $cell && $cell->status === \App\Services\Results\ResultCell::ABSENT;
                        @endphp
                        <td class="ka-num {{ $absent ? 'ka-cell-abs' : '' }}" data-testid="marks-cell" data-subject="{{ $subject['id'] }}">
                            @include('admin.marks.sheet-cell', ['cell' => $cell, 'setReleased' => (bool) ($setReleased ?? false)])
                        </td>
                    @endforeach
                    <td class="gm-total ka-num" data-testid="marks-total">{{ $student->displayTotal ?? '—' }}</td>
                    <td class="ka-num" data-testid="marks-average">{{ $student->displayAverage ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="ka-phone-cards" data-testid="marks-phone-cards">
    @foreach ($students as $student)
        <article class="ka-learner-card" data-testid="marks-phone-card">
            <div class="ka-learner-head">
                <span class="ka-learner-pos">{{ ($streamFiltered ? $student->streamPosition : $student->classPosition) ?? '—' }}</span>
                <span class="ka-learner-meta" data-testid="marks-phone-position">{{ ($streamFiltered ? $student->streamPosition : $student->classPosition) === null ? '—' : 'of '.($streamFiltered ? $student->streamOutOf : $student->classOutOf) }}</span>
                <strong>
                    @if($showReportLink && $exam && $class)
                        <a class="ka-learner-name" href="{{ route('admin.marks.student.class', ['user' => $student->studentId, 'class' => $class->id, 'exam' => $exam->id]) }}">{{ $student->name }}</a>
                    @else
                        {{ $student->name }}
                    @endif
                </strong>
            </div>
            <div class="ka-kls-line" data-testid="marks-kls">{{ $student->registrationNumber !== '' ? $student->registrationNumber : '—' }} · {{ $student->stream !== '' ? $student->stream : '—' }}</div>
            <dl class="ka-learner-subjects">
                @foreach ($subjects as $subject)
                    @php
                        $cell = $student->cell((int) $subject['id']);
                        $absent = $cell && $cell->status === \App\Services\Results\ResultCell::ABSENT;
                    @endphp
                    <div @class(['ka-tile-abs' => $absent])>
                        <dt>{{ $subject['name'] }}</dt>
                        <dd data-testid="marks-cell" data-subject="{{ $subject['id'] }}">
                            @include('admin.marks.sheet-cell', ['cell' => $cell, 'setReleased' => (bool) ($setReleased ?? false)])
                        </dd>
                    </div>
                @endforeach
                <div class="ka-learner-total">
                    <dt>Total</dt>
                    <dd data-testid="marks-phone-total">{{ $student->displayTotal ?? '—' }}</dd>
                </div>
            </dl>
        </article>
    @endforeach
    @if($students->hasMorePages())
        <a href="{{ $students->nextPageUrl() }}" class="ds-btn ds-btn-outline ka-sheet-more" data-testid="marks-show-more">Show 20 more</a>
    @endif
</div>
