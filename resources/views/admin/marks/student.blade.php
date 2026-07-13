@extends('layouts.admin.layout')
@section('content')

{{-- {{ dd($learner) }} --}}
@if (!is_null($learner))
    <div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
    {{-- ======== header ======= --}}
   <div class="flex items-center justify-between border-b border-gray-200 pb-4">
        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center font-bold text-gray-600">
            SL
        </div>
        <div>
            <h2 class="ds-section-title">{{ $learner->school->name }}</h2>
            <p class="ds-section-subtitle">{{$school->address}}</p>
            <p class="ds-section-subtitle">{{$school->phone}}</p>
        </div>

    <div class="text-right">
        <img src="{{ asset('images/el-el.png') }}" alt="STUDENT PHOTO" class="w-24 h-24 object-cover border border-gray-200 rounded">
        <p class="text-xs text-gray-500 mt-1">Term: {{$learner->marks->first()->exam->academicTerm->name }}</p>
    </div>
</div>

    {{-- ============ student's card info --}}
    <table class="ds-table w-full mt-6">
        <thead>
            <tr>
                <th>Name</th>
                <th>Class</th>
                <th>Term</th>
                <th>Agg</th>
                <th>Position</th>
                <th>Out Of</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-semibold">{{ $learner->userprofile->firstname }} {{ $learner->userprofile->lastname }}</td>
                <td>{{ $class_name }}</td>
                <td>{{ $learner->marks->first()->exam->academicTerm->name ?? "-" }}</td>
                <td>{{ $grade["agg"] ?? "-" }}</td>
                <td>{{ $myPos ?? "-" }}</td>
                <td>{{ $totalLearners ?? "-" }}</td>
            </tr>
        </tbody>
    </table>

{{-- ============== performance summary ============== --}}
<div class="flex items-center justify-center py-4">
    @php
        $termMap = ["First Term" => 1, "Second Term" => 2, "Third Term" => 3];
        $termName = $learner->marks->first()->exam->academicTerm->name;
    @endphp
    <h3 class="ds-section-title text-center">
        {{ $learner->marks->first()->exam->examType->name }}
        {{ $termMap[$termName] ?? "-" }}
        {{ $termName->academicYear }}
        <span>STUDENT REPORT CARD</span>
    </h3>
</div>

   <div class="ds-table-wrap mt-4">
    <!-- ================= Main Marks Table ===================== -->
    <table class="ds-table">
        {{-- header --}}
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
            <td class="font-medium">{{ $subject->name }}</td>
            <td>100</td>
                <td>
                    {{ $score ? floor($score) : "-" }}
                </td>

            @if ( $uniqueExamTypes > 1)
                 <td>
                {{ $average }}
            </td>
            @endif
            <td>{{ $subject->mark->first()->grade }}</td>
            <td>
                {{$learner->marks->where("subject_id", $subject->id)->first()?->teacher?->name ?? "-"}}
            </td>
            @php
                $subjectGrade = $subject->mark->first()->grade;
                $remark = collect($grade['remark'])->firstWhere('grade', $subjectGrade);
            @endphp
                 <td>{{ $remark->remark ?? '-' }}</td>
        </tr>
        @endforeach 
    </tbody>
    {{-- ========= calculated ========== --}}
    
    <tfoot>
        @php
            $span = max(count($controls) - 3, 0)
        @endphp
        <tr class="bg-gray-50 font-semibold">
             <td>TOTAL</td>
             <td>400</td>
             <td>{{ $total }}</td>
                @if ($uniqueExamTypes > 1)
                    <td>
                    {{ floor($average* $examsDone) ?? "N/A" }}
                </td>     
                @endif            
             <td colspan="{{$span}}"></td>
        </tr>
    </tfoot>    
</table>

{{-- =========== remarks =========== --}}
<table class="ds-table w-full mt-8">
    <thead>
        <tr>
            <th>Class teacher's comment</th>
            <th>Sign</th>
        </tr>
    </thead>
    <tbody>
        <tr>
        <td>{{$promotion ?? "Advised to repeat"}}</td>
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

{{-- =========== next term details =========== --}}
<table class="ds-table w-full mt-8">
    <thead>
        <tr>
            <th>Next term begins on</th>
            <th>End on</th>
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

{{-- ============= grading system =========== --}}
<div class="flex flex-col gap-2 mt-6">
    <h3 class="ds-section-title">School Grading System</h3>
    <table class="ds-table">
        <thead>
            <tr>
                <th class="text-center">Grade</th>
                @foreach ($grading_system as $grade)
                    <th class="text-center">{{ $grade->grade }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-medium text-center">Range</td>
                @foreach ($grading_system as $grade)
                    <td class="text-center">{{ $grade->min_score . "-" . $grade->max_score }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
 <h3 class="text-center text-lg font-semibold py-10 text-gray-500">
 This report card is invalid without the official school stamp
</h3>
</div>
</div>
@else
    <div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
        <h3 class="text-center text-lg font-semibold text-gray-500">No records found</h3>
    </div>
@endif

@endsection