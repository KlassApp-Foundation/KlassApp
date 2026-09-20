{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
    <div class="dashboard-shell dashboard-shell--teacher px-4 md:px-6 py-4">
        <div class="mb-6">
            @include('layouts.partials.page-header', [
                'title' => 'Events',
                'subtitle' => 'Browse school and class events and keep the calendar in view while reviewing the latest updates.',
            ])
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <show-event url="{{url('/')}}" count="{{ $count }}" no_of_events="{{ $subscription->plan->no_of_events }}" events="{{ $events }}"></show-event>
        </div>

        @php($eventList = json_decode($events, true) ?? [])
        <div class="mt-6 overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Start</th>
                        <th class="px-4 py-3">End</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($eventList as $event)
                        <tr class="hover:bg-emerald-50/40">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $event['title'] ?? 'Untitled event' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ isset($event['start']) ? \Carbon\Carbon::parse($event['start'])->format('d M Y, H:i') : '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ isset($event['end']) ? \Carbon\Carbon::parse($event['end'])->format('d M Y, H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-slate-500">No events have been published yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection