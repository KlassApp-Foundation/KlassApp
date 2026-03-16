{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
        <div class="flex flex-wrap lg:flex-row justify-between my-5">
            <h1 class="admin-h1  flex items-center">
                  <!--   <a href="{{ url('/admin/standardlinks') }}" title="Back" class="rounded-full bg-gray-100 p-2">
                       
                    </a> -->
                <span class="mx-3">Bus Pass</span>
            </h1>
            
            <div>
            <a href="{{ url('/admin/student/buspass/print/'.$standardLink->id) }}" class="text-xs">
                <div class="bg-blue-600 text-white px-3 py-1 rounded">
                    Print
                </div>
         
      </a>
  </div>
<!-- 
            <div class="relative flex items-center w-1/4 lg:justify-end">
                <div class="flex items-center">
                    
                </div>
            </div> -->
        </div>
        @include('partials.message')
        <div style="display: flex;flex-wrap: wrap;">
        @foreach($students as $user)

        <div class="card-width w-full lg:w-1/3 md:w-1/3" style="padding: 10px;"><!--  width: 33.33%; -->
            <div style="margin-top: 10px;box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);border-radius: 10px;background-size: cover;background-position: top;background-repeat: no-repeat;background-color: #fff;"><!--  background: url('/images/id-card-bg.png'); -->
            <!-- border:1px dashed #ccc;   -->
                <div style="border-radius: 8px;"><!--  background: #ffffff; -->
                    <div style=" background: url('/images/idcardbg.png');background-position: top;background-size: cover; border-top-right-radius: 10px;border-top-left-radius: 10px;">
                    <div style="padding: 15px 15px 10px;">
                       <table style="width: 100%;margin-left: auto;">
    <tbody>
      <tr>
        {{-- <td><img src="{{url('images/dm-logo.png')}}" style="height: 70px;"></td> --}}
        <!-- <td><label style="font-weight: 700;font-size: 12px;color: #166534;">Year : {{$academic->name}}</label></td> -->
        <td>
            <div style="">
            <h4 class="text-base" style="font-weight: 400;color: #fff;font-weight: 800;font-size: 13px;text-align: center;">{{ Auth::user()->school->name }}</h4>  
            <table style="width: 100%;">
          <tr>
            <!-- <td><p style="font-weight: 700;font-size: 12px;color: #fff;padding-top: 10px;">Year : {{$academic->name}}</p></td> -->
            <td style="text-align: center;"><p style="font-weight: 700;font-size: 11px;color: #fff;padding-top: 2px;">Office Phone : {{Auth::user()->school->phone}}</p></td>
        </tr>
         <tr>
            <!-- <td><p style="font-weight: 700;font-size: 12px;color: #fff;padding-top: 10px;">Year : {{$academic->name}}</p></td> -->
            <td style="text-align: center;"><p style="font-weight: 700;font-size: 18px;color: #fff;">BUS PASS  2022-2023</p></td>
        </tr>
        </table>
           </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
                </div>
                    <!-- <h2 class="text-sm" style="text-align: center;"><strong>Membership Card</strong></h2> -->
                    <div>
                        <!-- ***start*** -->
                        <div>    
                            <!-- <p style="width:35%;padding-top: 10px;padding-bottom: 10px;"><strong>User Detail :</strong></p> -->
                            <div style="padding: 10px;">
                                <div style="display: flex;font-size: 11px;padding: 10px 13px;" class="visitor-log">
                                   
                                    <div style="width: 80%;">
                                     <table style="width: 100%;">
                                        <tr class="visitor-log" style="vertical-align: baseline;">
                                           <td style="width: 23%;"> <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Name :</b></p></td>
                                          <td>  <p style="padding-top: 4px;padding-bottom: 4px;"><span style="font-size: 13px;font-weight: 800;">{{ ucfirst($user->FullName) }}</span></p></td>
                                        </tr>
                                      
                                        <tr class="visitor-log" style="vertical-align: baseline;">
                                           <td style="width: 23%;"> <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Class : </b></p></td>
                                           <td> <p style="padding-top: 4px;padding-bottom: 4px;"><span>{{optional(optional($user->studentAcademicLatest)->standardLink)->StandardSection}}</span></p><!--  {{ $user->userprofile->address }} -->
                                            </td>
                                        </tr>
                                        <tr style="vertical-align: baseline;">
                                          <td style="width: 23%;"> <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Address : </b></p></td>
                                           <td> <p style="padding-top: 4px;padding-bottom: 4px;"><span>{{$user->userprofile->address}},<br>
                                            {{$user->userprofile->city->name}},
                                            {{$user->userprofile->pincode}}</span></p><!--  {{ $user->userprofile->address }} -->
                                            </td>
                                           
                                        </tr>
                                         <tr style="vertical-align: baseline;">
                                          <td style="width: 23%;"> <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Mobile : </b></p></td>
                                           <td> <p style="padding-top: 4px;padding-bottom: 4px;"><span>{{$user->mobile_no}}</span></p>
                                            </td>
                                           
                                        </tr>
                                        <tr>
                                          <td style="width: 23%;"> <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Boarding Point : </b></p></td>
                                           <td> <p style="padding-top: 4px;padding-bottom: 4px;"><span>Nilaiyir, Temple Stop</span></p>
                                            </td>
                                          
                                        </tr>
                                        
                                        
                               </table>
                                        </div>
                                    
                                  <div style="width:20%;">
                                    <table style="width: 100%;">
                                      <tbody>
                                      <tr>
                                      
                                    <td>
                                    <span><img src="{{ $user->userprofile->AvatarPath }}" style="width: 75px;height: 75px;border-radius: 10px;margin-left: auto;"></span>
                                  </td>
                                  </tr>
                                </tbody>
                                  </table>
                                </div>
                                       
                                   
                                    </div> 
                                   
                                </div>     


                                <div style="font-size: 11px;padding: 10px;padding-top: 0;padding-right: 25px;padding-left: 25px;overflow-x:auto;">
                                  <table class="border w-full buspass-table" style="overflow-x:auto;">
                                    <thead style="background-color: #fff;">
                                      <tr>
                                        <th class="border-r p-1">June</th>
                                        <th class="border-r p-1">July</th>
                                        <th class="border-r p-1">Aug</th>
                                        <th class="border-r p-1">Sep</th>
                                        <th class="border-r p-1">Oct</th>
                                        <th class="border-r p-1">Nov</th>
                                        <th class="border-r p-1">Dec</th>
                                        <th class="border-r p-1">Jan </th>
                                        <th class="border-r p-1">Feb</th>
                                        <th class="border-r p-1">March</th>
                                        <th class="border-r p-1">April</th>
                                        <th class="border-r p-1">May</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr>
                                        <td colspan="13" class="border-t border-b text-center pt-2 pb-1">Fair Paid on with Sign</td>
                                      </tr>
                                      <tr>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                        <td class="border-r p-1 h-8"></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                               
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    <!-- ***end*** -->
                </div> 
            </div>
             @endforeach
           </div>
       
         </div>
   
@endsection