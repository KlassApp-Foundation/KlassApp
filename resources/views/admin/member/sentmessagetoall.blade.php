{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <section class="section">
        <div class="w-full">
            <div class="flex items-center justify-between">
                <div class="">
                    <h1 class="admin-h1 my-3">Sent Messages</h1>
                </div>
            </div>
            <div class="p-4 bg-white shadow-lg">
                <div class="w-full">
                    @include('partials.message')
                </div>
                <form action="{{ url('/admin/sentmessages') }}" enctype="multipart form-data">
                    <div class=" flex flex-wrap items-center mb-3">
                        <select class="tw-form-control text-xs" name="type">
                            <option value="">Filter By Type</option>
                            <option value="alumni" {{ \request()->query('type')== 'alumni' ? 'selected' : '' }}>Alumni</option>
                            <option value="parent" {{ \request()->query('type')== 'parent' ? 'selected' : '' }}>Parent</option>
                            <option value="teacher" {{ \request()->query('type')== 'teacher' ? 'selected' : '' }}>Teacher</option>
                        </select>
                        <button value="Submit" type="submit" class="blue-bg text-sm text-white px-2 py-1 rounded mx-1">Submit</button>
                        <a href="{{ url('/admin/sentmessages') }}" class="bg-gray-500 text-sm text-white px-2 py-1 rounded mx-1">Reset</a>
                    </div>
                </form>
                <div class="custom-table overflow-auto">
                    <table class="table table-bordered borderTable">
                        <thead class="bg-grey-light">
                            <tr>
                                <th>To</th>
                                <th>Type</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Sent On</th>
                                <th>Status</th>   
                            </tr>
                        </thead>
                        @if(count($messages) != 0)
                            @foreach($messages as $message)
                                <tbody>
                                    <td>
                                        @if($message->user->usergroup_id == 5)
                                            <a href="{{ url('/admin/teacher/show/'.$message->user->name) }}">
                                                {{ ucfirst($message->user->FullName) }}
                                            </a>
                                        @elseif($message->user->usergroup_id == 7)
                                            <a href="{{ url('/admin/parent/show/'.$message->user->name) }}">
                                                {{ ucfirst($message->user->FullName) }}
                                            </a>
                                        @elseif($message->user->usergroup_id == 9)
                                            <a href="{{ url('/admin/alumni/show/'.$message->user->name) }}">
                                                {{ $message->user->userprofile->firstname == null ? strtoupper(str_replace('.',' ',$message->user->name)):ucfirst($message->user->FullName) }}
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        @if($message->user->usergroup_id == 5)
                                            Teacher
                                        @elseif($message->user->usergroup_id == 7)
                                            Parent
                                        @elseif($message->user->usergroup_id == 9)
                                            Alumni
                                        @endif
                                    </td>
                                    <td>{{ $message->subject }}</td>          
                                    <td>{{ $message->message }}</td>         
                                    <td>
                                        @if($message->fired_at != '')
                                            {{ date('d-m-Y H:i:s',strtotime($message->fired_at)) }}
                                        @else
                                            {{ date('d-m-Y H:i:s',strtotime($message->executed_at)) }}
                                        @endif
                                    </td>
                                    <td>{{ $message->status }}</td>
                                </tbody>
                            @endforeach
                        @else
                            <tbody>
                                <td colspan="6" style="text-align: center;"> No Records found</td>
                            </tbody>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{ $messages->withQueryString()->links() }}
@endsection