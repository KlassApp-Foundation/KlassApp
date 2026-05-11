@extends('layouts.admin.layout')
@section('content')

{{-- {{ dd($learner) }} --}}
@if (!is_null($learner))
    <div class="container-fluid w-full lg:mx-2 py-4 px-6">
      {{-- @php
          $allowedTypes = ["Beginning of Term", "Mid Term", "End of Term"];
          $subjectAverages = [];
                    $filteredMarks = $learner->marks->filter(
              fn($m) => in_array($m->exam?->exam_type, $allowedTypes)
          );
       @endphp --}}
    {{-- ======== header ======= --}}
   <div class="flex items-center justify-between border-b border-gray-400 pb-4">
        <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center font-bold">
            SL
        </div>
        <div>
            <h2 class="font-bold text-2xl text-gray-700 uppercase">{{ $learner->school->name }}</h2>
            <p class="text-sm text-gray-500">P.O.Box 58, Kabale, Uganda</p>
            <p class="text-sm text-gray-500">+256781490899</p>
        </div>

    <div class="text-right">
        <img src="{{ asset('images/el-el.png') }}" alt="STUDENT PHOTO" class="w-24 h-24 object-cover border border-gray-400">
        <p class="text-sm text-gray-500">Term: {{$learner->marks->first()->exam->academicTerm->name }}</p>
    </div>
