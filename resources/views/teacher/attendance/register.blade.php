{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<style>
    [data-attendance-row] { border: 1px solid #E2E8F0; border-radius: 12px; background: #FFFFFF; padding: 12px; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px 16px; align-items: center; }
    [data-attendance-row][data-status="1"] { border-color: #BBF7D0; background: #F0FDF4; }
    [data-attendance-row][data-status="0"] { border-color: #FECACA; background: #FEF2F2; }
    .attendance-choice { min-height: 44px; min-width: 104px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-sizing: border-box; border: 1px solid #CBD5E1; border-radius: 10px; background: #FFFFFF; color: #334155; font-size: 14px; font-weight: 700; cursor: pointer; padding: 0 12px; }
    .attendance-choice.is-present { border: 2px solid #15803D; background: #DCFCE7; color: #166534; }
    .attendance-choice.is-absent { border: 2px solid #B91C1C; background: #FEE2E2; color: #7F1D1D; }
    .attendance-choice input { accent-color: currentColor; }
    .attendance-comment { grid-column: 1 / -1; display: none; }
    [data-attendance-row][data-status="0"] .attendance-comment { display: block; }
    .attendance-comment input { width: 100%; min-height: 42px; box-sizing: border-box; border: 1px solid #E2E8F0; border-radius: 8px; padding: 8px 10px; font-size: 14px; background: #FFFFFF; color: #0F172A; }
    .attendance-savebar { position: sticky; bottom: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-top: 12px; padding: 12px; border: 1px solid #E2E8F0; border-radius: 12px; background: #FFFFFF; box-shadow: 0 -4px 20px rgba(15, 23, 42, 0.06); }
    @media (max-width: 640px) {
        [data-attendance-row] { grid-template-columns: minmax(0, 1fr); }
        .attendance-choice { flex: 1; min-width: 0; }
    }
</style>

<div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4" data-testid="teacher-attendance-register" v-pre>
    @include('layouts.partials.page-header', [
        'title' => 'Take attendance',
        'subtitle' => 'Everyone is present unless you tap Absent. Save once.',
    ])

    @if (session('successmessage'))
        <div role="status" data-testid="attendance-flash" style="margin-top:12px;border:1px solid #BBF7D0;background:#F0FDF4;color:#166534;border-radius:8px;padding:10px 12px;font-size:14px;">
            {{ session('successmessage') }}
        </div>
    @endif
    @if (session('errormessage'))
        <div role="alert" data-testid="attendance-error" style="margin-top:12px;border:1px solid #FECACA;background:#FEF2F2;color:#991B1B;border-radius:8px;padding:10px 12px;font-size:14px;">
            {{ session('errormessage') }}
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" data-testid="attendance-validation" style="margin-top:12px;border:1px solid #FECACA;background:#FEF2F2;color:#991B1B;border-radius:8px;padding:10px 12px;font-size:14px;">
            @foreach ($errors->all() as $error)
                <p style="margin:0 0 4px;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ $filterUrl }}" class="ds-card ds-card-padding-default mt-6" data-testid="attendance-filters">
        <div class="grid gap-4 md:grid-cols-4">
            <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400">Class</span>
                <select name="section_id" id="attendance-section" class="ds-form-input ds-form-select w-full">
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected((int) $selectedSection === (int) $section->id)>{{ $section->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400">Stream</span>
                <select name="standardLink_id" id="attendance-stream" class="ds-form-input ds-form-select w-full" data-testid="attendance-stream-filter">
                    <option value="">{{ $isClassHead ? 'Whole class' : 'All my streams' }}</option>
                    @foreach ($streamChoices as $choice)
                        <option value="{{ $choice['id'] }}" data-section="{{ $choice['base_id'] }}" @selected((int) $selectedStream === (int) $choice['id'])>
                            {{ $choice['label'] }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400">Date</span>
                <input type="date" name="date" value="{{ $selectedDate }}" max="{{ now()->format('Y-m-d') }}" class="ds-form-input w-full">
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400">Session</span>
                <select name="session" class="ds-form-input ds-form-select w-full">
                    <option value="forenoon" @selected($selectedSession === 'forenoon')>Morning</option>
                    <option value="afternoon" @selected($selectedSession === 'afternoon')>Afternoon</option>
                </select>
            </label>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="submit" class="ds-btn ds-btn-primary ds-btn-md">Open register</button>
            <a href="{{ route('teacher.attendance.index') }}" class="ds-btn ds-btn-ghost ds-btn-md">Attendance history</a>
        </div>
    </form>

    @if (! $scopeReady)
        <div class="ds-card ds-card-padding-default mt-6">
            <p class="text-lg font-semibold">Set up the academic year first</p>
            <p class="mt-2 text-sm" style="color: var(--d-muted);">Attendance needs a current academic year and a class.</p>
        </div>
    @elseif ($students->isEmpty())
        <div class="ds-card ds-card-padding-default mt-6" data-testid="attendance-empty">
            <p class="text-lg font-semibold">No students to show</p>
            <p class="mt-2 text-sm" style="color: var(--d-muted);">
                @if ($sections->isEmpty())
                    No active class or stream is assigned to you yet. Ask the school admin to assign you as a class teacher.
                @else
                    This class or stream has no active students yet.
                @endif
            </p>
        </div>
    @else
        <form method="POST" action="{{ $saveUrl }}" data-testid="attendance-form">
            @csrf
            <input type="hidden" name="section_id" value="{{ $selectedSection }}">
            <input type="hidden" name="standardLink_id" value="{{ $selectedStream }}">
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <input type="hidden" name="session" value="{{ $selectedSession }}">
            <input type="hidden" name="rows_json" id="attendance-rows-json" value="">

            <div class="ds-card ds-card-padding-default mt-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold" data-testid="attendance-class-name">
                            {{ $className ?: 'Your class' }}@if (! $wholeClass && $selectedStream) <span class="text-sm font-normal" style="color: var(--d-muted);">· one stream</span>@endif
                        </h2>
                        <p class="mt-1 text-sm" style="color: var(--d-muted);">
                            {{ \Carbon\Carbon::parse($selectedDate)->format('l, j M Y') }} · {{ $selectedSession === 'afternoon' ? 'Afternoon' : 'Morning' }}
                            @if ($wholeClass && $isClassHead) · class head: every stream @endif
                        </p>
                    </div>
                    <p class="text-sm font-semibold">
                        <span data-present-count>{{ $presentCount }}</span> present ·
                        <span data-absent-count>{{ $absentCount }}</span> absent
                    </p>
                </div>

                <div class="mt-4" style="display:flex;flex-direction:column;gap:10px;">
                    @foreach ($students as $student)
                        @php
                            $status = (string) old('rows.'.$student['user_id'].'.status', (string) $student['status']);
                            $comment = old('rows.'.$student['user_id'].'.comment', $student['comment']);
                        @endphp
                        <div data-attendance-row data-status="{{ $status }}" data-user-id="{{ $student['user_id'] }}" data-testid="attendance-row">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold" data-testid="attendance-student-name">{{ $student['name'] }}</p>
                                <p class="truncate text-xs" style="color: var(--d-muted);" data-testid="attendance-stream-label">Stream {{ $student['stream'] }}</p>
                            </div>
                            <div class="flex gap-2" role="radiogroup" aria-label="{{ $student['name'] }} was">
                                <label class="attendance-choice" data-choice="present">
                                    <input type="radio" name="rows[{{ $student['user_id'] }}][status]" value="1" @checked($status === '1') data-testid="status-present">
                                    <span>Present</span>
                                </label>
                                <label class="attendance-choice" data-choice="absent">
                                    <input type="radio" name="rows[{{ $student['user_id'] }}][status]" value="0" @checked($status === '0') data-testid="status-absent">
                                    <span>Absent</span>
                                </label>
                            </div>
                            <label class="attendance-comment">
                                <span class="sr-only">Comment for {{ $student['name'] }}</span>
                                <input type="text" name="rows[{{ $student['user_id'] }}][comment]" value="{{ $comment }}" maxlength="500" placeholder="Optional comment (the parent sees this with the absence)" data-testid="attendance-comment">
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="attendance-savebar">
                <p class="text-sm" style="color: var(--d-muted);">
                    <span data-present-count>{{ $presentCount }}</span> present ·
                    <span data-absent-count>{{ $absentCount }}</span> absent
                    · <span class="font-semibold">{{ $students->count() }}</span> students
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="ds-btn ds-btn-outline ds-btn-md" data-testid="mark-all-present">Mark all present</button>
                    <button type="submit" class="ds-btn ds-btn-primary ds-btn-md" data-testid="save-attendance">Save attendance</button>
                    <span class="text-xs" style="color: var(--d-muted);" data-testid="mark-all-scope">{{ $wholeClass ? 'Sets every student in this class. Nothing is saved until Save attendance.' : 'Sets every student on this register. Other streams stay unchanged until you open them. Nothing is saved until Save attendance.' }}</span>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var section = document.getElementById('attendance-section');
        var stream = document.getElementById('attendance-stream');

        function filterStreams() {
            if (!section || !stream) return;
            var sectionId = section.value;
            var selected = stream.value;
            Array.prototype.forEach.call(stream.options, function (option) {
                if (!option.value) return;
                var matches = !sectionId || option.dataset.section === sectionId;
                option.hidden = !matches;
                option.disabled = !matches;
            });
            if (selected) {
                var current = stream.options[stream.selectedIndex];
                if (current && current.disabled) {
                    stream.value = '';
                }
            }
        }

        if (section) {
            section.addEventListener('change', function () {
                if (stream) stream.value = '';
                filterStreams();
            });
            filterStreams();
        }

        function liveRows() {
            var root = document.querySelector('[data-testid="teacher-attendance-register"]');
            return root ? Array.prototype.slice.call(root.querySelectorAll('[data-attendance-row]')) : [];
        }

        function paint(row) {
            var checked = row.querySelector('input[type="radio"]:checked');
            row.dataset.status = checked ? checked.value : '1';
            row.style.background = row.dataset.status === '0' ? '#FEF2F2' : '#F0FDF4';
            row.style.borderColor = row.dataset.status === '0' ? '#FECACA' : '#BBF7D0';
            Array.prototype.forEach.call(row.querySelectorAll('.attendance-choice'), function (label) {
                var input = label.querySelector('input');
                var on = !!(input && input.checked);
                var present = on && input.value === '1';
                var absent = on && input.value === '0';
                label.classList.toggle('is-selected', on);
                label.classList.toggle('is-present', present);
                label.classList.toggle('is-absent', absent);
                // The register sits inside the Vue root, which does not keep the
                // page stylesheet on these controls. Paint the colour directly.
                label.style.background = present ? '#DCFCE7' : (absent ? '#FEE2E2' : '#FFFFFF');
                label.style.borderColor = present ? '#15803D' : (absent ? '#B91C1C' : '#CBD5E1');
                label.style.color = present ? '#166534' : (absent ? '#7F1D1D' : '#334155');
            });
        }

        function counts() {
            var present = 0;
            var absent = 0;
            liveRows().forEach(function (row) {
                if (row.dataset.status === '0') absent++; else present++;
            });
            Array.prototype.forEach.call(document.querySelectorAll('[data-present-count]'), function (el) { el.textContent = present; });
            Array.prototype.forEach.call(document.querySelectorAll('[data-absent-count]'), function (el) { el.textContent = absent; });
        }

        function markRowPresent(row) {
            var present = row.querySelector('input[type="radio"][value="1"]');
            var absent = row.querySelector('input[type="radio"][value="0"]');
            if (absent) {
                absent.checked = false;
                absent.removeAttribute('checked');
            }
            if (present) {
                present.checked = true;
                present.setAttribute('checked', 'checked');
            }
            paint(row);
        }

        // The layout mounts Vue on #app after this script, which replaces the
        // register nodes. Listen on document, and paint again after that mount.
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-testid="mark-all-present"]');
            if (!button) return;
            event.preventDefault();
            liveRows().forEach(markRowPresent);
            counts();
        });

        document.addEventListener('change', function (event) {
            if (!event.target.matches || !event.target.matches('[data-attendance-row] input[type="radio"]')) return;
            var row = event.target.closest('[data-attendance-row]');
            if (!row) return;
            paint(row);
            counts();
        });

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form.matches || !form.matches('[data-testid="attendance-form"]')) return;
            var payload = {};
            liveRows().forEach(function (row) {
                var checked = row.querySelector('input[type="radio"]:checked');
                var comment = row.querySelector('input[name$="[comment]"]');
                payload[row.dataset.userId] = {
                    status: checked ? checked.value : '1',
                    comment: comment ? comment.value : ''
                };
            });
            var hidden = document.getElementById('attendance-rows-json');
            if (hidden) hidden.value = JSON.stringify(payload);
            Array.prototype.forEach.call(form.querySelectorAll('[data-attendance-row] input'), function (input) {
                input.disabled = true;
            });
        });

        function boot() {
            filterStreams();
            liveRows().forEach(paint);
            counts();
        }

        window.addEventListener('load', boot);
    })();
</script>
@endpush
