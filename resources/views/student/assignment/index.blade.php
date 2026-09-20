{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.student.layout')

@section('content')
    @if($hasAssignments)
        <assignment-list-student url="{{ url('/') }}" type="student" scope="{{ $standardLink_id }}" hidecolumns="true" searchquery="{{$query}}"></assignment-list-student>
    @else
        <div class="ds-empty-state mt-6" data-testid="student-assignments-empty">
            <div class="ds-empty-state-icon" aria-hidden="true">📄</div>
            <p class="ds-empty-state-title">No assignments yet</p>
            <p class="ds-empty-state-desc">
                Assignments set by your teachers will appear here, with their due dates and attachments.
            </p>
        </div>
    @endif
@endsection
