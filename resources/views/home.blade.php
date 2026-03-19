{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <div class="py-2">You are logged in!</div>
                    <div>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>
                    </div>
                    <div class="py-2">You belongs to usergroup <strong>{{ auth()->user()->usergroup->name }}</span></strong></div>
                    <div class="py-2">School Name <br/>
                        <div class="text-2xl capitalize">
                            <strong>{{ auth()->user()->school->name }} </strong>
                        </div>
                    </div>
                    <div>School Address </div>
                    <div class="py-2"><strong> {!! auth()->user()->school->fulladdress() !!}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
