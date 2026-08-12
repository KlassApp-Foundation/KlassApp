<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Student Report Card</title>
    <style>
        @page { margin: 2mm 5mm; }
        *, html{
            widows: 100%;
            background-color: #f9fbfc;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #2d3748;
            position: relative;
            padding: 8px 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 0;
        }
        .table{
            margin: 10px 0 0;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 10px;
        }
        th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .school-name {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase
        }
        .section-header {
            background-color: #e2e8f0;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        .eot-header {
            background-color: #c7d2fe;
            font-weight: bold;
        }
        .photo {
            width: 65px;
            height: 65px;
            border: 2px solid #555;
            background-color: #5a5a5c;
            color: white;
            font-size: larger;
            font-weight: 700;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .logo-circle {
            width: 45px;
            height: 45px;
            background: #ccc;
            border-radius: 50%;
            text-align: center;
            line-height: 45px; 
            font-weight: bold;
            font-size: 18px;
            display: inline-block;
        }
        .watermark {
    position: absolute;
    top: 40%;
    left: 10%;
    width: 80%;
    text-align: center;
    opacity: 0.05;
    font-size: 60px;
    color: #000;
    font-weight: 900;
}
    </style>
</head>
<body>

@if (!is_null($learner))
    
    <body>
    <div class="watermark">
        {{ $learner->school->name }}
    </div>
    <!-- Header -->
<div class="table">
    <table style="width:100%; margin-bottom:20px;">
    <tr style='border: 0'>
        <td style="width:15%; text-align:center; border: 0;">
            <div class="logo-circle">
                {{ strtoupper(str($learner->school->name)->limit(2, "")) }}
            </div>
        </td>
        <td style="width:70%; text-align:center; border: 0;">
            <h2 class="school-name">{{ $learner->school->name }}</h2>
            <p>{{$school->address ?? "School Address"}}</p>
            <p>{{$school->phone ?? "School Phone"}}</p>
        </td>
        <td class="photo" style="width:15%; text-align:center; border: 0;">
            {{ $learner->userprofile->firstname }}
            <!-- Empty for student photo -->
        </td>
    </tr>
</table>

</div>
    <!-- Student Info -->
    <div class="table">
        <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Class</th>
                {{-- <th>Stream</th> --}}
                <th>Term</th>
                <th>Agg</th>
                <th>Position</th>
                <th>Out Of</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $learner->userprofile->firstname }} {{ $learner->userprofile->lastname }}</strong>
                </td>
                <td>{{ $class_name }}</td>
                {{-- <td>{{ $learner->stream ?? "A" }}</td> --}}
                <td>{{ $learner->marks->first()->exam->academicTerm->name ?? "-" }}</td>
                <th>@if(!empty($isNursery)) — @else {{$grade["agg"] ?? "-" }} @endif</th>
                <td>@if(!empty($isNursery)) — @else {{ $myPos ?? "-" }} @endif</td>
                <td>@if(!empty($isNursery)) — @else {{ $totalLearners ?? "-" }} @endif</td>
            </tr>
        </tbody>
    </table>
    </div>

    <!-- ================= Main Marks Table ===================== -->
    @if(!empty($isNursery))
        {{-- Nursery: descriptive domain assessment table --}}
        <table>
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Rating</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $domains = ['Literacy', 'Numeracy', 'Motor Skills', 'Social/Emotional'];
                @endphp
                @foreach ($domains as $domain)
                    @php
                        $assessment = $nurseryAssessments->get($domain);
                        $rating = $assessment?->rating ?? '—';
                        $remarks = $assessment?->remarks ?? '—';
                    @endphp
                    <tr>
                        <td>{{ $domain }}</td>
                        <td><strong>{{ $rating }}</strong></td>
                        <td>{{ $remarks }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        {{-- Standard marks table for Primary, O-Level, A-Level --}}
        <table>
            <thead>
                <tr>
                    <th>SUBJECT</th>
                    <th>OUT OF</th>
                    @if ($midCount > 0)
                        <th class="section-header" colspan="{{ $midCount }}">MID TERM</th>
                    @endif
                    @if ($eotCount > 0)
                        <th class="section-header eot-header" colspan="{{ $eotCount }}">END OF TERM</th>
                    @endif
                    <th>GRADE</th>
                    <th>TEACHER</th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    @foreach ($allExamColumns as $colExam)
                        @php
                            $label = $colExam->examType->code === 'MID'
                                ? strtoupper($colExam->scheduled_at->format('M')) . ' MID'
                                : 'EOT';
                        @endphp
                        <th class="{{ $colExam->examType->code !== 'MID' ? 'eot-header' : '' }}">{{ $label }}</th>
                    @endforeach
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subjects as $subject)
                    @php
                     $subjectMarks = $learner->marks->where('subject_id', $subject->id);
                     $firstMark = $subjectMarks->first();
                    @endphp
                    <tr>
                        <td>{{ $subject->name }}</td>
                        <td>100</td>
                        @foreach ($allExamColumns as $colExam)
                            @php
                                $markForExam = $subjectMarks->firstWhere('exam_id', $colExam->id);
                            @endphp
                            <td>
                                {{ $markForExam ? floor($markForExam->marks) : "-" }}
                            </td>
                        @endforeach
                        @php
                            $subjectGrade = '-';
                            if ($firstMark && $firstMark->marks !== null) {
                                $gradeRecord = $grading_system->first(function ($gs) use ($firstMark) {
                                    return $gs->min_score <= $firstMark->marks && $gs->max_score >= $firstMark->marks;
                                });
                                $subjectGrade = $gradeRecord ? $gradeRecord->grade : '-';
                            }
                        @endphp
                        <td>{{ $subjectGrade }}</td>
                        <td>{{ $learner->marks->where("subject_id", $subject->id)->first()?->teacher->name ?? "N/A" }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $examCols = $midCount + $eotCount;
                @endphp
                <tr class="total-row">
                    <th>TOTAL</th>
                    <th>{{ count($subjects) * 100 }}</th>
                    <th colspan="{{ $examCols }}">{{ $total }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    @endif
   </div>

    <!-- Remarks -->
    <div class="table">
        <table>
        <thead>
            <tr>
                <th>Class teacher's comment</th>
                <th>Sign</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $teacherComment ?? '—' }}</td>
                <td>___________________________</td>
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

    </div>
    <!-- Next Term -->
    <div class="table">
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
                <td>{{$nextTerm->starts_on?->format("d M, Y")}}</td>
                <td>{{$nextTerm->ends_on?->format("d M, Y")}}</td>
                <td>UGX {{$fees}}</td>
            </tr>
        </tbody>
    </table>
    </div>

    <!-- Grading System -->
   <div class="table">
     <h3 style="margin: 12px 0 4px 0; font-size: 12px;">School Grading System</h3>
    <table>
        <thead>
            <tr>
                <th>Grade</th>
                @foreach ($grading_system as $grade)
                    <th>{{ $grade->grade }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Range</strong></td>
                @foreach ($grading_system as $grade)
                    <td>{{ $grade->min_score . "-" . $grade->max_score }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
   </div>
@else
    <h3 style="text-align:center;">No records found</h3>
@endif
</body>
</html>