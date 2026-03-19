{{-- SPDX-License-Identifier: MIT --}}
<ul class="list-reset text-sm"> 
    <li class="py-3 px-3  {{Request::segment ('2') == 'dashboard' ? 'active':''}}"> <!-- hover:bg-red-900 -->
        <a href="{{url('admin/dashboard')}}" class="flex items-center">
            <!--  <img src="{{asset('uploads/icons/sidebar/dashboard.svg')}}" class="w-5 h-5"> -->
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="512px" height="512px"><g><g><g><g><path d="M256,298.66c-10.527,0.031-20.665,3.982-28.437,11.083l-72.896-41.685c-5.116-2.946-11.652-1.186-14.597,3.931     c-2.945,5.116-1.186,11.652,3.931,14.597l71.755,41.003c-1.565,4.413-2.383,9.056-2.421,13.739     c0,23.564,19.102,42.667,42.667,42.667s42.667-19.102,42.667-42.667C298.667,317.762,279.564,298.66,256,298.66z M256,362.66 c-11.782,0-21.333-9.551-21.333-21.333c0-11.782,9.551-21.333,21.333-21.333c11.782,0,21.333,9.551,21.333,21.333 C277.333,353.109,267.782,362.66,256,362.66z" data-original="#000000" class="active-path" data-old_color="#000000" fill="#FFFFFF"/><path d="M436.501,158.233c-50.187-48.853-117.141-74.549-187.68-72.811C111.616,89.103,0,203.727,0,340.975v57.227 c0.123,15.799,13,28.524,28.8,28.459h454.4c15.796,0.065,28.671-12.653,28.8-28.448v-62.219     C512,268.793,485.184,205.625,436.501,158.233z M490.667,398.233c-0.116,4.018-3.448,7.192-7.467,7.115H28.8     c-4.019,0.078-7.35-3.097-7.467-7.115v-57.259c0-125.781,102.304-230.869,228.053-234.229     c64.12-2.218,126.352,21.907,172.224,66.763c44.523,43.36,69.056,101.056,69.056,162.485V398.233z" data-original="#000000" class="active-path" data-old_color="#000000" fill="#FFFFFF"/><path d="M256,181.327c5.891,0,10.667-4.776,10.667-10.667v-42.667c0-5.891-4.776-10.667-10.667-10.667     c-5.891,0-10.667,4.776-10.667,10.667v42.667C245.333,176.551,250.109,181.327,256,181.327z" data-original="#000000" class="active-path" data-old_color="#000000" fill="#FFFFFF"/><path d="M416,341.327c0,5.891,4.776,10.667,10.667,10.667h42.667c5.891,0,10.667-4.776,10.667-10.667     s-4.776-10.667-10.667-10.667h-42.667C420.776,330.66,416,335.436,416,341.327z" data-original="#000000" class="active-path" data-old_color="#000000" fill="#FFFFFF"/><path d="M85.333,330.66H42.667c-5.891,0-10.667,4.776-10.667,10.667s4.776,10.667,10.667,10.667h42.667     c5.891,0,10.667-4.776,10.667-10.667S91.224,330.66,85.333,330.66z" data-original="#000000" class="active-path" data-old_color="#000000" fill="#FFFFFF"/><path d="M160.491,195.193c2.993,5.075,9.533,6.763,14.608,3.771c5.075-2.993,6.763-9.533,3.771-14.608l-18.133-30.795     c-2.993-5.075-9.533-6.763-14.608-3.771c-5.075,2.993-6.763,9.533-3.771,14.608L160.491,195.193z" data-original="#000000" class="active-path" data-old_color="#000000" fill="#FFFFFF"/><path d="M396.224,256.783c2.893,5.13,9.397,6.945,14.528,4.053l31.477-17.771c5.179-2.807,7.102-9.281,4.295-14.46 c-2.807-5.179-9.281-7.102-14.46-4.295c-0.104,0.056-0.207,0.115-0.31,0.175l-31.477,17.771     C395.147,245.148,393.332,251.651,396.224,256.783z" data-original="#000000" class="active-path" data-old_color="#000000" fill="#FFFFFF"/><path d="M111.723,242.244h0.011l-31.488-17.771c-5.083-2.978-11.618-1.272-14.596,3.811c-2.978,5.083-1.272,11.617,3.811,14.596     c0.102,0.06,0.206,0.118,0.31,0.175l31.477,17.771c5.179,2.807,11.654,0.884,14.46-4.296     C118.449,251.472,116.686,245.152,111.723,242.244z" data-original="#000000" class="active-path" data-old_color="#000000" fill="#FFFFFF"/><path d="M365.915,149.782c-0.016-0.01-0.032-0.019-0.048-0.028c-5.075-2.989-11.612-1.298-14.603,3.776l-18.133,30.795     c-3.001,5.075-1.32,11.623,3.755,14.624c5.075,3.001,11.623,1.32,14.624-3.755l18.133-30.795     C372.65,159.333,370.981,152.788,365.915,149.782z" data-original="#000000" class="active-path" data-old_color="#000000" fill="#FFFFFF"/></g></g></g></g> </svg>
            <span class="mx-3 whitespace-no-wrap">Dashboard</span>
        </a>
    </li>

    <!-- start -->
    @php
        $class='';
        $array=array('students','student','parents','parent','teachers','teacher','staff','staffs','alumni','blocked_students');
        if(in_array(\Request()->segment('2'),$array))
        {
            $class='active';
        }
    @endphp


    <li class="relative py-3 px-3  {{$class}}">
        <a href="#" class="flex items-center">
            <!--  <img src="{{asset('uploads/icons/sidebar/settings.svg')}}" class="w-5 h-5"> -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
  <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
