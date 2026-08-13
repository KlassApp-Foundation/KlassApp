<div class="ds-table-wrap">
    <table class="ds-grid-marks">
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>Subject</th>
                <th>Marks</th>
                <th>Grade</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($marks as $mark)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="font-medium">{{ $mark->student?->displayName ?? '—' }}</div>
                        <div class="gm-subject-code">{{ $mark->student?->admission_number ?? '' }}</div>
                    </td>
                    <td>{{ $mark->subject?->name ?? '—' }}</td>
                    <td class="font-medium">{{ $mark->marks ?? '—' }}</td>
                    <td>{{ $mark->grade ?? '—' }}</td>
                    <td>
                        <div class="flex items-center justify-center gap-2">
                            <a href="#" class="px-2 py-0.5 rounded text-xs font-medium text-white" style="background:#059669;">Edit</a>
                            <a href="{{ route("admin.marks.student.class", [$mark->student, $class->id]) }}" class="px-2 py-0.5 rounded text-xs font-medium text-white" style="background:#3B82F6;">View</a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>