<div class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-emerald-700">Class roster</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Classes &amp; streams</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    A read-only view of the classes and teaching assignments you are authorized to see.
                </p>
            </div>
            @if($selectedYear)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Academic year</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $selectedYear->name }}</p>
                </div>
            @endif
        </div>

        @if($years->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                <p class="text-lg font-semibold text-slate-900">No academic year is available yet</p>
                <p class="mt-2 text-sm text-slate-500">Classes will appear here once an academic year is set up.</p>
            </div>
        @else
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Teaching load</p>
                        <p class="mt-1 text-sm text-slate-500">Your active classes for the selected academic year.</p>
                    </div>
                    <label class="block min-w-44">
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400">Academic year</span>
                        <select wire:model.live="selectedAcademicYearId" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach($years as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

            @if($sections->count())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left">
                        <thead class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-3 py-3">Class</th>
                                <th class="px-3 py-3">Level</th>
                                <th class="px-3 py-3">Students</th>
                                <th class="px-3 py-3">Streams</th>
                                <th class="px-3 py-3">Class teacher</th>
                                <th class="px-3 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                    @foreach($sections as $section)
                        @php
                            $streams = $section->standardLink;
                            $streamNames = collect([$section->stream])->filter()->unique()->values();
                            $streamLabel = \App\Support\TeacherRosterFormatter::formatStreams($streamNames);
                            $assignedStreams = $streams->filter(fn ($stream) => $stream->class_teacher_id !== null)->count();
                            $level = $streams->first()?->standard?->name;
                            $sectionTeacher = $section->classTeacher;
                            $studentCount = $streams->sum(fn ($stream) => $stream->studentAcademic->count());
                            $showRoute = auth()->user()->usergroup_id === 3
                                ? route('admin.classes.show', ['section' => $section->id, 'academic_year_id' => $selectedYear->id])
                                : route('teacher.classes.show', ['section' => $section->id, 'academic_year_id' => $selectedYear->id]);
                        @endphp
                        <tr class="transition hover:bg-emerald-50/40">
                            <td class="px-3 py-4"><a href="{{ $showRoute }}" class="font-bold text-slate-900 hover:text-emerald-700">{{ $section->name }}</a></td>
                            <td class="px-3 py-4 text-sm text-slate-600">{{ $level ?: 'Level pending' }}</td>
                            <td class="px-3 py-4 text-sm font-semibold text-slate-800">{{ $studentCount }}</td>
                            <td class="px-3 py-4 text-sm text-slate-600">{{ $streamLabel === '—' ? 'No streams yet' : $streamLabel }}</td>
                            <td class="px-3 py-4 text-sm text-slate-600">{{ $sectionTeacher?->name ?: 'Not assigned' }}</td>
                            <td class="px-3 py-4 text-right"><a href="{{ auth()->user()->usergroup_id === 5 ? route('teacher.classes.manage', ['section' => $section->id, 'academic_year_id' => $selectedYear->id]) : $showRoute }}" class="text-xs font-bold uppercase tracking-wider text-emerald-700 hover:text-emerald-900">Manage</a></td>
                        </tr>
                    @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <p class="text-lg font-semibold text-slate-900">No classes assigned for this year</p>
                    <p class="mt-2 text-sm text-slate-500">Your teaching assignments will appear here once they are linked to this academic year.</p>
                </div>
            @endif
            </div>
        @endif
    </div>
</div>
