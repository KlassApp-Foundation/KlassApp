{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
   <div>
   <!-- heading start -->
   	<div class="flex items-center justify-between">
   		<h1 class="admin-h1 my-3">Payroll {{$payroll->payrollno}}</h1>
   		<div class="flex items-center">
   			
   			<div class="bg-blue-500 rounded px-2 py-1 ">
   			<a href="{{url('/teacher/payroll/payslip')}}" class="flex items-center text-xs text-white">
   				<svg class="w-4 h-4 fill-current text-white" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
	<g>
		<path d="M492,236H144.262c-11.046,0-20,8.954-20,20s8.954,20,20,20H492c11.046,0,20-8.954,20-20C512,244.954,503.046,236,492,236z
			"/>
	</g>
</g>
<g>
	<g>
		<path d="M492,86H144.262c-11.046,0-20,8.954-20,20s8.954,20,20,20H492c11.046,0,20-8.954,20-20S503.046,86,492,86z"/>
	</g>
</g>
<g>
	<g>
		<path d="M492,386H144.262c-11.046,0-20,8.954-20,20c0,11.046,8.954,20,20,20H492c11.046,0,20-8.954,20-20
			C512,394.954,503.046,386,492,386z"/>
	</g>
</g>
<g>
	<g>
		<circle cx="27" cy="106" r="27"/>
	</g>
</g>
<g>
	<g>
		<circle cx="27" cy="256" r="27"/>
	</g>
</g>
<g>
	<g>
		<circle cx="27" cy="406" r="27"/>
	</g>
</g>
</svg>
<span class="mx-1">List Payroll</span>
</a>
</div>
   			
<div class="bg-blue-500 rounded px-2 py-1 ml-2">
<a href="{{url('/teacher/payroll/payslip/'.$payroll->id.'/view')}}" target="blank"><svg class="w-4 h-4 fill-current text-white" id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m437 129h-14v-54c0-41.355-33.645-75-75-75h-184c-41.355 0-75 33.645-75 75v54h-14c-41.355 0-75 33.645-75 75v120c0 41.355 33.645 75 75 75h14v68c0 24.813 20.187 45 45 45h244c24.813 0 45-20.187 45-45v-68h14c41.355 0 75-33.645 75-75v-120c0-41.355-33.645-75-75-75zm-318-54c0-24.813 20.187-45 45-45h184c24.813 0 45 20.187 45 45v54h-274zm274 392c0 8.271-6.729 15-15 15h-244c-8.271 0-15-6.729-15-15v-148h274zm89-143c0 24.813-20.187 45-45 45h-14v-50h9c8.284 0 15-6.716 15-15s-6.716-15-15-15h-352c-8.284 0-15 6.716-15 15s6.716 15 15 15h9v50h-14c-24.813 0-45-20.187-45-45v-120c0-24.813 20.187-45 45-45h362c24.813 0 45 20.187 45 45z"/><path d="m296 353h-80c-8.284 0-15 6.716-15 15s6.716 15 15 15h80c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m296 417h-80c-8.284 0-15 6.716-15 15s6.716 15 15 15h80c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m128 193h-48c-8.284 0-15 6.716-15 15s6.716 15 15 15h48c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/></g></svg></a>
</div>   			
   		</div>
   	</div>
   	<!-- heading end -->

   	<!-- start -->
   	<div class="bg-white shadow">
   	<div class="flex flex-wrap">
   	<!-- start -->
   		<div class="w-full lg:w-1/4 md:w-1/4 border-r">
   			<ul class="list-reset text-xs">
   				<!-- <li class="flex items-center justify-between py-2 px-2 border-b">
   					<p>Present (P)</p>
   					<p>3</p>
   				</li>
   				<li class="flex items-center justify-between py-2 px-2 border-b">
   					<p>Holiday (H)</p>
   					<p>9</p>
   				</li>
   				<li class="flex items-center justify-between py-2 px-2 border-b">
   					<p>Absent (A)</p>
   					<p>4</p>
   				</li>
   				<li class="flex items-center justify-between py-2 px-2 border-b">
   					<p>Half Day (HD)</p>
   					<p>1</p>
   				</li>
   				<li class="flex items-center justify-between py-2 px-2 border-b">
   					<p>Overtime (O)</p>
   					<p>0</p>
   				</li>
   				<li class="flex items-center justify-between py-2 px-2 border-b">
   					<p>Early Leaving (EL)</p>
   					<p>0</p>
   				</li>
   				<li class="flex items-center justify-between py-2 px-2 border-b">
   					<p>Present (Late) (LT)</p>
   					<p>0</p>
   				</li> -->
   				<li class="flex items-center justify-between py-2 px-2 border-b">
   					<p>Leave (L)</p>
   					<p>{{$payroll->getLeaveDays($payroll->staff_id,$payroll->start_date,$payroll->end_date)}}</p>
   				</li>
   				<li class="flex items-center justify-between py-2 px-2 border-b">
   					<p>Created at</p>
   					<p>{{date('d-m-Y H:s',strtotime($payroll->created_at))}}</p>
   				</li>
   				<li class="flex items-center justify-between py-2 px-2 border-b">
   					<p>Last Updated at</p>
   					<p>{{date('d-m-Y H:s',strtotime($payroll->updated_at))}}</p>
   				</li>
   			</ul>
   		</div>
   	<!-- end -->
   	<!-- start -->
   		<div class="w-full lg:w-3/4 md:w-3/4">
   			<div class="flex flex-wrap border-b">
   				<div class="w-full lg:w-1/2 md:w-1/2 border-r px-5 py-5">
   					<p class="text-sm">{{$payroll->user->userprofile->firstname}} {{$payroll->user->userprofile->lastname}}</p>
   					<p class="text-sm">{{ucfirst($payroll->user->teacherprofile[0][designation])}}</p>
   				</div>
   				<div class="w-full lg:w-1/2 md:w-1/2 px-5 py-5">
   					<p class="text-sm">{{date('d-m-Y',strtotime($payroll->start_date))}} to {{date('d-m-Y',strtotime($payroll->end_date))}}</p>
                  @if($payroll->status=='paid')
   					<p class="bg-green-500 text-white rounded inline-block px-2 text-xs">Paid</p>
                  @else
                  <p class="bg-red-500 text-white rounded inline-block px-2 text-xs">Unpaid</p>
                  @endif
   				</div>
   			</div>
   			<!-- ** -->
   			<div>
   				<div class="flex flex-wrap">
   					<div class="w-full lg:w-1/2 md:w-1/2 pt-4">
   						<p class="text-xs font-semibold text-gray-600 px-5 py-2">Earning</p>
   						<ul class="list-reset border-r text-xs border-t ml-3">
                         @foreach($payroll->payslipitems as $payslip)
              @if($payslip->salaryitem->templateitem->payrollitem->type=='earning')
   							<li class="flex items-center justify-between border-b py-2 px-2"> 
   								<p>{{$payslip->salaryitem->templateitem->payrollitem->name}}</p>
   								<p>&#8377; {{$payslip->amount}}</p>
   							</li>
                        @endif
               @endforeach 
   							<!-- <li class="flex items-center justify-between border-b py-2 px-2"> 
   								<p>Dearness Allowance</p>
   								<p>$ 400.00</p>
   							</li>
   							<li class="flex items-center justify-between border-b py-2 px-2"> 
   								<p>Transport Allowance</p>
   								<p>$ 200.00</p>
   							</li>
   							<li class="flex items-center justify-between py-2 px-2"> 
   								<p>Mobile Allowance</p>
   								<p>$ 100.00</p>
   							</li> -->
   						</ul>
   					</div>
   					<div class="w-full lg:w-1/2 md:w-1/2 pt-4">
   						<p class="text-xs font-semibold text-gray-600 px-5 py-2">Deduction</p>
   						<ul class="list-reset border-r text-xs border-t px-3">
   							@foreach($payroll->payslipitems as $payslip)
              @if($payslip->salaryitem->templateitem->payrollitem->type=='deduction')
                        <li class="flex items-center justify-between border-b py-2 px-2"> 
                           <p>{{$payslip->salaryitem->templateitem->payrollitem->name}}</p>
                           <p>&#8377; {{$payslip->amount}}</p>
                        </li>
                        @endif
               @endforeach 
   						<!-- 	<li class="flex items-center justify-between py-2 px-2"> 
   								<p>Security Deposit</p>
   								<p>$ 100.00</p>
   							</li> -->
   							
   						</ul>
   					</div>
   				</div>
   				<div class="flex flex-wrap">
   					<div class="w-full lg:w-1/2 px-5 text-xs flex items-center justify-between border-t border-b py-2">
   						<p>Earning Salary</p>
   						<p>&#8377; {{$payroll->totalearnings()}}</p>
   						</div>
   						<div class="w-full lg:w-1/2 text-xs px-5 flex items-center justify-between border-t border-b py-2">
   						<p>Deduction Salary</p>
   						<p>&#8377; {{$payroll->totaldeductions()}}</p>
   						</div> 
   			    </div>
   			    <div class="flex flex-wrap">
   					<div class="w-full lg:w-1/2 px-5 text-xs py-2">
   						<p class="font-semibold text-gray-600">Net Salary</p>
   					</div>
   					<div class="w-full lg:w-1/2 text-xs px-5 py-2">
   					<p class="text-right font-semibold text-gray-600">&#8377; {{$payroll->totalsalary()}}</p>
   					</div> 
   			    </div>
@if($payroll->status=='paid')
   			    <div class="px-3 py-2">
   			    	<h6>Payroll Transaction</h6>
   			    	<table class="text-xs w-full my-2">
   			    		<thead class="border-b">
   			    			<th class="text-left py-1">Voucher Number</th>
   			    			<th class="text-left py-1">Amount</th>
   			    			<th class="text-left py-1">Account</th>
   			    			<th class="text-left py-1">Payment Method</th>
   			    			<th class="text-left py-1">Date of Transaction</th>
   			    		</thead>
   			    		<tbody>
   			    			<tr>
   			    				<td class="py-1">{{$payroll->transaction->transaction_no}}</td>
   			    				<td class="py-1">&#8377; {{$payroll->transaction->amount}}</td>
   			    				<td class="py-1">{{$payroll->transaction->account->name}} </td>
   			    				<td class="py-1">{{$payroll->transaction->payment_method}}</td>
   			    				<td class="py-1">
                              {{date('d-m-Y',strtotime($payroll->transaction->transaction_date))}}</td>
   			    			</tr>
   			    		</tbody>
   			    	</table>
   			    </div>
   @endif             
   			</div>
   			<!-- ** -->
   		</div>
   	<!-- end -->
   	</div>
   	</div>
   	<!-- end -->
   </div>
 
 @endsection