{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')

<div class="flex flex-row justify-between">
   <div class="w-1/2 ">
   <h1 class="admin-h1 mb-3 flex items-center">
      <a  href="{{ url('/admin/reports') }}" class="rounded-full bg-gray-300 p-2" title="Back"><img src="{{asset('uploads/icons/back.svg')}}" class="w-3 h-3"></a>
      <span class="mx-3">Events Reports</span>
      </h1>
   </div>
</div>

<div class="relative">
   <div class="flex flex-row justify-between">
      <table class="w-full">
         <thead class="bg-grey-light">
            <tr class="border-t-2 border-b-2">
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Name</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">User Name</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Type</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Status</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Sent Date</th>
            </tr>
         </thead>
         @if(count($event_reports)==0)
            <tr class="border-t-2 border-b-2">
               <td colspan="5">No records found</td>
            </tr>
         @else
            <tbody class="bg-grey-light">
               @foreach($event_reports as $events)
                  <tr class="border-t-2 border-b-2">    
                     <td class="py-3 px-2">{{ $events->events->title }}days</td>
                     <td class="py-3 px-2">
                        @if( ($events->via == 'mail') || ($events->via == 'notification') )
                           {{ $events->user->email }}
                        @else
                           {{ $events->userSms->mobile_no }}
                        @endif
                     </td>
                     <td class="py-3 px-2">{{ $events->via }}</td>
                     <td class="py-3 px-2">{{ $events->queue_status }}</td>
                     <td class="py-3 px-2">{{ date('d-m-Y',strtotime($events->executed_at)) }}</td>
                  </tr>
               @endforeach
            </tbody>
         @endif
      </table>      
   </div>
</div>

@endsection