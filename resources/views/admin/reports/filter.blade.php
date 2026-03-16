{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')

<div class="relative">
   <div class="flex flex-row justify-between">
      <table class="w-full">
      <caption><h1 class="admin-h1 mb-6">Subscription History</h1></caption>
         <thead class="bg-grey-light">
            <tr class="border-t-2 border-b-2">
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Plan</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Amount</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Status</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Subscription Start Date</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Subscription End Date</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Actions</th>
            </tr>
         </thead>
         <tbody class="bg-grey-light">
            @foreach($subscriptions as $subscription)
               <tr class="border-t-2 border-b-2">    
                  <td class="py-3 px-2">{{ $subscription->plan->cycle }}days</td>
                  <td class="py-3 px-2">{{ $subscription->plan->amount }}</td>
                  <td class="py-3 px-2">{{ ucwords($subscription->status) }}</td>
                  <td class="py-3 px-2">
                     @if($subscription->payment_details['addedon']=="")
                        --
                     @else
                        {{ date('d-m-Y',strtotime($subscription->payment_details['addedon'])) }}
                     @endif
                  </td>
                  <td class="py-3 px-2">{{ date('d-m-Y',strtotime($subscription->end_date)) }}</td>
                  <td class="py-3 px-2"><a href="{{url('/admin/report/show/'.$subscription->id)}}">View Details</a></td>
               </tr>
            @endforeach
         </tbody>
      </table>      
   </div>
</div>

@endsection