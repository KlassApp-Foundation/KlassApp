<div class="ds-table-wrap">
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
                    <td>{{ $student->name }}</td>
                    <td data-testid="marks-stream">{{ $student->stream !== '' ? $student->stream : '—' }}</td>
                    @foreach ($subjects as $subject)
                        @php $cell = $student->cell((int) $subject['id']); @endphp
                        <td data-testid="marks-cell">
                            @if($cell && $cell->status === \App\Services\Results\ResultCell::MARKED && $cell->mark !== null)
                                {{ rtrim(rtrim(number_format($cell->mark, 2, '.', ''), '0'), '.') }}
                                @if($cell->grade)
                                    <span data-testid="marks-cell-grade">{{ $cell->grade }}</span>
                                @endif
                            @else
                                —
                            @endif
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
