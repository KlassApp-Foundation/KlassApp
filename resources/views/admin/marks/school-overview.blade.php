@extends('layouts.admin.layout')

@section('content')

<div class="w-full px-2 py-4 grid grid-cols-1 gap-6">

    <h3 class="text-center text-lg font-semibold text-gray-700">
        {{ $studentCount . " Students found" }}
    </h3>

    <table class="w-full border border-gray-200 rounded-lg overflow-hidden bg-white ">

        {{-- HEADER --}}
        <thead class="bg-gray-100 text-left text-sm text-gray-600">
            <tr>
                <th class="p-3">#</th>
                <th class="p-3">Student</th>

                @foreach($subjects as $subject)
                    <th class="p-3">
                        {{ str($subject->name)->limit(8, '...') }}
                    </th>
                @endforeach

                <th class="p-3">Total</th>
                <th class="p-3">Average</th>
                <th class="p-3">Position</th>
                <th class="p-3">Comment</th>
            </tr>
        </thead>

        {{-- BODY --}}
        <tbody class="divide-y divide-gray-200">

            @foreach($students as $student)

                @php
                    // Map marks by subject_id (safe)
                    $marks = $student->marks->keyBy(fn($m) => (int) $m->subject_id);

                    $total = 0;
                    $count = 0;
                @endphp

                <tr class="hover:bg-gray-50 transition">

                    {{-- LOOP INDEX --}}
                    <td class="px-4 py-3 text-gray-500">
                        {{ $loop->iteration }}
                    </td>

                    {{-- STUDENT NAME --}}
                    <td class="px-4 py-3 font-medium text-gray-800">
                        {{ $student->name }}
                    </td>

                    {{-- SUBJECT MARKS --}}
                    @foreach($subjects as $subject)

                        @php
                            $markObj = $marks->get($subject->id);
                            // dd($marks);
                            $mark = $markObj ? $markObj->marks : 0;

                            if ($mark > 0) {
                                $total += $mark;
                                $count++;
                            }
                        @endphp

                        <td class="px-4 py-3 text-gray-800">
                            {{ $mark }}
                        </td>

                    @endforeach

                    {{-- TOTAL --}}
                    <td class="px-4 py-3 font-semibold text-gray-900">
                        {{ $total }}
                    </td>

                    {{-- AVERAGE --}}
                    <td class="px-4 py-3 text-gray-800">
                        {{ $count ? round($total / $count, 2) : 0 }}
                    </td>

                    {{-- POSITION (you can fill later) --}}
                    <td class="px-4 py-3 text-gray-500">
                        -
                    </td>

                    {{-- COMMENT (optional) --}}
                    <td class="px-4 py-3 text-gray-500">
                        -
                    </td>

                </tr>

            @endforeach

        </tbody>
    </table>

</div>

@endsection