</svg>

            <span class="ml-3 whitespace-no-wrap flex items-center justify-between w-10/12">Academics
            <img src="{{url('images/right-arrow.svg')}}" class="w-2 h-2"> </span>

        </a>
        <ul class="list-reset sites-sidebar">
            <!-- start -->
           
            
            <li class="py-3 px-3 hover:font-semibold{{Request::segment ('2') == 'academics' ? 'active':''}} && {{Request::segment ('2') == 'academics' ? 'active':''}}">
                <a href="{{ url('/superadmin/academics/schools') }}" class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                    </svg>
                    <span class="mx-3 whitespace-no-wrap">Schools</span>
                </a>
            </li>
        </ul>
    </li>

    <li class="relative py-3 px-3  {{$class}}">
        <a href="#" class="flex items-center">
            <!--  <img src="{{asset('uploads/icons/sidebar/settings.svg')}}" class="w-5 h-5"> -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
  <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
</svg>

            <span class="ml-3 whitespace-no-wrap flex items-center justify-between w-10/12">Reports
            <img src="{{url('images/right-arrow.svg')}}" class="w-2 h-2"> </span>

        </a>
        <ul class="list-reset sites-sidebar">
            <!-- start -->
            <li class="py-3 px-3 hover:font-semibold{{Request::segment ('3') == 'contact' ? 'active':''}} && {{Request::segment ('3') == 'contact' ? 'active':''}}">
                <a href="{{ url('/superadmin/reports/contact') }}" class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>
                    <span class="mx-3 whitespace-no-wrap">Contacts</span>
                </a>
            </li>

            {{-- <li class="py-3 px-3 hover:font-semibold{{Request::segment ('3') == 'subscriptions' ? 'active':''}} && {{Request::segment ('3') == 'subscription' ? 'active':''}}">
                <a href="{{ url('/superadmin/reports/subscriptions') }}" class="flex items-center">
                  <img src="{{ url('uploads/superadmin/subscription.png') }}" class="w-5 h-5">
                    <span class="mx-3 whitespace-no-wrap">Subscriptions</span>
                </a>
            </li> --}}
        </ul>
    </li>

    <li class="relative py-3 px-3  {{$class}}">
        <a href="#" class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>

            <span class="ml-3 whitespace-no-wrap flex items-center justify-between w-10/12">Setting
            <img src="{{url('images/right-arrow.svg')}}" class="w-2 h-2"> </span>

        </a>
        <ul class="list-reset sites-sidebar">
          

            <li class="py-3 px-3 hover:font-semibold {{Request::segment ('2') == 'cities' ? 'active':''}} && {{Request::segment ('2') == 'city' ? 'active':''}}">
                <a href="{{ url('/superadmin/setting/cities') }}" class="flex items-center">
                    <img src="{{ url('uploads/superadmin/cities.png') }}" class="w-5 h-5">
                    <span class="mx-3 whitespace-no-wrap">Cities</span>
                </a>
            </li>
       

            <li class="py-3 px-3 hover:font-semibold{{Request::segment ('2') == 'countries' ? 'active':''}} && {{Request::segment ('2') == 'country' ? 'active':''}}">
                <a href="{{ url('/superadmin/setting/countries') }}" class="flex items-center">
                    <img src="{{ url('uploads/superadmin/countries.png') }}" class="w-5 h-5">
                    <span class="mx-3 whitespace-no-wrap">Countries</span>
                </a>
            </li>

            <li class="py-3 px-3 hover:font-semibold{{Request::segment ('2') == 'states' ? 'active':''}} && {{Request::segment ('2') == 'state' ? 'active':''}}">
                <a href="{{ url('/superadmin/setting/states') }}" class="flex items-center">
                    <img src="{{ url('uploads/superadmin/states.png') }}" class="w-5 h-5">
                    <span class="mx-3 whitespace-no-wrap">States</span>
                </a>
            </li>
            
            <li class="py-3 px-3 hover:font-semibold{{Request::segment ('2') == 'plans' ? 'active':''}} && {{Request::segment ('2') == 'plan' ? 'active':''}}">
                <a href="{{ url('/superadmin/setting/plans') }}" class="flex items-center">
                    <img src="{{ url('uploads/superadmin/plan.png') }}" class="w-5 h-5">
                    <span class="mx-3 whitespace-no-wrap">Plans</span>
                </a>
            </li>

        </ul>
    </li>
    <!-- end -->

</ul>