</div>

    {{-- ============ student's card info --}}
    <table class="w-full mt-6 border border-gray-400 rounded-xl overflow-hidden">
        <thead class="bg-gray-200 text-sm uppercase text-gray-700">
            <tr class="p-3 text-left border border-gray-500">
                <th class="p-3 border border-gray-500">Name</th>
                <th class="p-3 border border-gray-500">Class</th>
                {{-- <th class="p-3 border border-gray-500">Stream</th> --}}
                <th class="p-3 border border-gray-500">Term</th>
                <th class="p-3 border border-gray-500">Agg</th>
                <th class="p-3 border border-gray-500">Position</th>
                <th class="p-3 border border-gray-500">Out Of</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="p-3 border border-gray-500">
                    <span class="font-semibold">{{ $learner->userprofile->firstname }}</span>
                    <span class="font-semibold">{{ $learner->userprofile->lastname }}</span>
                </td>
                <td class="p-3 border border-gray-500"> {{ $class_name }}</td>
                {{-- <td class="p-3 border border-gray-500"> {{ $learner->stream ?? "A" }}</td> --}}
                <td class="p-3 border border-gray-500">
                    {{ $learner->marks->first()->exam->academicTerm->name ?? "-" }}
                </td>
                
                <td class="p-3 border border-gray-500"> {{ $grade["agg"] ?? "-" }}</td>
                 <td class="p-3 border border-gray-500"> {{ $myPos ?? "-" }}</td>
                <td class="p-3 border border-gray-500"> {{ $totalLearners ?? "-" }}</td>
            </tr>
        </tbody>

    </table>

{{-- ============== performance summary ============== --}}
<div class="flex items-center justify-center py-4 ">
    @php
        $termMap = ["First Term" => 1, "Second Term" => 2, "Third Term" => 3];
        $termName = $learner->marks->first()->exam->academicTerm->name;
    @endphp
    <h3 class="font-bold text-xl text-gray-700 uppercase">
        {{ $learner->marks->first()->exam->examType->name }}
        {{ $termMap[$termName] ?? "-" }}
        {{ $termName->academicYear }}
        <span>STUDENT REPORT CARD</span>
    </h3>
</div>

   <div class="">
    <!-- ================= Main Marks Table ===================== -->
    <table class="w-full border border-gray-400 rounded-xl overflow-hidden">
        {{-- header --}}
    <thead class="bg-gray-100 text-sm uppercase text-gray-600">
        <tr>
           
            @foreach ($controls as $control)
                <th class="p-3 border border-gray-500">{{ $control }}</th>
            @endforeach
        </tr>
    </thead>

    <tbody class="text-sm">
        {{-- {{dd($subjects);}} --}}
        @foreach ($subjects as $subject)
          @php
          $score = $subject->mark->first()->marks;
          $average = $subject->mark->average("marks");
        //   dd($score);
         @endphp

        <tr class="border-t hover:bg-gray-50">
            <td class="p-3 border border-gray-500">{{ $subject->name }}</td>
            <td class="p-3 border border-gray-500">100</td>
            {{-- {{ dd($marksFromSubject) }} --}}
                <td class="p-3 border border-gray-500">
                    {{-- {{dd($subject->)}} --}}
                    {{ $score ? floor($score) : "-" }}
                </td>                 
           

            {{-- @foreach ($studentTotals as $studentTotal) --}}
            @if ( $uniqueExamTypes > 1)
                 <td class="p-3 border border-gray-500">
                {{ $average }}
            </td>
            @endif
            {{-- @endforeach --}}
            <td class="p-3 border border-gray-500">{{ $subject->mark->first()->grade }}</td>
            <td class="p-3 border border-gray-500">
                {{$learner->marks->where("subject_id", $subject->id)->first()?->teacher?->name ?? "N/A"}}
            </td>
            @foreach($grade['remark'] as $comment)
            @if ($comment->grade === $subject->mark->first()->grade)
                 <td class="p-3 border border-gray-500">{{$comment->remark}}</td>
            @endif
              @endforeach
            
            
            
            {{-- {{ dd($learner) }} --}}
            
        </tr>
        @endforeach 
    </tbody>
    {{-- ========= calculated ========== --}}
    
    <thead class="bg-gray-100 text-sm uppercase text-gray-600">
        @php
            $span = count($controls) - 3
        @endphp
        <tr>
             <th class="p-3 text-left border border-gray-500">TOTAL</th>
             <th class="p-3 text-lg text-center border border-gray-500">{{400}}</th>
             <th class="p-3 text-lg text-center border border-gray-500">{{ $total }}</th>
                @if ($uniqueExamTypes > 1)
                    <td class="p-3 border border-gray-500">
                    {{floor($average* $examsDone) ?? "N/A" }}
                </td>     
                @endif            
             <th colspan="{{$span}}" class="p-3 text-center border border-gray-500"></th>
             
        </tr>
    </thead>    
</table>

{{-- =========== remarks =========== --}}
<table class="w-full mt-8 border border-gray-400 rounded-xl overflow-hidden text-sm">
    <thead class="bg-gray-200 text-sm uppercase text-gray-600">
        <tr>
            <th class="p-3 border border-gray-500 text-left">Class teacher's comment</th>
            <th class="p-3 border border-gray-500 text-left">Sign</th>
        </tr>
    </thead>
    <tbody>
        <tr>
        <td class="p-3 border border-gray-500">______________________</td>
        <td class="p-3 border border-gray-500">______________________</td>
    </tr>
    </tbody>

    <thead class="bg-gray-200 text-sm uppercase text-gray-600">
        <tr>
            <th class="p-3 border border-gray-500 text-left">Head teacher's comment</th>
            <th class="p-3 border border-gray-500 text-left">Sign</th>
        </tr>
    </thead>
    <tbody>
        <tr>
        <td class="p-3 border border-gray-500">______________________</td>
        <td class="p-3 border border-gray-500">______________________</td>
    </tr>
    </tbody>
</table>

{{-- =========== next term details =========== --}}
<table class="w-full mt-8 border border-gray-400 rounded-xl overflow-hidden text-sm">
    <thead class="bg-gray-200 text-sm uppercase text-gray-600">
        <tr>
            <th class="p-3 border border-gray-500 text-left ">Next term begins on</th>
            <th class="p-3 border border-gray-500 text-left ">End on</th>
            <th class="p-3 border border-gray-500 text-left ">Fees for next term</th>
        </tr>
    </thead>
    <tbody>
        <tr>
        <td class="p-3 border border-gray-500">{{$nextTerm->starts_on?->format("d M, Y")}}</td>
        <td class="p-3 border border-gray-500">{{$nextTerm->ends_on?->format("d M, Y")}}</td>
        <td class="p-3 border border-gray-500">UGX {{$fees}}</td>
    </tr>
    </tbody>
</table>

{{-- ============= grading system =========== --}}
<div class="flex flex-col gap-2 mt-6 text-sm">
    <h3 class="text-xl font-semibold text-gray-600">School Grading System</h3>
    <table class="w-full border border-gray-400 rounded-xl overflow-hidden">
        <thead class="bg-gray-100 uppercase text-gray-700">
            <tr>
                <th class="p-2 border border-gray-500 text-center">Grade</th>
                @foreach ($grading_system as $grade)
                {{-- {{dd($grade)}} --}}
                    <th class="p-2 border border-gray-500 text-center ">{{ $grade->grade }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="bg-gray-200 uppercase text-gray-700">
            <tr>
                <td class="p-2 border border-gray-500 text-center">Range</td>
                @foreach ($grading_system as $grade)
                    <td class="p-2 border border-gray-500 text-center">
                        {{ $grade->min_score . "-" . $grade->max_score }}
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
 <h3 class="text-center text-lg font-semibold py-10">
 This report card is invalid without the official school stamp
</h3>
</div>
@else
    <h3 class="text-center text-lg font-semibold">No records found</h3>
@endif

@endsection