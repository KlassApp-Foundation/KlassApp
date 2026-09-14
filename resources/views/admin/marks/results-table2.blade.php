<div class="ds-table-wrap">
    <table class="ds-grid-marks" data-testid="ds-grid-marks">
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                @foreach ($subjects as $subject)
                    <th>
                        {{ str($subject->name)->limit(4, '') }}
                        <span class="gm-subject-code">/100</span>
                    </th>
                @endforeach
                <th>Total</th>
                <th>Agg</th>
                <th>Pos</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
                <tr>
                    <td>{{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}</td>
                    <td>
                        @php
                            $display = trim(($student->userprofile?->firstname ?? '').' '.($student->userprofile?->lastname ?? ''));
                            if ($display === '') {
                                $display = $student->displayName ?: ($student->name ?: 'Student '.$student->id);
                            }
                        @endphp
                        {{ ucwords(strtolower($display)) }}
                    </td>
                    @foreach ($subjects as $subject)
                        @php
                            $markRow = $student->marks->firstWhere('subject_id', $subject->id);
                            $mark = $markRow?->marks;
                        @endphp
                        <td>{{ $mark !== null ? ceil($mark) : '—' }}</td>
                    @endforeach
                    <td class="gm-total">{{ $student->total ?? $student->marks->sum('marks') }}</td>
                    <td class="gm-agg">{{ $student->avg ?? '—' }}</td>
                    <td class="gm-pos">{{ $student->position ?? '—' }}</td>
                    <td>
                        @if($exam && $class)
                            <a href="{{ route('admin.report.student.class', [$student, $class->id, $exam->id]) }}" class="dt-name-link text-xs" target="_blank" rel="noopener">Report</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
