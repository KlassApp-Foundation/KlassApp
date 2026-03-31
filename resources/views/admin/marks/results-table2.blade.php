<div class="overflow-x-auto">
  
    <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-700">
        <thead class="bg-gray-50">
            <tr>
                <th class="p-2 text-left font-medium uppercase tracking-wider">ID</th>
                <th class="p-2 text-left font-medium uppercase tracking-wider">Student</th>

                @foreach ($subjects as $subject)
                    <th class="p-2 text-left font-medium uppercase tracking-wider">
                        {{ str($subject->name)->limit(4, "") }}
                    </th>
                @endforeach

                <th class="p-2 text-left font-medium uppercase tracking-wider">Total</th>
                <th class="p-2 text-left font-medium uppercase tracking-wider">Grade</th>
                <th class="p-2 text-left font-medium uppercase tracking-wider">Position</th>
                <th class="p-2 text-left font-medium uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200 ">
            @foreach ($students as $student)
                <tr class="hover:bg-gray-50 transition text-sm font-medium text-gray-800 ">
                    {{-- ID --}}
                    <td class="text-center p-2 whitespace-nowrap border border-gray-400">{{ $loop->iteration }}</td>

                    {{-- Student Name --}}
                    <td class="p-2 whitespace-nowrap border border-gray-400">
                        {{ filled($student->name) ? $student->name : "student " . $student->id }}       
                    </td>

                    {{-- Marks per Subject --}}
                    @foreach ($subjects as $subject)
                        @php
                            $mark = $student->marks->firstWhere('subject_id', $subject->id);
                        @endphp
                        <td class="p-2 text-center whitespace-nowrap border border-gray-400">
                            {{ ceil($mark?->marks) ?? '—' }}
                        </td>
                    @endforeach

                    {{-- Total Marks --}}
                    <td class=" p-2 text-center whitespace-nowrap border border-gray-400">
                        {{-- {{ $student->marks->sum('marks') ?? '—' }} --}}
                        {{ $student->total ?? '—' }}
                    </td>
 
                    {{-- Grade / Remark --}}
                    {{-- <td class=" p-2 whitespace-nowrap text-sm">
                        @foreach ($student->marks as $mark)
                            @if ($mark->remark)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $mark->remark->color_class ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $mark->remark->remark }}
                                </span>
                            @endif
                        @endforeach
                    </td> --}}

                    {{-- Grade --}}
                    <td class="p-2 text-center whitespace-nowrap border border-gray-400">
                        {{ $student->marks->first()?->grade ?? '—' }}
                    </td>
{{-- position --}}
                    <td class="p-2 text-center whitespace-nowrap border border-gray-400">
                        {{ $student->position ?? '—' }}
                    </td>

                    {{-- Actions --}}
                    <td class="p-2 text-center whitespace-nowrap border border-gray-400 flex items-center justify-center gap-2">
                        <a href="#" class="bg-green-500 hover:bg-green-600 px-2 py-1 rounded text-white">Edit Marks</a>
                        <a href="
                        {{ route("admin.marks.student.class", [$student, $student->marks->first()->exam?->section_id]) }}" 
                         class="bg-green-500 hover:bg-green-600 px-2 py-1 rounded text-white">
                         Report View
                        </a>
                        <a href="
                        {{ route("admin.report.student.class", [$student, $student->marks->first()->exam?->section_id]) }}" 
                         class="bg-green-500 hover:bg-green-600 px-2 py-1 rounded text-white">
                         Download Report
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>