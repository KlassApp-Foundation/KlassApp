@extends('layouts.admin.layout')

@section('content')
<div class="container-fluid w-full lg:mx-2">

      {{-- Flash Success Message --}}
   @include('partials.message')
    <!-- Page Header -->


    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Enter Marks
        </h1>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ $exams->name }} • {{ $exams->standard->name ?? '' }} • Term {{ $exams->term }}
        </p>
    </div>
{{-- {{ route('teacher.exam.marks.save', $exam->id) }} --}}
{{-- {{ dd($remarks) }} --}}
    <form action="" method="POST">
        @csrf

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">

            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Student Marks
                </h2>
            </div>

            <div class="p-6 overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                    <thead>
                        <tr class="text-left text-sm font-semibold text-gray-600 dark:text-gray-300">
                            <th class="py-3">Student</th>
                            <th class="py-3">Marks</th>
                            <th class="py-3">Out Of</th>
                            <th class="py-3">Remarks</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                        @foreach($students as $student)
                       

                        <tr>
                            <td class="py-3 text-gray-900 dark:text-white">
                                {{ $student->name }}
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

                            <td>
                                 <select name="remarks" id="remarks" class="tw-form-control w-full">
                                  <option value="">Select Remark</option>
                                  @foreach ($remarks as $remark)
                                      <option value="{{ $remark->id }}">{{ $remark->remark }}</option>
                                  @endforeach
                                 </select>
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