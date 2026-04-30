@extends('layouts.teacher.layout')

@section('content')
<div class="container-fluid w-full lg:mx-2 py-2 text-gray-800">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
        <div>
            <h2 class="text-2xl font-semibold ">{{ $exam->examType->code }} Exam Marks</h2>
            <p class="text-sm ">student performance</p>
        </div>

        <a href="#" class="inline-flex items-center px-4 py-2 bg-green-500 text-white text-sm font-medium rounded-lg shadow hover:bg-green-600">
            + Add Marks
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white p-1 rounded-lg shadow-sm border border-gray-200 mb-4 flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
        <input 
            type="text" 
            placeholder="Search student..." 
            class="w-full md:w-1/3 px-3 py-1 border border-gray-400 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 outline-none"
        >

        <form action="" method="GET" class="flex gap-1">
            <select class="p-1 border border-gray-300 rounded-lg text-sm">
                <option>All Classes</option>
            </select>

            <select class="p-1 border border-gray-300 rounded-lg text-sm">
                <option>All Exams</option>
            </select>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white border border-gray-200 rounded-lg shadow-sm p-1">
        <h3 class='text-center py-2 font-semibold '>
            <span>{{$exam->examType->name}}</span>
            <span>{{ $exam->academicTerm->name }}</span>
            <span>{{$exam->subject->name}}</span>
             <span>Exam</span> 
             
            </h3>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200  text-sm">
                <tr>
                    @foreach ( $headers as $header )
                    <th class="px-4 py-3 text-left border border-gray-400">{{$header}}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($allStudents as $student)
                    <tr class="hover:bg-gray-50 transition">
                        {{-- ID --}}
                        <td class="p-1  border border-gray-400">
                            {{ $loop->iteration }}
                        </td>
                        {{-- Student --}}
                        <td class="p-1 font-medium  border border-gray-400 ">
                            {{
                                isset($student->userprofile) ?
                                 ucwords(strtolower($student->userprofile->firstname . " " .
                                 $student->userprofile->lastname))
                                 : "student ". $mark->student->id 
                                 }}
                        </td>

                        {{-- Subject --}}
                        <td class="p-1 text-xs  border border-gray-400">
                            {{ $student->marks->first()->marks?? '-' }}
                        </td>

                        {{-- Marks --}}
                        {{-- <td class="p-1 border border-gray-400">
                            <span class="px-2 py-1 text-sm font-semibold rounded-full
                                {{ $mark->marks >= 70 ? 'bg-green-100 text-green-700' : 
                                   ($mark->marks >= 50 ? 'bg-yellow-100 text-yellow-700' : 
                                   'bg-red-100 text-red-700') }}">
                                {{ ceil($mark->marks) }}
                            </span>
                        </td> --}}
                         {{-- grade --}}
                         {{-- {{ dd($student->marks->first()->grade) }} --}}
                        <td class="p-1  border border-gray-400">
                            {{ $student->marks->first()->grade ?? '-' }}
                        </td>

                         {{-- remark --}}
                        <td class="p-1  border border-gray-400">
                            {{ str($student->marks->first()->remark->remark ?? 'missed exam')->limit(20, '...') }}
                        </td>

                        {{-- Actions --}}
                        <td class="p-1 border border-gray-400 space-x-6 text-center">
                            @if ($student->marks->isNotEmpty())
                                <a href="{{ route('teacher.student.marks.edit',
                                [$student->marks->first()->exam->id, $student->marks->first()->student_id, $student->marks->first()->id])}}" 
                                   class="px-3 py-1 text-xs font-medium bg-green-500 text-white bg-blue-50 rounded-md hover:bg-green-600">
                                Edit
                               </a>

                               {{-- <button 
                                    class="px-3 py-1 text-xs font-medium text-white bg-red-400 rounded-md hover:bg-red-500">
                                    Delete
                                </button> --}}
                            @endif
                                
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="7" class="text-center py-10">
                            <p class=" text-sm">No marks found</p>
                            {{-- {{ route('teacher.exam.marks.enter', ["exam" => $exam]) }} --}}
                            <a href="{{ route('teacher.exam.marks.enter', ["exam" => $exam]) }}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                                + Add your first record
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex items-center justify-end py-4 text-sm font-semibold ">
        {{-- <span>By</span> --}}
            <span>
                {{ "Tr. " . $exam->officialTeacher->firstname . " " . $exam->officialTeacher->firstname }}
            </span>
    </div>
</div>
@endsection