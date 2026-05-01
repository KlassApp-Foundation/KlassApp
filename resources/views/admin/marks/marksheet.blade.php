<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Marks Sheet</title>
<style>
    @page { margin: 10mm 10mm; }
    body {
        font-family: sans-serif;
        font-size: 12px;
        line-height: 1.4;
        color: #333;
        margin: 0;
        padding: 10px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    th, td {
        border: 1px solid #555;
        padding: 6px 8px;
        text-align: center;
        vertical-align: middle;
        font-size: 11px;
    }
    th {
        background-color: #f0f0f0;
        font-weight: bold;
        text-transform: uppercase;
    }
    tr:nth-child(even) td {
        background-color: #f9f9f9;
    }
    .name{
         min-width: 220px;
    }
    .header-table td {
        border: 0;
        text-align: left;
        padding: 2px 5px;
    }
    .student-name {
        text-align: left;
        padding-left: 5px;
    white-space: nowrap;

    }
    .title {
        text-align: center;
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 10px;
        text-transform: uppercase;
    }
    .note {
        font-weight: bold;
        text-align: center;
        margin-top: 20px;
        font-size: 13px;
    }
</style>
</head>
<body>

<h2 class="title"> 
    <span>{{ $subjects->first()->section->name }}</span>
     <span>Marks Sheet For</span>
     <span>{{ $term }}</span>
     <span>{{ $academic_year->name }}</span>
    </h2>

<!-- Marks Table -->
<table>
    <thead>
        <tr>
            <th>No</th>
            <th class='name'>Name</th>
            @foreach ($subjects as $subject)
                <th>{{ $subject->name }}</th>
            @endforeach
            @foreach ($headers as $header)
                <th>{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($students as $student)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="student-name">{{ ucwords(strtolower($student->userprofile?->firstname . " " . $student->userprofile?->lastname ?? "Student".$student->id)) }}</td>

                @foreach ($subjects as $subject)
                    @php
                        $mark = $student->marks->firstWhere('subject_id', $subject->id);
                    @endphp
                    <td>{{ $mark && $mark->marks > 0 ? ceil($mark->marks) : '-' }}</td>
                @endforeach

                <td>{{ $student->total ?? '-' }}</td>
                <td>{{ $student->average ?? '-' }}</td>
                <td>{{ $student->marks->first()?->grade ?? '-' }}</td>
                <td>{{ $student->position ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>