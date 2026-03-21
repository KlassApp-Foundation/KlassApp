@extends('layouts.admin.layout')
@section('content')

<div class="overflow-x-auto">
    <h2 class="text-xl font-bold mb-4">
        Marks Overview - {{ $standard->name }} | Year {{ $yearId }} | Term {{ $term }}
    </h2>

    <table class="table-auto w-full border-collapse border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="border px-2 py-1">Student Name</th>
                @foreach($marks->first() as $mark)
                    <th class="border px-2 py-1">{{ $mark->subject->name }}</th>
                @endforeach
                <th class="border px-2 py-1">Total</th>
                <th class="border px-2 py-1">Average</th>
                <th class="border px-2 py-1">Position</th>
                <th class="border px-2 py-1">Comment</th>
            </tr>
        </thead>
        <tbody>
            @foreach($marks as $studentId => $studentMarks)
                @php
                    $student = $studentMarks->first()->student;
                    $total   = $studentMarks->sum('marks');
                    $average = $studentMarks->avg('marks');
                @endphp
                <tr>
                    <td class="border px-2 py-1">{{ $student->name }}</td>
                    @foreach($studentMarks as $mark)
                        <td class="border px-2 py-1">{{ $mark->marks }}</td>
                    @endforeach
                    <td class="border px-2 py-1">{{ $total }}</td>
                    <td class="border px-2 py-1">{{ number_format($average,2) }}</td>
                    <td class="border px-2 py-1">{{ $positions[$studentId] }}</td>
                    <td class="border px-2 py-1">{{ Str::limit($studentMarks->first()->remark?->name, 20) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
