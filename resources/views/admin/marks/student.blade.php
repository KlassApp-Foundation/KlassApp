@extends('layouts.admin.layout')
@section('content')

<div class="container-fluid w-full lg:mx-2 py-4 px-6">
    {{-- ======== header ======= --}}
   <div class="flex items-center justify-between border-b border-gray-400 pb-4">
        <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center font-bold">
            SL
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-800">{{ $learner->school->name }}</h2>
            <p class="text-sm text-gray-500">P.O.Box 58, Kabale, Uganda</p>
            <p class="text-sm text-gray-500">+256781490899</p>
        </div>

    <div class="text-right">
        <img src="{{ asset('images/el-el.png') }}" alt="STUDENT PHOTO" class="w-24 h-24 object-cover border border-gray-400">
        <p class="text-sm text-gray-500">Term {{$learner->marks->first()->exam?->term }}</p>
    </div>
</div>

    {{-- ============ student's card info --}}
    <table class="w-full mt-6 border border-gray-400 rounded-xl overflow-hidden">
        <thead class="bg-gray-200 text-sm uppercase text-gray-700">
            <tr class="p-3 text-left border border-gray-500">
                <th class="p-3 border border-gray-500">Name</th>
                <th class="p-3 border border-gray-500">Class</th>
                <th class="p-3 border border-gray-500">Stream</th>
                <th class="p-3 border border-gray-500">Term</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="p-3 border border-gray-500">
                    <span class="font-semibold">{{ $learner->userprofile->firstname }}</span>
                    <span class="font-semibold">{{ $learner->userprofile->lastname }}</span>
                </td>
                <td class="p-3 border border-gray-500"> {{ $class_name }}</td>
                <td class="p-3 border border-gray-500"> {{ $learner->stream ?? "A" }}</td>
                <td class="p-3 border border-gray-500"> {{ $learner->marks->first()->exam?->term }}</td>
            </tr>
        </tbody>

    </table>

{{-- ============== performance summary ============== --}}
<div class="flex items-center justify-center py-4 ">
    {{-- <h3 class="font-bold text-2xl text-gray-700">END OF TERM 2 2025 </h3> --}}
    <h3 class="font-bold text-2xl text-gray-700 uppercase">
        {{ $learner->marks->first()->exam?->examType->name }}
        {{ $learner->marks->first()->exam?->term }}
        {{ $learner->marks->first()->exam?->academicYear->name }}
        <span>STUDENT REPORT CARD</span>
    </h3>
</div>

   
   <div class="">

    <table class="w-full border border-gray-400 rounded-xl overflow-hidden">
        {{-- header --}}
    <thead class="bg-gray-100 text-sm uppercase text-gray-600">
        <tr>
            @foreach ($controls as $control)
                <th class="p-3 border border-gray-500">{{ $control }}</th>
            @endforeach
        </tr>
    </thead>
{{-- ==== body ====== --}}

    <tbody class="text-sm">
        @foreach ($subjects as $subject)
          @php
            $mark = $learner->marks
            ->where('subject_id', $subject->id)
            ->sortBy(function($m){
                return match($m->exam->examType->name){
                    "Beginning of Term" => 1,
                    "Mid Term" => 2,
                    "End of Term"  => 3
                };
            });

            $marksByType = $mark->keyBy(fn($m) =>$m->exam?->examType?->name);
            $bot = $marksByType["Beginning of Term"]->marks ?? null;
            $mot = $marksByType["Mid Term"]->marks ?? null;
            $eot = $marksByType["End of Term"]->marks ?? null;

            $values = collect([$bot, $mot, $eot])->filter(fn($v) =>$v !== null);
            $average = $values->count() > 0 ? round($values->avg(), 1) : null;
                // if($average !== null){
                    $subjectAverages[] = $average;
                // }
                $totalAverages = count($subjectAverages) ? array_sum($subjectAverages) : null;
         @endphp
        <tr class="border-t hover:bg-gray-50">
            
            <td class="p-3 border border-gray-500">{{ $subject->name }}</td>
            <td class="p-3 border border-gray-500">100</td>
            
            <td class="p-3 border border-gray-500">
                {{$mark->firstWhere("exam.examType.name", "Beginning of Term")?->marks ?? "-"}}
            </td>
            <td class="p-3 border border-gray-500">
                {{$mark->firstWhere("exam.examType.name", "Mid Term")?->marks ?? "-"}}
            </td>
            <td class="p-3 border border-gray-500">
                {{$mark->firstWhere("exam.examType.name", "End of Term")?->marks ?? "-"}}
            </td>
            <td class="p-3 border border-gray-500">{{$average ?? "-"}}</td>
            <td class="p-3 border border-gray-500">D2</td>
            <td class="p-3 border border-gray-500">more energy</td>
            <td class="p-3 border border-gray-500">elicom</td>
            {{-- calculated --}}
        </tr>
        @endforeach
    </tbody>
    @php
    $marksByType = $learner->marks
        ->groupBy(fn($m) => $m->exam?->examType?->name);
    
    $botTotal = collect($marksByType['Beginning of Term'] ?? [])->sum('marks');
    $motTotal = collect($marksByType['Mid Term'] ?? [])->sum('marks');
    $eotTotal = collect($marksByType['End of Term'] ?? [])->sum('marks');
    @endphp
    {{-- ========= calculated ========== --}}
    <thead class="bg-gray-100 text-sm uppercase text-gray-600">
        <tr>
             <th class="p-3 text-left border border-gray-500">TOTAL</th>
             <th class="p-3 text-left border border-gray-500">{{400}}</th>
             <th class="p-3 text-left border border-gray-500">{{ $botTotal }}</th>
             <th class="p-3 text-left border border-gray-500">{{ $motTotal }}</th>
             <th class="p-3 text-left border border-gray-500">{{ $eotTotal }}</th>
             <th class="p-3 text-left border border-gray-500">{{ $totalAverages }}</th>
             <th class="p-3 text-left border border-gray-500"></th>
             <th class="p-3 text-left border border-gray-500"></th>
        </tr>
    </thead>    
</table>
{{-- =========== remarks =========== --}}
    <table class="w-full mt-8 border border-gray-400 rounded-xl overflow-hidden">
        <thead class="bg-gray-200 text-sm uppercase text-gray-600">
            <tr class="p-3 text-left border border-gray-500">
                <th class="p-3 border border-gray-500">Class teacher's comment</th>
                <th class="p-3 border border-gray-500">Sign</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="p-3 border border-gray-500"> ______________________</td>
                <td class="p-3 border border-gray-500">______________________ </td>
            </tr>
        </tbody>

        <thead class="bg-gray-200 text-sm uppercase text-gray-600">
            <tr class="p-3 text-left border border-gray-400">
                <th class="p-3 border border-gray-500">Head teacher's comment</th>
                <th class="p-3 border border-gray-500">Sign</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="p-3 border border-gray-500"> ______________________</td>
                <td class="p-3 border border-gray-500"> ______________________</td>
            </tr>
        </tbody>
    </table>

    {{-- =========== next term details =========== --}}
    <table class="w-full mt-8 border border-gray-400 rounded-xl overflow-hidden">
        <thead class="bg-gray-200 text-sm uppercase text-gray-600">
            <tr class="p-3 text-left border border-gray-500">
                <th class="p-3 border border-gray-500">Next term begins on</th>
                <th class="p-3 border border-gray-500">End on</th>
                <th class="p-3 border border-gray-500">Fees for next term</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="p-3 border border-gray-500"> 2026/05/12</td>
                <td class="p-3 border border-gray-500">2026/08/17 </td>
                <td class="p-3 border border-gray-500">UGX 500,000 </td>
            </tr>
        </tbody>

    </table>

    {{-- ============= grading system =========== --}}
   <div class="flex flex-col gap-2 mt-6">
    <h3 class="text-xl font-semibold text-gray-600">School Grading System</h3>
     <table class="w-full  border border-gray-400 rounded-xl overflow-hidden">
         <thead class="bg-gray-100 uppercase text-gray-700">
                 <tr>
                    <th class="p-2 border border-black text-center">Grade</th>
                    @foreach ( $grading_system as $range => $grade )
                    <th class="p-2 border border-black text-center">{{ $grade }}</th>
                     @endforeach
                 </tr>
    </thead>
    <tbody class="bg-gray-200 uppercase text-gray-700">
       <tr>
            <td class="p-2 border border-black text-center">Range</td>
               @foreach ( $grading_system as $range => $grade )
                  <td class="p-2 border border-black text-center">{{ $range }}</td>
               @endforeach
         </tr>
   </tbody>
    </table>
   </div>

   </div>
</div>

@endsection
