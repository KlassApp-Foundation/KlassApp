<div class="overflow-x-auto">
  
    <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-900">
        <thead class="bg-gray-50 uppercase">
            <tr>
                <th class="p-2 text-left font-medium tracking-wider border border-gray-400">No</th>
                <th class="p-2 text-left font-medium tracking-wider border border-gray-400">Student</th>

                @foreach ($subjects as $subject)
                    <th class="p-2 text-left font-medium tracking-wider border border-gray-400">
                        {{ str($subject->name)->limit(4, "") }}
                    </th>
                @endforeach

                <th class="p-2 text-left font-medium tracking-wider border border-gray-400">Total</th>
                <th class="p-2 text-left font-medium tracking-wider border border-gray-400">Division</th>
                <th class="p-2 text-left font-medium tracking-wider border border-gray-400">Position</th>
                <th class="p-2 text-center  font-medium tracking-wider border border-gray-400">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200 ">
            @foreach ($students as $student)
                <tr class="hover:bg-gray-50 transition text-sm font-medium text-gray-800 ">
                    {{-- ID --}}
                    <td class="text-center p-2 whitespace-nowrap border border-gray-400">
                        {{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}
                    </td>

                    {{-- Student Name --}}
                    <td class="p-2 whitespace-nowrap border border-gray-400 ">
                        {{-- {{ filled($student->name) ? $student->name : "student " . $student->id }}    --}}
                        {{ ucwords(strtolower($student->userprofile?->firstname . " ".$student->userprofile?->lastname ?? "Student". $student->id)) }}       
                    </td>

                    {{-- Marks per Subject --}}
                    @foreach ($subjects as $subject)
                        @php
                            $mark = $student?->marks->where("subject_id", $subject->id)->first()->marks;
                        @endphp
                        <td class="p-2 text-center whitespace-nowrap border border-gray-400">
                            {{  $mark ? ceil($mark) : '—'  }}
                            {{-- {{ ceil($student?->marks->where("subject_id", $subject->id)->first()->marks) ?? '—' }} --}}
                        </td>
                    @endforeach

                    {{-- Total Marks --}}
                    <td class=" p-2 text-center whitespace-nowrap border border-gray-400">
                        {{-- {{ $student->marks->sum('marks') ?? '—' }} --}}
                        {{ $student->total ?? '—' }}
                    </td>
 
                    {{-- Grade --}}
                    {{-- {{ dd($student->marks->first()) }} --}}
                    <td class="p-2 text-center whitespace-nowrap border border-gray-400">
                        {{ $student->marks->first()?->grade ?? '—' }}
                    </td>
{{-- position --}}
                    <td class="p-2 text-center whitespace-nowrap border border-gray-400">
                        {{ $student->position ?? '—' }}
                    </td>

                    {{-- Actions --}}
                    <td class="p-2 text-center whitespace-nowrap border border-gray-400 flex items-center justify-center gap-2">
                        {{-- <a href="{{ route('teacher.student.marks.edit',[ $student->marks->first()->exam_id, $student->id, $student->marks->first()->id])}}" class="bg-green-600 hover:bg-green-500 px-2 py-1 rounded text-white">Edit Marks</a> --}}
                        <a href="
                        {{ route("admin.marks.student.class", [$student->id, $class->id,  $exam->id]) }}" 
                         class="bg-green-600 hover:bg-green-500 px-2 py-1 rounded text-white">
                         Report View
                        </a>
                        <a href="
                        {{ route("admin.report.student.class", [$student, $class->id, $exam]) }}" 
                         class="bg-green-600 hover:bg-green-500 px-2 py-1 rounded text-white">
                         Download Report
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
        <div class="mt-4">
    {{ $students->links() }}
    {{-- {{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }} --}}
</div>
</div>