@extends('layouts.admin.layout')
@section('content')

@php
$allowedTypes = ["Beginning of Term", "Mid Term", "End of Term"];
$subjectAverages = [];

// filter once globally
$filteredMarks = $learner->marks->filter(
    fn($m) => in_array($m->exam?->examType?->name, $allowedTypes)
);
@endphp

<div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
    {{-- ======== header ======= --}}
   <div class="flex items-center justify-between border-b border-gray-200 pb-4">
        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center font-bold text-gray-600">
            SL
        </div>
        <div>
            <h2 class="ds-section-title">{{ $learner->school->name }}</h2>
            <p class="ds-section-subtitle">P.O.Box 58, Kabale, Uganda</p>
            <p class="ds-section-subtitle">+256781490899</p>
        </div>

    <div class="text-right">
        <img src="{{ asset('images/el-el.png') }}" alt="STUDENT PHOTO" class="w-24 h-24 object-cover border border-gray-200 rounded">
        <p class="text-xs text-gray-500 mt-1">Term {{$filteredMarks->first()?->exam?->term }}</p>
    </div>
</div>

    {{-- ============ student's card info --}}
    <table class="ds-table w-full mt-6">
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
                <td>
                    <span class="font-semibold">{{ $learner->userprofile->firstname }}</span>
                    <span class="font-semibold">{{ $learner->userprofile->lastname }}</span>
                </td>
                <td>{{ $class_name }}</td>
                <td>{{ $learner->stream ?? "A" }}</td>
                <td>{{ $filteredMarks->first()?->exam?->term }}</td>
            </tr>
        </tbody>
    </table>

{{-- ============== performance summary ============== --}}
<div class="flex items-center justify-center py-4">
    <h3 class="ds-section-title text-center">
        {{ $filteredMarks->first()?->exam?->examType->name }}
        {{ $filteredMarks->first()?->exam?->term }}
        {{ $filteredMarks->first()?->exam?->academicYear->name }}
        <span>STUDENT REPORT CARD</span>
    </h3>
</div>

   <div class="ds-table-wrap mt-4">

    <table class="ds-table">
        {{-- header --}}
    <thead>
        <tr>
            @foreach ($controls as $control)
                <th>{{ $control }}</th>
            @endforeach
        </tr>
    </thead>

    <tbody class="text-sm">
        @foreach ($subjects as $subject)
          @php
            $mark = $filteredMarks
            ->where('subject_id', $subject->id)
            ->sortBy(fn($m) => array_search($m->exam->examType->name, $allowedTypes));

            $marksByType = $mark->keyBy(fn($m) =>$m->exam?->examType?->name);

            $bot = $marksByType["Beginning of Term"]->marks ?? null;
            $mot = $marksByType["Mid Term"]->marks ?? null;
            $eot = $marksByType["End of Term"]->marks ?? null;

            $values = collect([$bot, $mot, $eot])->filter(fn($v) =>$v !== null);
            $average = $values->count() > 0 ? round($values->avg(), 1) : null;

            $subjectAverages[] = $average ?? 0;
            $totalAverages = count($subjectAverages) ? array_sum($subjectAverages) : null;

            if ($average >= 85) {
               $grade = "D1";
               $remark = "Well done!";
           } elseif ($average >= 80) {
               $grade = "D2";
               $remark = "Good work!";
           } elseif ($average >= 75) {
               $grade = "C3";
               $remark = "Promising Learner!";
           } elseif ($average >= 65) {
               $grade = "C4";
               $remark = "Fairly done!";
           } elseif ($average >= 55) {
               $grade = "C5";
               $remark = "Put more energy!";
           } elseif ($average >= 50) {
               $grade = "C6";
               $remark = "Improve!";
           } elseif ($average >= 45) {
               $grade = "P7";
               $remark = "Read harder!";
           } elseif ($average >= 40) {
               $grade = "P8";
               $remark = "Read harder!";
           } else {
               $grade = "F9";
               $remark = "Wake up!";
           }
         @endphp

        <tr>
            <td class="font-medium">{{ $subject->name }}</td>
            <td>100</td>
            <td>{{ $bot ?? "-" }}</td>
            <td>{{ $mot ?? "-" }}</td>
            <td>{{ $eot ?? "-" }}</td>
            <td class="font-semibold">{{$average ?? "-"}}</td>
            <td>{{ $grade }}</td>
            <td>{{$remark}}</td>
            <td>{{$learner->marks->first()->teacher->name ?? '-'}}</td>
        </tr>
        @endforeach
    </tbody>

    @php
    $marksByType = $filteredMarks
        ->groupBy(fn($m) => $m->exam?->examType?->name);

    $botTotal = collect($marksByType['Beginning of Term'] ?? [])->sum('marks');
    $motTotal = collect($marksByType['Mid Term'] ?? [])->sum('marks');
    $eotTotal = collect($marksByType['End of Term'] ?? [])->sum('marks');
    @endphp

    {{-- ========= calculated ========== --}}
    <tfoot>
        <tr class="bg-gray-50 font-semibold">
             <td>TOTAL</td>
             <td>{{400}}</td>
             <td>{{ $botTotal }}</td>
             <td>{{ $motTotal }}</td>
             <td>{{ $eotTotal }}</td>
             <td>{{ $totalAverages }}</td>
             <td></td>
             <td></td>
             <td></td>
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
        <td>2026/05/12</td>
        <td>2026/08/17</td>
        <td>UGX 500,000</td>
    </tr>
    </tbody>
</table>

{{-- ============= grading system =========== --}}
<div class="flex flex-col gap-2 mt-6">
    <h3 class="ds-section-title">School Grading System</h3>
    <table class="ds-table">
        <thead>
            <tr>
                <th>Grade</th>
                @foreach ($grading_system as $range => $grade)
                    <th class="text-center">{{ $grade }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-medium">Range</td>
                @foreach ($grading_system as $range => $grade)
                    <td class="text-center">{{ $range }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>

</div>
@endsection