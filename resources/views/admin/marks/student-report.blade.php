<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Student Report Card</title>
    <style>
        @page { margin: 20mm; }
        body {
            font-family: sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th, td {
            border: 1px solid #555;
            padding: 8px 10px;
            vertical-align: middle;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 25px 0 15px;
            text-transform: uppercase;
        }
        .photo {
            width: 90px;
            height: 90px;
            border: 2px solid #555;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .logo-circle {
    width: 60px;
    height: 60px;
    background: #ccc;
    border-radius: 50%;
    text-align: center;
    line-height: 60px; /* vertical centering hack */
    font-weight: bold;
    font-size: 22px;
    display: inline-block;
}
    </style>
</head>
<body>

<table style="border: none; margin-bottom: 30px;">
    <tr>
        <td style="width: 80px; border: none; vertical-align: top;">
            <div class="logo-circle">SL</div>
        </td>
        <td style="border: none; vertical-align: top;">
            <h2 class="school-name">{{ $learner->school->name ?? 'School Name' }}</h2>
            <p>P.O.Box 58, Kabale, Uganda</p>
            <p>+256781490899</p>
        </td>
        <td style="border: none; text-align: right; vertical-align: top;">
            {{-- @php
                $photoPath = $learner->userprofile?->avatar 
                    ? public_path('uploads/user/avatar/' . $learner->userprofile->avatar)
                    : public_path('images/el-el.png');
            @endphp --}}
            {{-- <img src="{{ $photoPath }}" class="photo" alt="Student Photo"> --}}
            <p style="margin-top: 8px; font-size: 11px;">
                Term {{ $learner->marks->first()?->exam?->term ?? '' }}
            </p>
        </td>
    </tr>
</table>

<!-- Student Info -->
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Class</th>
            <th>Stream</th>
            <th>Term</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>{{ $learner->userprofile->firstname ?? '' }} {{ $learner->userprofile->lastname ?? '' }}</strong></td>
            <td>{{ $class_name ?? 'N/A' }}</td>
            <td>{{ $learner->stream ?? 'A' }}</td>
            <td>{{ $learner->marks->first()?->exam?->term ?? '' }}</td>
        </tr>
    </tbody>
</table>

<h3 class="title">
    {{ $learner->marks->first()?->exam?->examType?->name ?? '' }} 
    {{ $learner->marks->first()?->exam?->term ?? '' }} 
    {{ $learner->marks->first()?->exam?->academicYear?->name ?? '' }} 
    STUDENT REPORT CARD
</h3>

<!-- Main Marks Table -->
<table>
    <thead>
        <tr>
            @foreach ($controls as $control)
                <th>{{ $control }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($subjects as $subject)
            @php
            $allowedTypes = ["Beginning of Term", "Mid Term", "End of Term"];
                $mark = $learner->marks
                    ->where('subject_id', $subject->id)
                    ->sortBy(fn($m) => array_search($m->exam?->examType?->name ?? '', $allowedTypes));

                $marksByType = $mark->keyBy(fn($m) => $m->exam?->examType?->name ?? '');

                $bot = $marksByType["Beginning of Term"]->marks ?? null;
                $mot = $marksByType["Mid Term"]->marks ?? null;
                $eot = $marksByType["End of Term"]->marks ?? null;

                $values = collect([$bot, $mot, $eot])->filter();
                $average = $values->count() > 0 ? round($values->avg(), 1) : null;

                $subjectAverages[] = $average ?? 0;

                if ($average >= 85) { $grade = "D1"; $remark = "Well done!"; }
                elseif ($average >= 80) { $grade = "D2"; $remark = "Good work!"; }
                elseif ($average >= 75) { $grade = "C3"; $remark = "Promising Learner!"; }
                elseif ($average >= 65) { $grade = "C4"; $remark = "Fairly done!"; }
                elseif ($average >= 55) { $grade = "C5"; $remark = "Put more energy!"; }
                elseif ($average >= 50) { $grade = "C6"; $remark = "Improve!"; }
                elseif ($average >= 45) { $grade = "P7"; $remark = "Read harder!"; }
                elseif ($average >= 40) { $grade = "P8"; $remark = "Read harder!"; }
                else { $grade = "F9"; $remark = "Wake up!"; }
            @endphp

            <tr>
                <td>{{ $subject->name }}</td>
                <td>100</td>
                <td>{{ $bot ?? '-' }}</td>
                <td>{{ $mot ?? '-' }}</td>
                <td>{{ $eot ?? '-' }}</td>
                <td>{{ $average ?? '-' }}</td>
                <td>{{ $grade }}</td>
                <td>{{ $remark }}</td>
                <td>{{ $learner->marks->first()?->teacher?->name ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </tbody>

    @php
        $marksByType = $learner->marks->groupBy(fn($m) => $m->exam?->examType?->name ?? '');
        $botTotal = collect($marksByType['Beginning of Term'] ?? [])->sum('marks');
        $motTotal = collect($marksByType['Mid Term'] ?? [])->sum('marks');
        $eotTotal = collect($marksByType['End of Term'] ?? [])->sum('marks');
        $totalAverages = count($subjectAverages) ? round(array_sum($subjectAverages) / count($subjectAverages), 1) : 0;
    @endphp

    <tfoot>
        <tr class="total-row">
            <th>TOTAL</th>
            <th>400</th>
            <th>{{ $botTotal }}</th>
            <th>{{ $motTotal }}</th>
            <th>{{ $eotTotal }}</th>
            <th>{{ $totalAverages }}</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </tfoot>
</table>

<!-- Remarks -->
<table>
    <thead>
        <tr>
            <th>Class teacher's comment</th>
            <th>Sign</th>
        </tr>
    </thead>
    <tbody>
        <tr>
        <td>______________________</td>
        <td>______________________</td>
    </tr>
    </tbody>

    <thead>
        <tr>
            <th>Head teacher's comment</th>
            <th>Sign</th>
        </tr>
    </thead>
    <tbody>
        <tr>
        <td>______________________</td>
        <td>______________________</td>
    </tr>
    </tbody>
</table>


<!-- Next Term -->
<table>
    <thead>
        <tr>
            <th>Next term begins on</th>
            <th>Ends on</th>
            <th>Fees for next term</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>2026/05/12</td>
            <td>2026/08/17</td>
            <td>UGX 500,000</td>
        </tr>
    </tbody>
</table>

<!-- Grading System -->
<h3 style="margin: 25px 0 10px 0; font-size: 14px;">School Grading System</h3>
<table>
    <thead>
        <tr>
            <th>Grade</th>
            @foreach ($grading_system as $range => $grade)
                <th>{{ $grade }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Range</strong></td>
            @foreach ($grading_system as $range => $grade)
                <td>{{ $range }}</td>
            @endforeach
        </tr>
    </tbody>
</table>

</body>
</html>