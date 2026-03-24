

<div class="">
    <h3>Test all students</h3>
    <div class="">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    @foreach ($subjects as $subject )
                        <th class="px-2">{{ str($subject->name ?? "N/A")->limit(4, "") }}</th>
                    @endforeach
                    <th class="p-2 border border-black text-center">Total</th>
                </tr>
            </thead>
            <tbody>
               @foreach ($all_students as $student)
            @php
                $total = 0;
            @endphp
            <tr>
                <td class="p-2 border border-black">{{ $student->name }}</td>
                @foreach ($subjects as $subject)
                    @php
                        $mark = $student->marks->firstWhere('subject_id', $subject->id);
                        $score = $mark->score ?? '--';
                        $total += is_numeric($score) ? $score : 0;
                    @endphp
                    <td class="p-2 border border-black text-center">{{ $score }}</td>
                @endforeach
                <td class="p-2 border border-black text-center">{{ $total }}</td>
            </tr>
        @endforeach
            </tbody>
        </table>
    </div>
</div>
{{-- {{ dd($all_students) }} --}}
