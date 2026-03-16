{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="relative">
        <div class="flex flex-wrap lg:flex-row justify-between my-5">
            <h1 class="admin-h1  flex items-center">
                  <!--   <a href="{{ url('/admin/standardlinks') }}" title="Back" class="rounded-full bg-gray-100 p-2">
                       
                    </a> -->
                <span class="mx-3">Id card</span>
            </h1>
            
            <div>
            <a href="{{ url('/admin/standardLink/id-card-print/'.$standardLink->id) }}" class="text-xs">
                <div class="bg-blue-600 text-white px-3 py-1 rounded">
                    Print
                </div>
          <!-- <div class="bg-gray-200 rounded-full w-8 h-8 lg:w-10 md:h-10 md:w-10 md:h-10 flex items-center justify-center mx-auto hover:bg-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" id="Capa_1" enable-background="new 0 0 512 512" height="512px" viewBox="0 0 512 512" width="512px" class="w-4 h-4 lg:w-5 lg:h-5 md:w-5 md:h-5 fill-current text-gray-600"><g><g><path d="m74.791 114.523c26.424.368 67.758 5.62 114.616 28.55 7.392 3.619 16.401.608 20.066-6.88 3.641-7.441.561-16.425-6.881-20.067-51.852-25.374-97.892-31.189-127.384-31.6-.071-.001-.143-.001-.213-.001-8.187 0-14.88 6.579-14.994 14.791-.115 8.283 6.507 15.091 14.79 15.207z" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m202.593 176.126c-51.852-25.374-97.892-31.189-127.384-31.6-.071-.001-.143-.001-.213-.001-8.187 0-14.88 6.579-14.994 14.791-.116 8.284 6.506 15.092 14.789 15.208 26.424.368 67.758 5.62 114.616 28.55 7.392 3.619 16.401.608 20.066-6.88 3.641-7.443.561-16.427-6.88-20.068z" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m202.593 236.126c-51.852-25.374-97.892-31.189-127.384-31.6-.071-.001-.143-.001-.213-.001-8.187 0-14.88 6.579-14.994 14.791-.116 8.284 6.506 15.092 14.789 15.208 26.424.368 67.758 5.62 114.616 28.55 7.392 3.619 16.401.608 20.066-6.88 3.641-7.443.561-16.427-6.88-20.068z" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m309.407 116.126c-7.441 3.641-10.521 12.625-6.881 20.067 2.604 5.32 7.937 8.41 13.484 8.41 2.213 0 4.461-.492 6.582-1.53 37.64-18.419 75.865-28.024 113.616-28.55 8.283-.115 14.905-6.924 14.789-15.208-.115-8.283-6.876-14.901-15.207-14.79-42.213.589-84.735 11.221-126.383 31.601z" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path><path d="m500.638 7.584c-18.59-4.713-37.148-7.584-60.591-7.584-.016 0-.035 0-.05 0-43.627.009-110.281 11.25-183.997 63.569-73.272-52-139.55-63.382-182.937-63.566-24.136-.104-43.055 2.852-61.7 7.581-6.678 1.67-11.363 7.669-11.363 14.552v294.5c0 4.619 2.128 8.98 5.769 11.823 6.635 5.182 13.606 2.463 13.981 2.448 41.952-10.63 127.258-17.266 227.25 57.729 2.667 2 5.833 3 9 3s6.333-1 9-3c31.188-23.391 63.401-40.556 96-51.235v159.599c0 8.284 6.716 15 15 15h60c8.284 0 15-6.716 15-15v-172.236c21.132 1.097 36.088 4.848 41.675 6.252.277.008 7.061 2.515 13.557-2.557 3.641-2.843 5.769-7.204 5.769-11.823v-294.5c-.001-6.883-4.685-12.882-11.363-14.552zm-470.638 290.414v-263.956c9.661-1.955 24.286-4.124 42.937-4.04 39.466.168 100.214 10.809 168.063 59.726v258.166c-45.801-29.614-93.332-47.095-141.641-52.035-29.797-3.047-53.622-.62-69.359 2.139zm385.485-56.998h-18.969l9.484-29.8zm5.515 241h-30v-211h30zm61-183.931c-8.266-1.429-18.763-2.783-31-3.342v-38.667c.009-1.64-.255-3.321-.813-4.942l-29.894-93.926c-2.22-6.938-9.127-11.275-16.273-10.32-31.752 4.227-63.585 14.069-94.613 29.253-7.441 3.641-10.521 12.625-6.881 20.066 2.604 5.32 7.937 8.41 13.484 8.41 2.213 0 4.461-.492 6.582-1.53 20.297-9.932 40.912-17.319 61.544-22.097l-22.324 70.143c-.521 1.513-.806 3.135-.813 4.823v50.053c-30.537 8.934-60.681 22.965-90 41.91v-258.175c68.249-49.206 129.329-59.72 169.004-59.728h.043c18.182 0 32.464 2.124 41.954 4.043z" data-original="#000000" data-old_color="#000000" fill="" class="active-path"></path></g></g></svg>
          </div>
        <span class="hover:font-semibold hidden lg:block md:block">print<span> -->
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
                    <div style=" background: url('/images/idcardbg.png');background-position: bottom;background-size: cover; border-top-right-radius: 10px;border-top-left-radius: 10px;">
                    <div style="padding: 15px;padding-bottom: 30px;">
                       <table style="width: 100%;margin-left: auto;">
    <tbody>
      <tr>
        <td><img src="{{url('images/dm-logo.png')}}" style="height: 70px;"></td>
        <!-- <td><label style="font-weight: 700;font-size: 12px;color: #166534;">Year : {{$academic->name}}</label></td> -->
        <td>
            <div style="padding-left: 15px;">
            <h4 class="text-base" style="font-weight: 500;color: #fff;font-weight: 800;font-size: 19px;">{{ Auth::user()->school->name }}</h4>  
            <table style="width: 100%;">
<tr>
            <td><p style="font-weight: 700;font-size: 12px;color: #fff;padding-top: 10px;">Year : {{$academic->name}}</p></td>
            <td style="text-align: right;"><p style="font-weight: 700;font-size: 12px;color: #fff;padding-top: 10px;">Cell : {{Auth::user()->school->phone}}</p></td>
        </tr>
        </table>
           </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
                    <!-- <div class="" style="text-align: center;padding:10px;border-top-left-radius:8px;border-top-right-radius:8px;">  <!-- background-color: #bee3f8;                     <h4 class="text-base mx-2" style="text-align: center;font-weight: 500;color: rgb(190, 18, 60);font-weight: 800;font-size: 17px;">{{ Auth::user()->school->name }}</h4>  
                        
                        
                    </div>    --> 
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
                                           <td style="width: 25%;"> <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Name :</b></p></td>
                                          <td>  <p style="padding-top: 4px;padding-bottom: 4px;"><span style="font-size: 15px;font-weight: 800;">{{ ucfirst($user->FullName) }}</span></p></td>
                                        </tr>
                                       <!--  <tr class="visitor-log" style="vertical-align: baseline;">
                                         <td colspan="2">
                                            <table style="width: 100%;">
                                                <tbody>
                                            <tr>
                                            <td style="width: 50%;" >
                                                <table style="width: 100%;">
                                                    <tr>
                                                    <td style="width: 50%;"><p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>ID :</b></p></td>
                                                    <td><p style="padding-top: 4px;padding-bottom: 4px;font-weight: 700;">132923</p></td>
                                                </tr>
                                                </table>
                                            </td>
                                            <td  style="text-align: right;width: 50%;padding-right: 10px;">
                                              <table style="width: 100%;">
                                                    <tr>
                                                    <td><p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Class :</b></p></td>
                                                    <td><p style="padding-top: 4px;padding-bottom: 4px;font-weight: 700;">1-B</p></td>
                                                </tr>
                                                </table>
                                            </td>
                                                </tr>
                                            </tbody>
                                            </table>
                                          </td>
                                        </tr> -->
                                        <tr class="visitor-log" style="vertical-align: baseline;">
                                           <td style="width: 25%;"> <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Class : </b></p></td>
                                           <td> <p style="padding-top: 4px;padding-bottom: 4px;"><span>{{optional(optional($user->studentAcademicLatest)->standardLink)->StandardSection}}</span></p><!--  {{ $user->userprofile->address }} -->
                                            </td>
                                        </tr>

                                        <tr class="visitor-log" style="vertical-align: baseline;">
                                           <td style="width: 25%;"> <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Address : </b></p></td>
                                           <td> <p style="padding-top: 4px;padding-bottom: 4px;padding-right: 4px;word-break: break-all;"><span>{{$user->userprofile->address}},<br>
                                            {{$user->userprofile->city->name}},
                                            {{$user->userprofile->pincode}}</span></p><!--  {{ $user->userprofile->address }} -->
                                            </td>
                                        </tr>
                                        <tr style="vertical-align: baseline;">
                                            <td style="width: 25%;"> <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Parent name :</b></p></td>
                                         <td>
                                            <p style="padding-top: 4px;padding-bottom: 4px;">
                                            @if(count($user->parents)>0)
                                             {{$user->parents[0]['userParent']['userprofile']['firstname'].' '.$user->parents[0]['userParent']['userprofile']['lastname'].'('.ucfirst($user->parents[0]['userParent']->getParentDetails()['relation']).')'}}
                                            @endif
                                          </p>
                                        </td>
                                        </tr>
                               </table>
                                        </div>
                                    
                                  <div style="width:20%;">
                                    <p style="padding-bottom: 4px;color: #000;text-align: center;font-size: 13px;"><b>ID : </b> {{optional($user->studentAcademicLatest)->roll_number}}</p>
                                    <span><img src="{{ $user->userprofile->AvatarPath }}" style="width: 100px;height: 100px;border-radius: 10px;"></span>
                                </div>
                                       
                                   
                                    </div> 
                                   
                                </div>     


                                <div style="font-size: 11px;padding: 10px;padding-top: 0;padding-right: 25px;padding-left: 25px;">
                                    <div style="padding: 5px 13px;border-top: 1px dashed #ccc; border-bottom: 1px dashed #ccc;"> 
                                    <table style="width: 100%;">
                                        <tbody>
                                            <tr>
                                                
                                                <td>
                                                    <div style="text-align: center;">
                                                    <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>D.O.B</b></p>
                                                    <p style="padding-top: 2px;padding-bottom: 2px;"><span >
                                                     {{ optional($user->userprofile)->date_of_birth ? \Carbon\Carbon::parse($user->userprofile->date_of_birth)->format('d-m-Y') : '' }}
                                                    </span></p>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div  style="border-right: 1px dashed #ccc;border-left: 1px dashed #ccc;text-align: center;">
                                                    <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Mobile :</b></p>
                                                    <p style="padding-top: 2px;padding-bottom: 2px;"><span >{{ $user->mobile_no }}</span></p>
                                                </div>
                                                </td>
                                                <td>
                                                    <div style="text-align: center;">
                                                    <p style="padding-top: 4px;padding-bottom: 4px;color: #4a5568;"><b>Blood Group : </b></p>
                                                    <p style="padding-top: 2px;padding-bottom: 2px;"><span >
                                                    @if($user->userprofile->blood_group!=null) 
                                                        {{ucwords($user->userprofile->blood_group)}} VE
                                                        @else
                                                        -
                                                        @endif
                                                    </span></p>
                                                </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                                <div style="padding: 0 25px 10px 25px;color: #000;font-size: 12px;font-style: italic;font-weight: 800;">
                                    <table style="width: 100%;">
                                        <tr>
                                            <td style="text-align: right;">Signature of Principal</td>
                                            
                                        </tr>
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