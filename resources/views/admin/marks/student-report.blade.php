<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Student Report Card</title>
    <style>
        @page { margin: 2mm 7mm; } /* top-bottom 10mm, sides 15mm */
        *, html{
            widows: 100%;
            background-color: #f9fbfc;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #2d3748;
            position: relative;
            padding: 15px 18px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 0;
        }
        .table{
            margin: 24px 0 0;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 10px 10px;
            vertical-align: middle;
            font-size: 12px;
        }
        th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
        }
        .school-name {
            font-size: 25px;
            font-weight: bold;
            text-transform: uppercase
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0 15px;
            text-transform: uppercase;
        }
        .photo {
            width: 90px;
            height: 90px;
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
            width: 60px;
            height: 60px;
            background: #ccc;
            border-radius: 50%;
            text-align: center;
            line-height: 60px; 
            font-weight: bold;
            font-size: 22px;
            display: inline-block;
        }
        .note{
            font-weight: 700;
            font-size: 16px;
            text-align: center;
            margin-top: 24px;
        }
        .note-txt{
            text-decoration: underline;
            color: red;
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
    transform: rotate(-60deg);
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
            <p>P.O.Box 58, Kabale, Uganda</p>
            <p>+256781490899</p>
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
                <th>{{$grade["agg"] ?? "-" }}</th>
                <td>{{ $myPos ?? "-" }}</td>
                <td>{{ $totalLearners ?? "-" }}</td>
            </tr>
        </tbody>
    </table>
    </div>

    <!-- =========== Performance Summary =============== -->
   <div class="table">
     <div>
        @php
            $termMap = ["First Term" => 1, "Second Term" => 2, "Third Term" => 3];
            $termName = $learner->marks->first()->exam->academicTerm->name;
        @endphp
        <h3 class="title">
            {{ $learner->marks->first()->exam->exam_type }}
            {{ $termMap[$termName] ?? "-" }}
            {{ $termName->academicYear }}
            <span>STUDENT REPORT CARD</span>
        </h3>
    </div>

    <!-- ================= Main Marks Table ===================== -->
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
                 $score = $subject->mark->first()->marks;
                 $average = $subject->mark->average("marks");
                @endphp
                <tr>
                    <td>{{ $subject->name }}</td>
                    <td>100</td>
                    @foreach ($marksFromSubject as $subje)
                      <td>
                          {{$score ? floor($score) : "-"}}
                      </td>                 
                    @endforeach
                        @if ($uniqueExamTypes > 1)
                             <td>
                            {{ $score ? floor($score) : "-" }}
                        </td>
                        @endif
                    {{-- ============ grade ========= --}}
                    <td>{{ $subject->mark->first()->grade }}</td>
                    {{-- ============ Remark ========= --}}
                    @foreach($grade['remark'] as $comment)
                     @if ($comment->grade === $subject->mark->first()->grade)
                          <td>{{$comment->remark}}</td>
                     @endif
                    @endforeach
                    <td>{{ $learner->marks->where("subject_id", $subject->id)->first()?->teacher->name ?? "N/A" }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @php
            $span = count($controls) - 3
        @endphp
            <tr class="total-row">
                <th>TOTAL</th>
                <th>400</th>
                <th>{{ $total }}</th>
                @if ($uniqueExamTypes > 1)
                    <td>
                    {{floor($average) * $examsDone ?? "-" }}
                </td>     
                @endif     
                <th colspan="{{$span}}"></th>
            </tr>
        </tfoot>
    </table>
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
                <td>___________________________</td>
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

   </div>
@else
    <h3 style="text-align:center;">No records found</h3>
@endif
    <h3 class="note">
        <strong>NOTE:</strong>
        <span class='note-txt'>This report card is invalid without the official school stamp</span>
</h3>
</body>
</html>