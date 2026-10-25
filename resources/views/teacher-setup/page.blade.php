{{-- SPDX-License-Identifier: MIT --}}
@extends(auth()->user()->usergroup_id === 5 ? 'layouts.teacher.layout' : 'layouts.admin.layout')

@section('content')
    @livewire('teacher.setup.teacher-class-setup')
@endsection
