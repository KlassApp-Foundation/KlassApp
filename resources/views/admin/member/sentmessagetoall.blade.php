{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Sent Messages</h1>
            <p class="dashboard-subtitle">View all messages sent to recipients across the school.</p>
        </div>
    </div>

    @include('partials.message')

    {{-- Filter --}}
    <div class="bg-white rounded-lg shadow border border-gray-100 p-4 mb-6">
        <form action="{{ url('/admin/sentmessages') }}" enctype="multipart form-data">
            <div class="flex flex-wrap items-center gap-2">
                <select class="text-sm border rounded px-3 py-2 w-48" name="type">
                    <option value="">Filter By Type</option>
                    <option value="alumni" {{ \request()->query('type')== 'alumni' ? 'selected' : '' }}>Alumni</option>
                    <option value="parent" {{ \request()->query('type')== 'parent' ? 'selected' : '' }}>Parent</option>
                    <option value="teacher" {{ \request()->query('type')== 'teacher' ? 'selected' : '' }}>Teacher</option>
                </select>
                <button type="submit" class="bg-blue-600 text-sm text-white px-3 py-2 rounded">Submit</button>
                <a href="{{ url('/admin/sentmessages') }}" class="bg-gray-500 text-sm text-white px-3 py-2 rounded">Reset</a>
            </div>
        </form>
    </div>

    {{-- Messages table --}}
    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-5 py-3 font-semibold">To</th>
                        <th class="px-5 py-3 font-semibold">Type</th>
                        <th class="px-5 py-3 font-semibold">Subject</th>
                        <th class="px-5 py-3 font-semibold">Message</th>
                        <th class="px-5 py-3 font-semibold">Sent On</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @if(count($messages) != 0)
                        @foreach($messages as $message)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 text-gray-700">
                                    @if($message->user->usergroup_id == 5)
                                        <a href="{{ url('/admin/teacher/show/'.$message->user->name) }}" class="text-blue-600 hover:underline">
                                            {{ ucfirst($message->user->FullName) }}
                                        </a>
                                    @elseif($message->user->usergroup_id == 7)
                                        <a href="{{ url('/admin/parent/show/'.$message->user->name) }}" class="text-blue-600 hover:underline">
                                            {{ ucfirst($message->user->FullName) }}
                                        </a>
                                    @elseif($message->user->usergroup_id == 9)
                                        <a href="{{ url('/admin/alumni/show/'.$message->user->name) }}" class="text-blue-600 hover:underline">
                                            {{ $message->user->userprofile->firstname == null ? strtoupper(str_replace('.',' ',$message->user->name)):ucfirst($message->user->FullName) }}
                                        </a>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-500">
                                    @if($message->user->usergroup_id == 5)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">Teacher</span>
                                    @elseif($message->user->usergroup_id == 7)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">Parent</span>
                                    @elseif($message->user->usergroup_id == 9)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Alumni</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-700 font-medium">{{ $message->subject }}</td>
                                <td class="px-5 py-4 text-gray-600 max-w-xs truncate">{{ $message->message }}</td>
                                <td class="px-5 py-4 text-gray-400 text-xs whitespace-nowrap">
                                    @if($message->fired_at != '')
                                        {{ date('d-m-Y H:i:s',strtotime($message->fired_at)) }}
                                    @else
                                        {{ date('d-m-Y H:i:s',strtotime($message->executed_at)) }}
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                        {{ $message->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-400 text-sm">No records found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if(method_exists($messages, 'hasPages') && $messages->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $messages->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection