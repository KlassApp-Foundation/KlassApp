{{-- SPDX-License-Identifier: MIT --}}
@unless(request()->is('/'))
    <nav class="navbar sticky bg-white w-full flex  lg:flex-row px-4 lg:px-8 pt-4 mb-0 justify-between items-center shadow">
        <div>
            <a href="{{ url('/') }}" >
                <img src="{{ asset('images/gego_k12.png') }}" class="inline-block mb-4" style="height:55px;" alt="GegoK12 Logo">
            </a>
        </div>

        <div>
            <ul class="navbar-nav ml-auto flex items-center">
                @guest
                    <li class="nav-item px-2">
                        <a class="text-sm lg:text-base px-3 py-1 rounded bg-white border hover:bg-gray-300 hover:text-gray-900" href="{{ route('register') }}" id="register">{{ __('Free Sign Up') }}</a>
                    </li>
                    <li class="nav-item px-2">
                        <a class="text-sm lg:text-base px-3 py-1 rounded bg-white border hover:bg-gray-300 hover:text-gray-900" href="{{ route('login') }}" id="login">{{ __('Login') }}</a>
                    </li>
                @endguest
                @auth
                    <li class="nav-item px-2">
                        <a class="text-sm lg:text-base px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 hover:text-white" href="{{ route('dashboard') }}" id="dashboard">{{ __('Dashboard') }}</a>
                    </li>
                    <li class="">
                        <a class="text-sm lg:text-base  px-3 py-1 rounded bg-white border hover:bg-gray-300 hover:text-gray-900" dusk="logout-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('Logout') }}</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                @endauth
            </ul>
        </div>
    </nav>
@endunless

@if(request()->is('/'))
    <nav class="navbar bg-white w-full flex  lg:flex-row px-3 mt-4 mb-0 justify-between items-center home-navbar" ><!-- onscroll="checkscroll()" -->
        <!-- <div class="w-1/6 lg:w-1/6 md:w-1/6 pt-4">
            <a class="text-sm lg:text-base px-3 py-1 rounded bg-white border hover:bg-gray-300 hover:text-gray-900 whitespace-no-wrap" href="https://gegosoft.com">{{ __('A Gegosoft Product') }}</a>
        </div> -->
        <!-- <div class="w-4/6 lg:w-3/6 absolute lg:relative px-3 left-0 homemenu">
            <div class="hamburger-menu">
                <input id="menu__toggle" type="checkbox"/>
                <label class="menu__btn" for="menu__toggle">
                    <span></span>
                    <p class="text-sm ml-10 mr-5 text-white menu__text">Menu</p>
                </label>
                <div class="menu__box w-full">
                    <div class="w-4/6 lg:w-3/6  bg-white h-full absolute overflow-y-auto">
                        <div class="rounded-full w-8 h-8 bg-red-600 flex items-center justify-center absolute" style="top: 24px;right: 40px;">
                            <a href="#">
                                <svg class="w-5 h-5 fill-current text-white " version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 426.667 426.667" style="enable-background:new 0 0 426.667 426.667;" xml:space="preserve"><g><g><polygon points="213.333,32 0,224 64,224 64,394.667 170.667,394.667 170.667,266.667 256,266.667 256,394.667 362.667,394.667 362.667,224 426.667,224"/></g></g></svg>
                            </a>
                        </div>
                        <div class="py-16">
                            <div class="py-4">
                                <h2 class="text-base font-semibold text-gray-500 px-4 py-2">Education Type</h2>
                                <ul class="leading-loose text-gray-700 font-semibold pt-3 text-sm">
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">Education Type 1</a></li>
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">Education Type 2</a></li>
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">Education Type 3</a></li>
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">Education Type 4</a></li>
                                </ul>
                            </div> 
                            <div class="py-4">  
                                <h2 class="text-base font-semibold text-gray-500 px-4 py-2">Also From Gegok12</h2>
                                <ul class="leading-loose text-gray-700 font-semibold pt-3 text-sm">
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">About us</a></li>
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">News</a></li>
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">Privacy Policy</a></li>
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">Service & Contact</a></li>
                                </ul>
                            </div>
                            <div class="py-4">
                                <h2 class="text-base font-semibold text-gray-500 px-4 py-2">About Gegok12</h2>
                                <ul class="leading-loose text-gray-700 font-semibold pt-3 text-sm">
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">About us</a></li>
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">News</a></li>
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">Privacy Policy</a></li>
                                    <li class="px-4 py-2 hover:bg-gray-100"><a class="menu__item" href="#">Service & Contact</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        <div class="w-9/12 lg:w-2/6 md:w-2/6 flex flex-row items-center justify-end pt-1 lg:px-8 ml-auto mt-2">
            <div>
                <ul class="navbar-nav ml-auto flex items-center">
                    @guest
                        <li class="nav-item px-2">
                            <a class="text-sm lg:text-base px-3 py-1 rounded bg-white border hover:bg-gray-300 hover:text-gray-900" href="{{ route('login') }}" id="login">{{ __('Login') }}</a>
                        </li>
                    @endguest
                    @auth
                        <li class="px-2">
                            <a class="text-sm lg:text-base px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 hover:text-white" href="{{ route('dashboard') }}" id="dashboard">{{ __('Dashboard') }}</a>
                        </li>
                        <li class="">
                            <a class="text-sm lg:text-base  px-3 py-1 rounded bg-white border hover:bg-gray-300 hover:text-gray-900" dusk="logout-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('Logout') }}</a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>
@endif