@extends('layouts.teacher.layout')

@section('content')
<div class="container-fluid w-full py-3 lg:mx-2">

      {{-- Flash Success Message --}}
   @include('partials.message')
    <!-- Page Header -->


    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Enter Marks
        </h1>

        <p class="mt-1  text-gray-600 dark:text-gray-400">
            {{-- {{ dd($exam) }} --}}
            {{ $exam->subject->name }} • {{ $exam->academicTerm->name }}
        </p>
    </div>
    <form action="{{ route('teacher.exam.marks.save', $exam)  }}" method="POST">
        @csrf

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">

            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Exam Marks for {{ $subject->name }} 
                </h2>
                <p>{{ $total }} students</p>
            </div>

            <div class="p-6 overflow-x-auto text-sm">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                    <thead>
                        <tr class="text-left uppercase font-semibold text-gray-700 dark:text-gray-300">
                            <th class="py-3">Student</th>
                            <th class="py-3">Marks</th>
                            <th class="py-3">Out Of</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($allStudents as $student)
                       {{-- {{ dd($student) }} --}}

                        <tr>
                            <td class="py-3 text-gray-900 dark:text-white flex items-center gap-2">
                                <span>{{ $student->userprofile->firstname }}</span>
                                <span>{{ $student->userprofile->lastname }}</span>
                            </td>

                            <td>
                                <input type="number"
                                       name="marks[{{ $student->id }}]"
                                       class="tw-form-control w-24"
                                       min="0"
                                       max="100"
                                       oninput="this.value = Math.min(100, Math.max(0, this.value))"
                                       placeholder="0">
                            </td>

                            <td>
                                <input type="number"
                                       name="out_of[{{ $student->id }}]"
                                       value="100"
                                       class="tw-form-control w-24">
                            </td>
                        </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

            <!-- Submit -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">

                <button type="submit"
                        class="py-2 px-5 rounded text-white bg-green-500 hover:bg-green-600">
                    Save Marks
                </button>

            </div>

        </div>

    </form>

</div>
@endsection