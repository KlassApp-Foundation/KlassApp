{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="w-1/2 lg:mr-8 md:mr-8">
   <div>
      <h1 class="admin-h1 mb-5 flex items-center">
         <a href="{{ url('/admin/report/index') }}" title="Back" class="rounded-full bg-gray-300 p-2">
            <img src="{{asset('uploads/icons/back.svg')}}" class="w-3 h-3">
         </a>
         <span class="mx-3">Subscription Details</span>
      </h1>
   </div>
   <table class="w-full">
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Transaction ID   : </td>
         <td class="py-3 px-2">{{ $subscriptions->payment_details['txnid'] }} </td>
      </tr>
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Amount  :  </td>
         <td class="py-3 px-2">{{ $subscriptions->payment_details['amount'] }} </td>
      </tr>
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">First Name  :  </td>
         <td class="py-3 px-2">{{ $subscriptions->payment_details['firstname'] }} </td>
      </tr> 
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Email ID : </td>
         <td class="py-3 px-2">{{ $subscriptions->payment_details['email'] }}</td>
      </tr>
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Phone Number : </td>
         <td class="py-3 px-2">{{ $subscriptions->payment_details['phone'] }}</td>
      </tr>
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Product Information  :  </td>
         <td class="py-3 px-2">{{ $subscriptions->payment_details['productinfo'] }} </td>
      </tr>
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Status :  </td>
         <td class="py-3 px-2">{{ $subscriptions->payment_details['status'] }} </td>
      </tr>
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Payment Mode  :  </td>
         <td class="py-3 px-2">{{ $subscriptions->payment_details['mode'] }} </td>
      </tr> 
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Error Message : </td>
         <td class="py-3 px-2">{{ $subscriptions->payment_details['error_Message'] }}</td>
      </tr>
      <tr class="border-t-2 border-b-2">
         <td class="py-3 px-2">Added On : </td>
         <td class="py-3 px-2">
            @if($subscriptions->payment_details['addedon']=="")
               --
            @else
               {{ date('d-m-Y H:i:s',strtotime($subscriptions->payment_details['addedon'])) }}
            @endif
         </td>
      </tr>
   </table>
</div>
@endsection