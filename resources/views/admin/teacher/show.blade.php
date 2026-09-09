{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
    <div class="">
        @include('partials.message')
        <div class="py-2 mt-2 border-b">
            <h1 class="admin-h1 mb-3 flex items-center">
                <a  href="{{ url('/admin/teachers') }}" class="rounded-full bg-gray-100 p-2" title="Back">
                    <svg class="w-3 h-3 fill-current text-gray-700" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 492 492" style="enable-background:new 0 0 492 492;" xml:space="preserve" width="512px" height="512px"><g><g><g><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124 c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844 L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412 c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008 c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788 C492,219.198,479.172,207.418,464.344,207.418z" data-original="#000000" class="active-path" fill=""/></g></g></g> </svg>
                </a>
                <span class="mx-3">Teaching Staff Profile</span>
            </h1>
        </div>
        <div class="flex flex-col lg:flex-row md:flex-col">
            <div class="w-full lg:w-1/5 md:w-full py-3">
                <!-- start -->
                <div class="bg-white  rounded leading-relaxed">
                    <div class="relative">
                        <div class="p-4">
                            <img src="{{ $user->userprofile->AvatarPath }}" class="w-full max-h-48 w-auto object-cover">
                        </div>
                        <div class=" mx-auto p-2 ">
                            <ul class="flex justify-center">
                                <li class="mx-2">
                                    <a href="{{url('/admin/teacher/edit/'.$user->name)}}" title="Edit Member" class="text-white text-xs flex items-center blue-bg rounded p-1" id="edit">
                                        <svg class="w-3 h-3 fill-current text-white" id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m128.285 260.925h319.073v75h-319.073z" transform="matrix(.707 -.707 .707 .707 -126.717 290.929)"/><path d="m29.021 422.521-29.021 89.479 89.481-29.02z"/><path d="m54.039 186.679h319.073v75h-319.073z" transform="matrix(.707 -.707 .707 .707 -95.964 216.682)"/><path d="m371.541 5.46h90v180h-90z" transform="matrix(.707 -.707 .707 .707 54.502 322.498)"/><path d="m57.148 335.796-17.737 54.689 82.106 82.105 54.689-17.737z"/></g></svg>
                                        <span class="mx-1">Edit</span>
                                    </a>
                                </li>

                                <li class="mx-2">
                                    <form action="{{ url('/admin/teacher/delete', ['name'=>$user->name]) }}" method="POST" class="text-white text-xs flex items-center bg-red-600 rounded p-1" id="delete">
                                        @csrf
                                        @method('delete')
                                   
                                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve" class="w-3 h-3 fill-current text-white"><g><g><g><polygon points="353.574,176.526 313.496,175.056 304.807,412.34 344.885,413.804"></polygon><rect x="235.948" y="175.791" width="40.104" height="237.285"></rect><polygon points="207.186,412.334 198.497,175.049 158.419,176.52 167.109,413.804"></polygon> <path d="M17.379,76.867v40.104h41.789L92.32,493.706C93.229,504.059,101.899,512,112.292,512h286.74 c10.394,0,19.07-7.947,19.972-18.301l33.153-376.728h42.464V76.867H17.379z M380.665,471.896H130.654L99.426,116.971h312.474 L380.665,471.896z"></path></g></g></g> <g><g><path d="M321.504,0H190.496c-18.428,0-33.42,14.992-33.42,33.42v63.499h40.104V40.104h117.64v56.815h40.104V33.42 C354.924,14.992,339.932,0,321.504,0z"></path></g></g></svg>
                                        <button type="submit" class="mx-1">Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="my-2 px-2">
                        <p class="capitalize text-gray-600 text-sm">basic information :</p>
                        <ul class="list-reset text-xs leading-relaxed my-2">
                            <li class="flex py-1">
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 fill-current text-gray-800" id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m144 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m144 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m144 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m272 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m272 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m272 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m400 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m400 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m400 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m467 65h-36v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-130v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-130v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-36c-24.813 0-45 20.187-45 45v332c0 24.813 20.187 45 45 45h422c24.813 0 45-20.187 45-45 0-9.682 0-323.575 0-332 0-24.813-20.187-45-45-45zm-437 45c0-8.271 6.729-15 15-15h36v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h130v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h130v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h36c8.271 0 15 6.729 15 15v59h-452zm437 347h-422c-8.271 0-15-6.729-15-15v-243h452v243c0 8.271-6.729 15-15 15z"/></g></svg>
                                    <span class="mx-2 text-gray-700 font-medium">Date Of Birth  :</span>
                                </div>
                                <div class="">
                                    <p>{{ date('d-m-Y',strtotime(optional($user->userprofile)->date_of_birth)) }}</p>
                                </div>
                            </li>
                            <li class="flex py-1">
                                <div class="flex items-center">
                                   <svg class="w-3 h-3 fill-current text-gray-800" xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 512 512" height="512px" viewBox="0 0 512 512" width="512px"><g><path d="m181 356.391v34.609h-60v60h60v61h60v-61h60v-60h-60v-34.609c60.07-13.678 105-67.18 105-131.391 0-26.389-7.87-50.817-20.958-71.614l50.958-50.959v17.573h60v-120h-120v60h17.573l-50.958 50.958c-20.798-13.088-45.226-20.958-71.615-20.958-74.559 0-135 60.441-135 135 0 64.211 44.93 117.713 105 131.391zm30-206.391c41.353 0 75 33.647 75 75s-33.647 75-75 75-75-33.647-75-75 33.647-75 75-75z" data-original="#000000" class="active-path" fill=""/></g> </svg>
                                    <span class="mx-2 text-gray-700 font-medium">Gender :</span>
                                </div>
                                <div class="">
                                    <p class="capitalize">{{ optional($user->userprofile)->gender }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="my-4 px-2">
                        <p class="capitalize text-gray-600 text-sm">contact information :</p>
                        <ul class="list-reset text-xs leading-loose my-2">
                            <li class="flex items-baseline py-1">
                                <svg height="682pt" viewBox="-119 -21 682 682.66669" width="682pt" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current text-gray-800"><path d="m216.210938 0c-122.664063 0-222.460938 99.796875-222.460938 222.460938 0 154.175781 222.679688 417.539062 222.679688 417.539062s222.242187-270.945312 222.242187-417.539062c0-122.664063-99.792969-222.460938-222.460937-222.460938zm67.121093 287.597656c-18.507812 18.503906-42.8125 27.757813-67.121093 27.757813-24.304688 0-48.617188-9.253907-67.117188-27.757813-37.011719-37.007812-37.011719-97.226562 0-134.238281 17.921875-17.929687 41.761719-27.804687 67.117188-27.804687 25.355468 0 49.191406 9.878906 67.121093 27.804687 37.011719 37.011719 37.011719 97.230469 0 134.238281zm0 0"></path></svg>
                                <div class="w-full mx-2">
                                    <p class="text-gray-700 leading-normal">
                                        @if($user->userprofile->address==null)
                                            --
                                        @else
                                            {{ optional($user->userprofile)->address }}
                                        @endif
                                    </p>
                                </div>
                            </li>
                            <li class="flex py-1 items-center">
                                <svg height="512" viewBox="0 0 58 58" width="512" xmlns="http://www.w3.org/2000/svg" class="fill-current w-3 h-3 text-gray-800"><g id="Page-1" fill="none" fill-rule="evenodd"><g id="003---Call" fill="rgb(0,0,0)" fill-rule="nonzero" transform="translate(-1)"><path id="Shape" d="m25.017 33.983c-5.536-5.536-6.786-11.072-7.068-13.29-.0787994-.6132828.1322481-1.2283144.571-1.664l4.48-4.478c.6590136-.6586066.7759629-1.685024.282-2.475l-7.133-11.076c-.5464837-.87475134-1.6685624-1.19045777-2.591-.729l-11.451 5.393c-.74594117.367308-1.18469338 1.15985405-1.1 1.987.6 5.7 3.085 19.712 16.855 33.483s27.78 16.255 33.483 16.855c.827146.0846934 1.619692-.3540588 1.987-1.1l5.393-11.451c.4597307-.9204474.146114-2.0395184-.725-2.587l-11.076-7.131c-.7895259-.4944789-1.8158967-.3783642-2.475.28l-4.478 4.48c-.4356856.4387519-1.0507172.6497994-1.664.571-2.218-.282-7.754-1.532-13.29-7.068z"></path><path id="Shape" d="m47 31c-1.1045695 0-2-.8954305-2-2-.0093685-8.2803876-6.7196124-14.9906315-15-15-1.1045695 0-2-.8954305-2-2s.8954305-2 2-2c10.4886126.0115735 18.9884265 8.5113874 19 19 0 1.1045695-.8954305 2-2 2z"></path><path id="Shape" d="m57 31c-1.1045695 0-2-.8954305-2-2-.0154309-13.800722-11.199278-24.9845691-25-25-1.1045695 0-2-.8954305-2-2s.8954305-2 2-2c16.008947.01763587 28.9823641 12.991053 29 29 0 .530433-.2107137 1.0391408-.5857864 1.4142136-.3750728.3750727-.8837806.5857864-1.4142136.5857864z"></path></g></g></svg>
                                <div class="w-full mx-2">
                                    <a href="#" class="blue-text">
                                        @if($user->mobile_no != null)
                                            {{ $user->mobile_no }}
                                        @else
                                            --
                                        @endif
                                    </a>
                                </div>
                            </li>
                            <li class="flex py-1 items-center">
                                <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve" class="w-3 h-3 fill-current text-gray-800"><g><g><polygon points="339.392,258.624 512,367.744 512,144.896        "></polygon></g></g> <g><g><polygon points="0,144.896 0,367.744 172.608,258.624        "></polygon></g></g> <g><g><path d="M480,80H32C16.032,80,3.36,91.904,0.96,107.232L256,275.264l255.04-168.032C508.64,91.904,495.968,80,480,80z"></path></g></g> <g><g><path d="M310.08,277.952l-45.28,29.824c-2.688,1.76-5.728,2.624-8.8,2.624c-3.072,0-6.112-0.864-8.8-2.624l-45.28-29.856 L1.024,404.992C3.488,420.192,16.096,432,32,432h448c15.904,0,28.512-11.808,30.976-27.008L310.08,277.952z"></path></g></g></svg>
                                <div class="w-full mx-2">
                                    @if($user->email == '')
                                        --
                                    @else
                                        <a href="#" class="blue-text">{{ $user->email }}</a>
                                    @endif
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- end -->
            </div>
            <div class="w-full lg:w-4/5 md:w-full lg:mx-8 md:mx-0">
                <div class="flex lg:items-center lg:justify-between flex-col lg:flex-row">
                    <h3 class="font-semibold text-3xl text-gray-700">{{ ucwords($user->FullName) }}</h3>
                    <div class="my-3 flex text-xs">
                    
                        @if(optional($user)->status == "inactive")
                            <a href="#" rel="{{ url('/admin/user/updateStatus/'.$user->name) }}" class="capitalize text-white custom-green rounded px-2 py-1 font-medium activate" value="active" id="status">Activate</a>
                        @else

                            <a href="#" rel="{{ url('/admin/user/updateStatus/'.$user->name) }}" class="capitalize text-white bg-red-600 rounded px-2 py-1  font-medium activate" value="inactive" id="status">Deactivate</a>

                        @endif

                          @if(optional($user)->status != "exit")
                            <a href="#" rel="{{ url('/admin/user/updateStatus/'.$user->name) }} " class="capitalize text-teal-500 rounded px-4 py-1 font-medium activate my-1 lg:my-0 md:my-0 mr-2 " value="exit" id="status">Exit</a>
                        @endif
                    
                        @if($user->email != null)
                            @if($user->email_verified == 1)
                                <a href="#" rel="{{ url('/admin/user/resetPassword/'.$user->id) }}" class="capitalize text-white blue-bg rounded px-2 py-1 ml-2 font-medium reset">reset Password</a>
                            @endif

                            @if($user->email_verified != 1)
                                <a href="#" rel="{{ url('/admin/user/'.$user->id.'/verificationcode') }}" class="capitalize text-white blue-bg rounded px-2 py-1 ml-2 font-medium verify" id="verify_mail">verify email</a>
                            @endif
                        @endif

                        @if($user->userprofile->usergroup_id=='8')
                         <a href="{{ url('/library/'.$user->id.'/impersonate') }}" target="_blank" class="capitalize text-white blue-bg rounded px-2 py-1 ml-2 font-medium">Login as Librarian</a>
                         @else
                        <a href="{{ url('/teacher/'.$user->id.'/impersonate') }}" target="_blank" class="capitalize text-white bg-purple-500 rounded px-2 py-1 ml-2 font-medium">Login as Teacher</a>
                        @endif
                        
                    </div>
                </div>
                <div class="leading-relaxed">
                    <p class="text-lg text-gray-700 font-semibold">ID: {{ $user->id }}</p>
                    <change-credential url="{{url('/')}}" name="{{$user->name}}"  ></change-credential>
                </div>
                <div class="bg-white shadow my-5">
                    <profile-tab-teacher url="{{url('/')}}"  entity_id="{{ $user->id }}" school_id="{{ $user->school_id }}" name="{{$user->name}}" mode="teacher"></profile-tab-teacher>
                   
                    <portal-target name="teacherprofile"></portal-target>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')

<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $('.activate').on('click', function(){
            var link = $(this).attr('rel');
            var status = $(this).attr('value');
            //alert(status);
            swal({
                icon: "info",
                text: "Do you want to change the status ?",
                buttons: {
                    cancel: true,
                    confirm: true,
                },
                allowOutsideClick: false,
            }).then((willChange) => {
                if (willChange) 
                {
                    $.ajax({
                        url: link,
                        data: { status: status },
                        type: "POST",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success:function(data)
                        {
                            //alert(ans);
                            swal({
                                icon: "success",
                                text: "Teacher Status Updated Successfully",
                            }).then(function(){
                                window.location.reload();
                            });
                        }
                    })
                } 
                else 
                {
                    swal("Cancelled");
                } 
            });
        });
    });


    $(document).ready(function(){
        $('.reset').on('click', function(){
            var link = $(this).attr('rel');
            //alert(link);
            swal({
                icon: "info",
                text: "Do you want to reset password for this teacher ?",
                buttons: {
                    cancel: true,
                    confirm: true,
                },
                allowOutsideClick: false,
            }).then((willChange) => {
                if (willChange) 
                {
                    $.ajax({
                        url: link,
                        type: "GET",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success:function(data)
                        {
                            //alert(ans);
                            swal({
                                icon: "success",
                                text: "Check your email to reset the password",
                                showConfirmButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                            }).then(function(){
                                window.location.reload();
                            });
                        }
                    })
                }
                else 
                {
                    swal("Cancelled");
                } 
            });
        });
    });

    $(document).ready(function(){
        $('.verify').on('click', function(){
            var link = $(this).attr('rel');
            //alert(link);
            swal({
                icon: "info",
                text: "Do you want to verify email for this teacher ?",
                buttons: {
                    cancel: true,
                    confirm: true,
                },
                allowOutsideClick: false,
            }).then((willChange) => {
                if (willChange) 
                {
                    $.ajax({
                        url: link,
                        type: "GET",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success:function(data)
                        {
                            //alert(ans);
                            swal({
                                icon: "success",
                                text: "Verification code sent Successfully",
                                showConfirmButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                            }).then(function(){
                                window.location.reload();
                            });
                        }
                    })
                }
                else 
                {
                    swal("Cancelled");
                } 
            });
        });
    });

    $(document).ready(function(){
        $('.exit-member').on('click', function(){
            var link = $(this).attr('rel');
            var name = {!! json_encode($user->name) !!};
            //alert(link);
            swal({
                icon: "info",
                text: "Do you want to exit this member ?",
                buttons: {
                    cancel: true,
                    confirm: true,
                },
                allowOutsideClick: false,
            }).then((willChange) => {
                if (willChange) 
                {
                    $.ajax({
                        url: link,
                        type: "GET",
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        success:function(data)
                        {
                            //alert(name);
                            window.location.href="/admin/teacher/exit/"+name;
                        }
                    })
                }
                else 
                {
                    swal("Cancelled");
                } 
            });
        });
    });
</script>

@endpush