{{-- SPDX-License-Identifier: MIT --}}
 <footer class="border-t border-gray-800">
<div class="bg-white">
 <div class="container mx-auto py-4 px-4 lg:px-0 ">
 <div class="flex flex-col-reverse lg:flex-row md:flex-row justify-between">
     <div class="text-sm pt-4 lg:pt-0 md:pt-0">
        <p>{!! __('faq.copy') !!}{{date('Y')}}{!! __('faq.allright') !!}</p>
     </div>
     <div>
         <ul class="flex list-reset text-sm footer-links">

             <li class="font-semibold"><a href="{{url('/')}}" class="hover:text-blue-700">{!! __('faq.home') !!}</a></li>
             <li class="font-semibold"><a href="{{url('/about')}}" class="hover:text-blue-700">{!! __('faq.about') !!}</a></li>
             <li class="font-semibold"><a href="{{url('/faq')}}" class="hover:text-blue-700">{!! __('faq.faq') !!}</a></li>
              <li class="font-semibold"><a href="{{url('/swotanalysis')}}" class="hover:text-blue-700">{!! __('faq.swotanalysis') !!}</a></li>
             <!-- <li class="font-semibold"><a href="{{url('/')}}" class="hover:text-blue-700">{!! __('faq.contact') !!}</a></li> -->
             <li class="font-semibold"><a href="{{url('/privacypolicy')}}" class="hover:text-blue-700">{!! __('faq.privacypolicy') !!}</a></li>

         </ul>
     </div>
 </div>
 <!-- <div class="flex flex-col lg:flex-row md:flex-row">
     <div class="w-full lg:w-1/3 md:w-1/3">
         <a href="http://church-membership-pro.test" class="navbar-brand text-3xl">
                    Laravel
         </a>
         <p class="my-2 leading-relaxed text-sm text-gray-700">
             Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s.
         </p>
         <div class="text-gray-700 text-sm my-2 leading-relaxed">
             <p>112, Akshya Nagar 1st Block 1st Cross, <br/>
              Rammurthy nagar, Bangalore-560016</p>
              <p>Phone: (1800) 420 - 4321</p>
              <p>Email: liana44@example.com</p>
         </div>
     </div>
     <div class="w-full lg:w-1/3 md:w-1/3">
         
     </div>
     <div class="w-full lg:w-1/3 md:w-1/3">
         
     </div>
 </div> -->
 </div>
 </div>

 {{--<div class="footer_bg pt-12">
     <div class="container mx-auto">
          <h1 class="text-white text-5xl text-center font-light">Now <span class="text-teal-400">Subscribe</span> us</h1>
    <p class="text-lg text-white leading-loose py-2 text-center">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
    <div class="w-1/2 mx-auto my-10">
        <form>
            <div class="flex">
                <input type="text" name="email" class="border bg-white rounded-l px-3 py-3 focus:outline-none w-3/4" placeholder="Your Email Here..">
                <button class="bg-teal-500 text-white w-1/4 rounded-r flex items-center justify-center"><img src="{{asset('uploads/icons/home/learning.svg')}}" class="w-5 h-5" style="filter: brightness(0) invert(1);"> <span class="mx-2">Submit</span></button>
            </div>
        </form>
        </div>
        <div class="flex justify-between">
            <div class="w-full lg:w-1/3 py-4 ">
                <h2 class="text-2xl font-semibold text-white">About Us</h2>
                <hr class="border-b border-teal-500 w-1/2 mr-auto float-left py-1" />
                <div class="float-left">
                <p class="text-base text-white leading-loose py-2">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
                </div>
            </div>
            <div class="w-full lg:w-1/3 py-4 lg:px-10 " >
            <div class="w-1/2 mx-auto flex flex-col">
                <h2 class="text-2xl font-semibold text-white">School Links</h2>
                <div>
                <hr class="border-b border-teal-500 w-full mr-auto float-left py-1" />
                </div>
                <div class="">
               <ul class="list-disc px-5 leading-loose  text-white">
                   <li class="font-semibold py-1"><a href="{{url('/')}}" class="text-white">{!! __('faq.home') !!}</a></li>
             <li class="font-semibold py-1"><a href="{{url('/about')}}" class="text-white">{!! __('faq.about') !!}</a></li>
             <li class="font-semibold py-1"><a href="{{url('/faq')}}" class="text-white">{!! __('faq.faq') !!}</a></li>
              <li class="font-semibold py-1"><a href="{{url('/swotanalysis')}}" class="text-white">{!! __('faq.swotanalysis') !!}</a></li>
             <!-- <li class="font-semibold"><a href="{{url('/')}}" class="text-white">{!! __('faq.contact') !!}</a></li> -->
             <li class="font-semibold py-1"><a href="{{url('/privacypolicy')}}" class="text-white">{!! __('faq.privacypolicy') !!}</a></li>
               </ul>
                </div>
                </div>
            </div>
            <div class="w-full lg:w-1/3 py-4 lg:px-10 flex flex-col" >
                <h2 class="text-2xl font-semibold text-white">Contact Info</h2>
                <div>
                <hr class="border-b border-teal-500 w-1/2 mr-auto float-left py-1" />
                </div>
                <div class="">
               <ul class="list-reset  leading-loose  text-white">
                   <li class="font-semibold py-1">Address : 123 Main Street Str. London</li>
                    <li class="font-semibold py-1">Phone : (531) 123-123-12</li>
                    <li class="font-semibold py-1">Email : schoolname@mail.com</li>
               </ul>
                </div>
            </div>
        </div>
    
     </div>
         <div class="text-sm py-5 text-white mt-5 border-t text-center border-gray-800">
        <p class="mb-0">{!! __('faq.copy') !!}{{date('Y')}}{!! __('faq.allright') !!}</p>
     </div>
 </div>--}}
 </footer>