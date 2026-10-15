{{-- SPDX-License-Identifier: MIT --}}
{{-- Read-only class marks for the latest exam: students by code, a row per subject, the grade under the mark. --}}
@extends('layouts.admin.layout')

{{-- Outside #app: the Vue root drops <style> tags inside its template. --}}
@section('outside-app')
<style>
    .cm { max-width: 1120px; padding: 20px 16px 40px; color: var(--d-text, #1E293B); }
    .cm h1 { font-size: 22px; line-height: 1.25; margin: 0 0 4px; }
    .cm-sub { color: var(--d-text-secondary, #64748B); margin: 0 0 16px; }
    .cm-classes { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 16px; padding: 0; list-style: none; }
    .cm-classes a { display: inline-flex; align-items: center; min-height: 44px; padding: 0 14px; border: 1px solid var(--d-border, #E2E8F0); border-radius: var(--d-radius-md, 8px); background: var(--d-white, #fff); color: var(--d-text, #1E293B); font-weight: 600; text-decoration: none; }
    .cm-classes a[aria-current="page"] { background: var(--d-accent, #15803D); border-color: var(--d-accent, #15803D); color: #fff; }
    .cm-classes a:focus-visible { outline: none; box-shadow: var(--d-focus-ring, 0 0 0 3px rgba(30,111,217,.25)); }
    .cm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin: 0; padding: 0; list-style: none; }
    .cm-card { background: var(--d-white, #fff); border: 1px solid var(--d-border, #E2E8F0); border-radius: var(--d-radius-lg, 12px); padding: 14px; min-width: 0; }
    .cm-card h2 { display: flex; justify-content: space-between; gap: 8px; font-size: 15px; margin: 0 0 8px; }
    .cm-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .cm-name { color: var(--d-text-secondary, #64748B); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cm-row { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 6px 0; border-top: 1px solid var(--d-border, #E2E8F0); }
    .cm-mark { text-align: right; font-variant-numeric: tabular-nums; }
    .cm-mark b { display: block; font-size: 16px; }
    .cm-mark span { display: block; font-size: 12px; color: var(--d-text-secondary, #64748B); }
    .cm-empty { background: var(--d-white, #fff); border: 1px solid var(--d-border, #E2E8F0); border-radius: var(--d-radius-lg, 12px); padding: 20px; }
</style>
@endsection

@section('content')
<div class="cm" data-testid="class-marks">
    <h1>Class marks</h1>
    <p class="cm-sub">Read only.@if($exam) {{ $current?->name }} · {{ $exam['label'] }}@endif</p>

    @if($sections->isNotEmpty())
        <nav aria-label="Classes">
            <ul class="cm-classes">
                @foreach($sections as $s)
                    <li><a href="{{ route('admin.marks.classes', ['section' => $s->id]) }}" @if($current && $current->id === $s->id) aria-current="page" @endif>{{ $s->name }}</a></li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if($students->isEmpty())
        <div class="cm-empty" data-testid="class-marks-empty">No marks entered for this class yet.</div>
    @else
        <ul class="cm-grid" data-testid="class-marks-grid">
            @foreach($students as $student)
                <li class="cm-card" data-testid="class-marks-student">
                    <h2><span class="cm-code">{{ $student['code'] }}</span><span class="cm-name">{{ $student['first_name'] }}</span></h2>
                    @foreach($student['rows'] as $row)
                        <div class="cm-row">
                            <span>{{ $row['subject'] }}</span>
                            <span class="cm-mark"><b>{{ $row['mark'] }}</b><span>{{ $row['grade'] }}</span></span>
                        </div>
                    @endforeach
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
