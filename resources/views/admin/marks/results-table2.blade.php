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
                <th class="ka-th ka-num" scope="col">Agg</th>
                <th class="ka-th" scope="col">Division</th>
                <th class="ka-th" scope="col">Report</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
                @php
                    $incompleteNames = ($sheet ?? null) instanceof \App\Services\Results\ResultSheet
                        ? app(\App\Services\StudentReportCardService::class)->incompleteSetNames($sheet, (int) $student->studentId)
                        : [];
                @endphp
                <tr data-testid="marks-sheet-row">
                    <td class="gm-pos" data-testid="marks-position">{{ $student->placeLabel($streamFiltered) }}</td>
                    <th scope="row" class="ka-learner-name">
                        @if($showReportLink && $exam && $class)
                            <a class="ka-learner-name" href="{{ route('admin.marks.student.class', ['user' => $student->studentId, 'class' => $class->id, 'exam' => $exam->id]) }}">{{ $student->name }}</a>
                        @else
                            {{ $student->name }}
                        @endif
                        @if ($incompleteNames !== [])
                            <div data-kind="incomplete">Incomplete: {{ implode(', ', $incompleteNames) }}.</div>
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
                            @include('admin.marks.sheet-cell', ['cell' => $cell])
                        </td>
                    @endforeach
                    <td class="gm-total ka-num" data-testid="marks-total">{{ $student->displayTotal ?? '—' }}</td>
                    <td class="ka-num" data-testid="marks-average">{{ $student->displayAverage ?? '—' }}</td>
                    <td class="gm-agg">{{ $student->aggregate ?? '—' }}</td>
                    <td data-testid="marks-division">{{ $student->division ?? '—' }}</td>
                    <td>
                        @if($showReportLink && $exam && $class)
                            <a href="{{ route('admin.marks.student.class', ['user' => $student->studentId, 'class' => $class->id, 'exam' => $exam->id]) }}" class="dt-name-link text-xs" target="_blank" rel="noopener">Report</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="ka-phone-cards" data-testid="marks-phone-cards">
    @foreach ($students as $student)
        @php
            $incompleteNames = ($sheet ?? null) instanceof \App\Services\Results\ResultSheet
                ? app(\App\Services\StudentReportCardService::class)->incompleteSetNames($sheet, (int) $student->studentId)
                : [];
        @endphp
        <article class="ka-learner-card" data-testid="marks-phone-card">
            <div class="ka-learner-head">
                <span class="ka-learner-pos">{{ ($streamFiltered ? $student->streamPosition : $student->classPosition) ?? '—' }}</span>
                <span class="ka-learner-meta" data-testid="marks-phone-position">{{ $student->placeLabel($streamFiltered) }}</span>
                <strong>
                    @if($showReportLink && $exam && $class)
                        <a class="ka-learner-name" href="{{ route('admin.marks.student.class', ['user' => $student->studentId, 'class' => $class->id, 'exam' => $exam->id]) }}">{{ $student->name }}</a>
                    @else
                        {{ $student->name }}
                    @endif
                </strong>
            </div>
            @if ($incompleteNames !== [])
                <span data-kind="incomplete">Incomplete: {{ implode(', ', $incompleteNames) }}.</span>
            @endif
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
                            @include('admin.marks.sheet-cell', ['cell' => $cell])
                        </dd>
                    </div>
                @endforeach
                <div class="ka-learner-total">
                    <dt>Total</dt>
                    <dd data-testid="marks-phone-total">Total {{ $student->displayTotal ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Aggregate</dt>
                    <dd>{{ $student->aggregate ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Division</dt>
                    <dd data-testid="marks-phone-division">{{ $student->division ?? '—' }}</dd>
                </div>
            </dl>
        </article>
    @endforeach
    @if($students->hasMorePages())
        <a href="{{ $students->nextPageUrl() }}" class="ds-btn ds-btn-outline ka-sheet-more" data-testid="marks-show-more">Show 20 more</a>
    @endif
</div>
