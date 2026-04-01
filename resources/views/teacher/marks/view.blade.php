@extends('layouts.teacher.layout')

@section('content')
<div class="container-fluid w-full lg:mx-2 py-2">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">{{ $exam->examType->code }} Exam Marks</h2>
            <p class="text-sm text-gray-500">student performance</p>
        </div>

        <a href="#" class="inline-flex items-center px-4 py-2 bg-green-500 text-white text-sm font-medium rounded-lg shadow hover:bg-green-600">
            + Add Marks
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200 mb-4 flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
        <input 
            type="text" 
            placeholder="Search student..." 
            class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 outline-none"
        >

        <div class="flex gap-2">
            <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option>All Subjects</option>
            </select>

            <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option>All Exams</option>
            </select>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white border border-gray-200 rounded-lg shadow-sm p-2">
        <h3 class='text-center py-2 font-semibold text-gray-700'>
            <span>{{$exam->examType->name}}</span>
            <span>{{ $exam->academicTerm->name }}</span>
            <span>{{$exam->subject->name}}</span>
             <span>Exam</span> 
             
            </h3>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left border border-gray-400">ID</th>
                    <th class="px-4 py-3 text-left border border-gray-400">Student</th>
                    <th class="px-4 py-3 text-left border border-gray-400">Subject</th>
                    <th class="px-4 py-3 text-left border border-gray-400">Marks</th>
                    <th class="px-4 py-3 text-left border border-gray-400">Exam</th>
                    <th class="px-4 py-3 text-left border border-gray-400">Grade</th>
                    <th class="px-4 py-3 text-left border border-gray-400">Comment</th>
                    <th class="px-4 py-3 text-center border border-gray-400">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($marks as $mark)
                    <tr class="hover:bg-gray-50 transition">
                        {{-- ID --}}
                        <td class="p-2 text-gray-500 border border-gray-400">
                            {{ $loop->iteration }}
                        </td>

                        {{-- Student --}}
                        <td class="p-2 font-medium text-gray-800 border border-gray-400">
                            {{ filled($mark->student->name) ? $mark->student->name : 'student' . " ". $mark->student->id }}
                        </td>

                        {{-- Subject --}}
                        <td class="p-2 text-xs text-gray-600 border border-gray-400">
                            {{ $mark->subject->name ?? 'N/A' }}
                        </td>

                        {{-- Marks --}}
                        <td class="p-2 border border-gray-400">
                            <span class="px-2 py-1 text-sm font-semibold rounded-full
                                {{ $mark->marks >= 70 ? 'bg-green-100 text-green-700' : 
                                   ($mark->marks >= 50 ? 'bg-yellow-100 text-yellow-700' : 
                                   'bg-red-100 text-red-700') }}">
                                {{ ceil($mark->marks) }}
                            </span>
                        </td>

                        {{-- Exam --}}
                        <td class="px-4 text-xs py-3 text-gray-600 border border-gray-400">
                            {{ $mark->subject->name . " Exam" ?? 'N/A' }}
                        </td>

                        {{-- Teacher --}}
                        {{-- <td class="p-2 text-gray-600">
                            {{ $mark->teacher->name ?? 'N/A' }}
                        </td> --}}

                         {{-- grade --}}
                        <td class="p-2 text-gray-600 border border-gray-400">
                            {{ $mark->grade ?? 'N/A' }}
                        </td>

                         {{-- remark --}}
                        <td class="p-2 text-gray-600 border border-gray-400">
                            {{ str($mark->remark->remark ?? 'N/A')->limit(20, '...') }}
                        </td>

                        {{-- Actions --}}
                        <td class="p-2 flex items-center justify-center gap-4 border border-gray-400">
                                <a href="{{ route('teacher.student.marks.edit',[ $mark->exam->id, $mark->student_id, $mark->id])}}" 
                                   class="px-3 py-1 text-xs font-medium bg-green-500 text-white bg-blue-50 rounded-md hover:bg-green-600">
                                    Edit
                                </a>

                                <button 
                                    class="px-3 py-1 text-xs font-medium text-white bg-red-400 rounded-md hover:bg-red-500">
                                    Delete
                                </button>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="7" class="text-center py-10">
                            <p class="text-gray-500 text-sm">No marks found</p>
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
    <div class="flex items-center justify-end py-4 text-sm font-semibold text-gray-700">
        {{-- <span>By</span> --}}
            <span>
                {{ "Tr. " . $exam->officialTeacher->firstname . " " . $exam->officialTeacher->firstname }}
            </span>
    </div>
</div>
@endsection