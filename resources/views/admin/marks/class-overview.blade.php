@extends('layouts.admin.layout')

@section('content')
<div class="w-full px-4 py-6">
    
    <div class="bg-white shadow-lg rounded-2xl overflow-hidden">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Student Rankings</h2>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-600">
                
                <thead class="bg-gray-100 text-xs uppercase text-gray-700">
                    <tr>
                        <th class="px-6 py-3">#</th>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Class</th>
                        <th class="px-6 py-3">Exam</th>
                        <th class="px-6 py-3">Marks</th>
                        <th class="px-6 py-3">Grade</th>
                        <th class="px-6 py-3">Avg</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Comment</th>
                        <th class="px-6 py-3 text-center">Position</th>
                    </tr>
                </thead>
{{-- {{ dd($exam) }} --}}
                <tbody class="divide-y divide-gray-200">
                    @foreach($rankedStudents as $student)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            
                            <td class="px-6 py-4 font-medium">
                                {{ $loop->iteration }}
                            </td>

                            <td class="px-6 py-4 font-semibold text-gray-800">
                                {{ $student['name'] }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $student['class'] }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $student['exam'] }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-lg text-xs font-medium">
                                    {{ $student['obtained'] }} / {{ $student['possible'] }}
                                </span>
                            </td>

                            <td class="px-6 py-4 font-semibold">
                                <span class="px-2 py-1 rounded-lg text-xs 
                                    @if($student['grade'] == 'A') bg-green-100 text-green-700
                                    @elseif($student['grade'] == 'B') bg-blue-100 text-blue-700
                                    @elseif($student['grade'] == 'C') bg-yellow-100 text-yellow-700
                                    @else bg-red-100 text-red-700
                                    @endif">
                                    {{ $student['grade'] }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                {{ $student['average'] }}
                            </td>

                            <td class="px-6 py-4 font-medium">
                                {{ $student['total'] }}
                            </td>

                            <td class="px-6 py-4 text-gray-500 italic">
                                {{ $student['comment'] }}
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-bold">
                                    {{ $student['position'] }}
                                </span>
                            </td>

                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

    </div>

</div>
@endsection