<div class="ds-table-wrap ka-sheet-table">
    <table class="ds-grid-marks" data-testid="ds-grid-marks">
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>Stream</th>
                @foreach ($subjects as $subject)
                    <th>
                        {{ str($subject['name'])->limit(4, '') }}
                        <span class="gm-subject-code">/100</span>
                        <span class="gm-subject-code" data-testid="marks-subject-state">{{ $subject['submission'] ?? 'Not started' }}</span>
                    </th>
                @endforeach
                <th>Total</th>
                <th>Average</th>
                <th>Agg</th>
                <th>Pos</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
                <tr data-testid="marks-sheet-row">
                    <td>{{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}</td>
                    <td>
                        {{ $student->name }}
                        @if($student->registrationNumber !== '')
                            <div class="ka-learner-meta">{{ $student->registrationNumber }}</div>
                        @endif
                    </td>
                    <td data-testid="marks-stream">{{ $student->stream !== '' ? $student->stream : '—' }}</td>
                    @foreach ($subjects as $subject)
                        <td data-testid="marks-cell" data-subject="{{ $subject['id'] }}">
                            @include('admin.marks.sheet-cell', ['cell' => $student->cell((int) $subject['id'])])
                        </td>
                    @endforeach
                    <td class="gm-total" data-testid="marks-total">{{ $student->displayTotal ?? '—' }}</td>
                    <td data-testid="marks-average">{{ $student->displayAverage ?? '—' }}</td>
                    <td class="gm-agg">{{ $student->aggregate ?? '—' }}</td>
                    <td class="gm-pos" data-testid="marks-position">{{ $student->placeLabel($streamFiltered) }}</td>
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
        <article class="ka-learner-card" data-testid="marks-phone-card">
            <div class="ka-learner-head">
                <span class="ka-learner-pos">{{ ($streamFiltered ? $student->streamPosition : $student->classPosition) ?? '—' }}</span>
                <span class="ka-learner-meta" data-testid="marks-phone-position">{{ $student->placeLabel($streamFiltered) }}</span>
                <strong>{{ $student->name }}</strong>
                <span class="ka-learner-meta" data-testid="marks-kls">{{ $student->registrationNumber !== '' ? $student->registrationNumber : '—' }}</span>
                <span class="ka-learner-meta">{{ $student->stream !== '' ? $student->stream : '—' }}</span>
            </div>
            <dl class="ka-learner-subjects">
                @foreach ($subjects as $subject)
                    <div>
                        <dt>{{ $subject['name'] }}</dt>
                        <dd data-testid="marks-cell" data-subject="{{ $subject['id'] }}">
                            @include('admin.marks.sheet-cell', ['cell' => $student->cell((int) $subject['id'])])
                        </dd>
                    </div>
                @endforeach
                <div class="ka-learner-total">
                    <dt>Total</dt>
                    <dd data-testid="marks-phone-total">Total {{ $student->displayTotal ?? '—' }}</dd>
                </div>
            </dl>
        </article>
    @endforeach
    @if($students->hasMorePages())
        <a href="{{ $students->nextPageUrl() }}" class="ds-btn ds-btn-outline ka-sheet-more" data-testid="marks-show-more">Show 20 more</a>
    @endif
</div>
