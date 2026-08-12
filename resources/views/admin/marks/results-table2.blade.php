<div class="ds-table-wrap">
    <table class="ds-grid-marks">
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                @foreach ($subjects as $subject)
                    <th>
                        {{ str($subject->name)->limit(4, "") }}
                        <span class="gm-subject-code">{{ str($subject->name)->limit(10, "") }}</span>
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
                    <td>{{ ucwords(strtolower($student->userprofile?->firstname . " ".$student->userprofile?->lastname ?? "Student". $student->id)) }}</td>
                    @foreach ($subjects as $subject)
                        @php $mark = $student?->marks->where("subject_id", $subject->id)->first()->marks; @endphp
                        <td>{{ $mark ? ceil($mark) : '—' }}</td>
                    @endforeach
                    <td class="gm-total">{{ $student->marks->sum('marks') }}</td>
                    <td class="gm-agg">{{ $student->marks->sum('marks') ? "—" : "—" }}</td>
                    <td class="gm-pos">—</td>
                    <td>
                        <a href="{{ route('admin.report.student.class', [$student, $class->id, $exam->id]) }}" class="text-blue-600 hover:text-blue-800 text-xs" target="_blank">Report</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
