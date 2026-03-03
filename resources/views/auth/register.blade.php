{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@section('content')

<div class="flex flex-col w-full my-4 mx-2 lg:mx-0 md:mx-0">
@include('layouts.partials.logo')
<div class="w-full lg:w-1/3 m-8  p-8 mx-auto login-form relative">
  <div class="card-header uppercase text-gray-200 text-center text-lg font-semibold tracking-widest">{{ __('Register your School here') }}</div>

  <div class="card-body">

    @if(\Config::get('settings.register')==1)
      <div class="alert-box danger">
        Register page is under maintenance!!!
      </div>
    @else
      <form method="POST" action="{{ route('register') }}" aria-label="{{ __('Register') }}">
        @csrf
        <div class="lg:px-5 md:px-5">
          <div class="form-group py-3">
            <div class="relative">
            <div class="input-group flex w-full">
              <span class="input-group-addon w-6 flex items-center justify-center" style="color: #aaa;">
                <svg class="w-4 h-4 fill-current" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
   viewBox="0 0 480 480" style="enable-background:new 0 0 480 480;" xml:space="preserve">
<g>
  <g>
    <g>
      <path d="M472,192H336v-24c0-2.56-1.225-4.966-3.296-6.472L248,99.928V72h88c4.418,0,8-3.582,8-8V16c0-4.418-3.582-8-8-8h-88
        c0-4.418-3.582-8-8-8s-8,3.582-8,8v91.928l-84.704,61.6C145.225,163.034,144,165.44,144,168v24H8c-4.418,0-8,3.582-8,8v272
        c0,4.418,3.582,8,8,8h464c4.418,0,8-3.582,8-8V200C480,195.582,476.418,192,472,192z M248,24h80v32h-80V24z M144,464H16V208h128
        V464z M272,464h-64v-80h64V464z M320,464h-32v-88c0-4.418-3.582-8-8-8h-80c-4.418,0-8,3.582-8,8v88h-32V172.072l80-58.184
        l80,58.184V464z M464,464H336V208h128V464z"/>
      <rect x="40" y="232" width="32" height="16"/>
      <rect x="88" y="232" width="32" height="16"/>
      <rect x="40" y="264" width="32" height="16"/>
      <rect x="88" y="264" width="32" height="16"/>
      <rect x="40" y="296" width="32" height="16"/>
      <rect x="88" y="296" width="32" height="16"/>
      <rect x="40" y="328" width="32" height="16"/>
      <rect x="88" y="328" width="32" height="16"/>
      <rect x="40" y="360" width="32" height="16"/>
      <rect x="88" y="360" width="32" height="16"/>
      <rect x="360" y="232" width="32" height="16"/>
      <rect x="408" y="232" width="32" height="16"/>
      <rect x="360" y="264" width="32" height="16"/>
      <rect x="408" y="264" width="32" height="16"/>
      <rect x="360" y="296" width="32" height="16"/>
      <rect x="408" y="296" width="32" height="16"/>
      <rect x="360" y="328" width="32" height="16"/>
      <rect x="408" y="328" width="32" height="16"/>
      <rect x="360" y="360" width="32" height="16"/>
      <rect x="408" y="360" width="32" height="16"/>
      <path d="M184,240c0.035,30.913,25.087,55.965,56,56c30.928,0,56-25.072,56-56c0-30.928-25.072-56-56-56
        C209.072,184,184,209.072,184,240z M280,240c0,22.091-17.909,40-40,40c-22.091,0-40-17.909-40-40c0.026-22.08,17.92-39.974,40-40
        C262.091,200,280,217.909,280,240z"/>
      <polygon points="232,232 216,232 216,248 248,248 248,208 232,208      "/>
    </g>
  </g>
</g></svg>
              </span>
              <input id="school_name" type="text" class="form-control px-2 py-2 w-full text-sm inputAnimation focus:outline-none" name="school_name" value="{{ old('school_name') }}" placeholder="" required>
                <label for="school_name" class="control-label text-sm">School Name</label>
            </div>
            </div>
            @if ($errors->has('school_name'))
              <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
                {{ $errors->first('school_name') }}
              </span>
            @endif
          </div>

          <!-- <div class="form-group my-6">
            <div class="input-group flex w-full">
              <input id="address" type="address" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }} px-2 py-2 w-full text-sm" name="address" value="{{ old('address') }}" placeholder="Address">
            </div>
            @if ($errors->has('address'))
              <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
                {{ $errors->first('address') }}
              </span>
            @endif
          </div>

          <div class="form-group my-6">
            <div class="">
              <select name="state" class="form-control{{ $errors->has('state') ? ' is-invalid' : '' }} px-2 py-2 w-full text-sm">
                <option value="">Select State</option>
                @foreach($states as $state)
                  <option value="{{ $state->name }}" >{{ ucwords($state->name) }}</option>
                @endforeach
              </select>
              @if ($errors->has('state'))
                <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
                  {{ $errors->first('state') }}
                </span>
              @endif
            </div>
          </div>

          <div class="form-group my-6">
            <div class="">
                <select name="city" class="form-control{{ $errors->has('city') ? ' is-invalid' : '' }} px-2 py-2 w-full text-sm">
                  <option value="">Select City</option>
                  @foreach($city_names as $city)
                  <option value="{{ $city->name }}" >{{ ucwords($city->name) }}</option>
                  @endforeach
                </select>
                @if ($errors->has('city'))
                    <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
                    {{ $errors->first('city') }}
                    </span>
                @endif
            </div>
          </div>

          <div class="form-group my-6">
            <div class="">
              <input id="pincode" type="text" class="form-control{{ $errors->has('pincode') ? ' is-invalid' : '' }} px-2 py-2 w-full text-sm" name="pincode" value="{{ old('pincode') }}" placeholder="Pincode">
              @if ($errors->has('pincode'))
                <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
                  {{ $errors->first('pincode') }}
                </span>
              @endif
            </div>
          </div> -->

          <div class="form-group py-3">
          <div class="relative">
            <div class="input-group flex w-full">

              <span class="input-group-addon w-6 flex items-center justify-center" style="color: #aaa;">
               <svg class="w-4 h-4 fill-current" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
   viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
  <g>
    <path d="M256,0c-74.439,0-135,60.561-135,135s60.561,135,135,135s135-60.561,135-135S330.439,0,256,0z M256,240
      c-57.897,0-105-47.103-105-105c0-57.897,47.103-105,105-105c57.897,0,105,47.103,105,105C361,192.897,313.897,240,256,240z"/>
  </g>
</g>
<g>
  <g>
    <path d="M423.966,358.195C387.006,320.667,338.009,300,286,300h-60c-52.008,0-101.006,20.667-137.966,58.195
      C51.255,395.539,31,444.833,31,497c0,8.284,6.716,15,15,15h420c8.284,0,15-6.716,15-15
      C481,444.833,460.745,395.539,423.966,358.195z M61.66,482c7.515-85.086,78.351-152,164.34-152h60
      c85.989,0,156.825,66.914,164.34,152H61.66z"/>
  </g>
</g></svg>

              </span>
              <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }} px-2 py-2 w-full text-sm inputAnimation focus:outline-none" name="name" value="{{ old('name') }}" placeholder="" required>
                <label for="name" class="control-label text-sm">Username</label>
            </div>
            </div>
            @if ($errors->has('name'))
              <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
                {{ $errors->first('name') }}
              </span>
            @endif
          </div>

          <div class="form-group py-3">
          <div class="relative"> 
            <div class="input-group flex w-full">
              <span class="input-group-addon w-6 flex items-center justify-center" style="color: #aaa;">
               <svg class="w-4 h-4 fill-current" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
   viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
  <g>
    <path d="M298.667,25.6h-85.333c-4.71,0-8.533,3.823-8.533,8.533c0,4.71,3.823,8.533,8.533,8.533h85.333
      c4.71,0,8.533-3.823,8.533-8.533C307.2,29.423,303.377,25.6,298.667,25.6z"/>
  </g>
</g>
<g>
  <g>
    <path d="M358.4,25.6h-8.533c-4.71,0-8.533,3.823-8.533,8.533c0,4.71,3.823,8.533,8.533,8.533h8.533
      c4.71,0,8.533-3.823,8.533-8.533C366.933,29.423,363.11,25.6,358.4,25.6z"/>
  </g>
</g>
<g>
  <g>
    <path d="M266.598,435.2H245.41c-12.979,0-23.543,10.564-23.543,23.543v4.122c0,12.979,10.564,23.535,23.535,23.535h21.188
      c12.979,0,23.543-10.556,23.543-23.535v-4.122C290.133,445.764,279.569,435.2,266.598,435.2z M273.067,462.865
      c0,3.567-2.901,6.468-6.468,6.468H245.41c-3.575,0-6.477-2.901-6.477-6.468v-4.122c0-3.575,2.901-6.477,6.477-6.477h21.18
      c3.576,0,6.477,2.901,6.477,6.477V462.865z"/>
  </g>
</g>
<g>
  <g>
    <path d="M370.227,0H141.781c-17.007,0-30.848,13.841-30.848,30.848v450.304c0,17.007,13.841,30.848,30.848,30.848h228.437
      c17.007,0,30.848-13.841,30.848-30.839V30.848C401.067,13.841,387.226,0,370.227,0z M384,481.152
      c0,7.595-6.178,13.781-13.773,13.781H141.781c-7.603,0-13.781-6.187-13.781-13.773V30.848c0-7.595,6.178-13.781,13.781-13.781
      h228.437c7.603,0,13.781,6.187,13.781,13.781V481.152z"/>
  </g>
</g>
<g>
  <g>
    <path d="M392.533,51.2H119.467c-4.71,0-8.533,3.823-8.533,8.533v358.4c0,4.71,3.823,8.533,8.533,8.533h273.067
      c4.71,0,8.533-3.823,8.533-8.533v-358.4C401.067,55.023,397.244,51.2,392.533,51.2z M384,409.6H128V68.267h256V409.6z"/>
  </g>
</g></svg>
              </span>
              <input id="mobile_no" type="text" class="form-control{{ $errors->has('mobile_no') ? ' is-invalid' : '' }} px-2 py-2 w-full text-sm inputAnimation focus:outline-none" name="mobile_no" value="{{ old('mobile_no') }}" placeholder="" required>
               <label for="mobile_no" class="control-label text-sm">Mobile Number</label>
            </div>
            </div>
            @if ($errors->has('mobile_no'))
              <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
                {{ $errors->first('mobile_no') }}
              </span>
            @endif
          </div>

          <div class="form-group py-3">
          <div class="relative">
            <div class="input-group flex w-full">

              <span class="input-group-addon w-6 flex items-center justify-center" style="color: #aaa;">
              <svg class="fill-current w-4 h-4" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
     viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<g>
    <g>
        <path d="M469.333,64H42.667C19.135,64,0,83.135,0,106.667v298.667C0,428.865,19.135,448,42.667,448h426.667
            C492.865,448,512,428.865,512,405.333V106.667C512,83.135,492.865,64,469.333,64z M42.667,85.333h426.667
            c1.572,0,2.957,0.573,4.432,0.897c-36.939,33.807-159.423,145.859-202.286,184.478c-3.354,3.021-8.76,6.625-15.479,6.625
            s-12.125-3.604-15.49-6.635C197.652,232.085,75.161,120.027,38.228,86.232C39.706,85.908,41.094,85.333,42.667,85.333z
             M21.333,405.333V106.667c0-2.09,0.63-3.986,1.194-5.896c28.272,25.876,113.736,104.06,169.152,154.453
            C136.443,302.671,50.957,383.719,22.46,410.893C21.957,409.079,21.333,407.305,21.333,405.333z M469.333,426.667H42.667
            c-1.704,0-3.219-0.594-4.81-0.974c29.447-28.072,115.477-109.586,169.742-156.009c7.074,6.417,13.536,12.268,18.63,16.858
            c8.792,7.938,19.083,12.125,29.771,12.125s20.979-4.188,29.76-12.115c5.096-4.592,11.563-10.448,18.641-16.868
            c54.268,46.418,140.286,127.926,169.742,156.009C472.552,426.073,471.039,426.667,469.333,426.667z M490.667,405.333
            c0,1.971-0.624,3.746-1.126,5.56c-28.508-27.188-113.984-108.227-169.219-155.668c55.418-50.393,140.869-128.57,169.151-154.456
            c0.564,1.91,1.194,3.807,1.194,5.897V405.333z"/>
    </g>
</g> </svg>

              </span>
              <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }} px-2 py-2 w-full text-sm inputAnimation focus:outline-none" name="email" value="{{ old('email') }}" placeholder="" required>
               <label for="email" class="control-label text-sm">E-Mail Address</label>
            </div>
            </div>
            @if ($errors->has('email'))
              <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
                {{ $errors->first('email') }}
              </span>
            @endif
          </div>

          <div class="form-group py-3">
          <div class="relative">
            <div class="input-group flex w-full">

              <span class="input-group-addon w-6 flex items-center justify-center" style="color: #aaa;">
              <svg class="fill-current w-4 h-4" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
     viewBox="0 0 477.867 477.867" style="enable-background:new 0 0 477.867 477.867;" xml:space="preserve">
<g>
    <g>
        <path d="M409.6,170.667h-17.067V153.6C392.439,68.808,323.725,0.094,238.933,0c-84.792,0.094-153.506,68.808-153.6,153.6v17.067
            H68.267c-9.426,0-17.067,7.641-17.067,17.067V460.8c0,9.426,7.641,17.067,17.067,17.067H409.6c9.426,0,17.067-7.641,17.067-17.067
            V187.733C426.667,178.308,419.026,170.667,409.6,170.667z M119.467,153.6c0-65.98,53.487-119.467,119.467-119.467
            S358.4,87.62,358.4,153.6v17.067H119.467V153.6z M392.533,443.733h-307.2V204.8h307.2V443.733z"/>
    </g>
</g>
<g>
    <g>
        <path d="M287.209,290.111c-7.211-20.472-26.571-34.152-48.276-34.111c-28.211-0.053-51.124,22.773-51.177,50.984
            c-0.041,21.705,13.639,41.065,34.111,48.276v37.274c0,9.426,7.641,17.067,17.067,17.067S256,401.959,256,392.533V355.26
            C282.609,345.888,296.582,316.719,287.209,290.111z M238.933,324.267c-9.426,0-17.067-7.641-17.067-17.067
            s7.641-17.067,17.067-17.067S256,297.774,256,307.2S248.359,324.267,238.933,324.267z"/>
    </g>
</g></svg>
              </span>
                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }} px-2 py-2 w-full text-sm inputAnimation focus:outline-none" name="password" placeholder="" required>
                 <label for="password" class="control-label text-sm">Password</label>
            </div>     

            </div>
            @if ($errors->has('password'))
              <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
                {{ $errors->first('password') }}
              </span>
            @endif
          </div>

          <div class="form-group py-3">
          <div class="relative">
            <div class="input-group flex w-full">

              <span class="input-group-addon w-6 flex items-center justify-center" style="color: #aaa;">
               <svg class="fill-current w-4 h-4" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
     viewBox="0 0 477.867 477.867" style="enable-background:new 0 0 477.867 477.867;" xml:space="preserve">
<g>
    <g>
        <path d="M409.6,170.667h-17.067V153.6C392.439,68.808,323.725,0.094,238.933,0c-84.792,0.094-153.506,68.808-153.6,153.6v17.067
            H68.267c-9.426,0-17.067,7.641-17.067,17.067V460.8c0,9.426,7.641,17.067,17.067,17.067H409.6c9.426,0,17.067-7.641,17.067-17.067
            V187.733C426.667,178.308,419.026,170.667,409.6,170.667z M119.467,153.6c0-65.98,53.487-119.467,119.467-119.467
            S358.4,87.62,358.4,153.6v17.067H119.467V153.6z M392.533,443.733h-307.2V204.8h307.2V443.733z"/>
    </g>
</g>
<g>
    <g>
        <path d="M287.209,290.111c-7.211-20.472-26.571-34.152-48.276-34.111c-28.211-0.053-51.124,22.773-51.177,50.984
            c-0.041,21.705,13.639,41.065,34.111,48.276v37.274c0,9.426,7.641,17.067,17.067,17.067S256,401.959,256,392.533V355.26
            C282.609,345.888,296.582,316.719,287.209,290.111z M238.933,324.267c-9.426,0-17.067-7.641-17.067-17.067
            s7.641-17.067,17.067-17.067S256,297.774,256,307.2S248.359,324.267,238.933,324.267z"/>
    </g>
</g></svg>

              </span>
              <input id="password-confirm" type="password" class="form-control px-2 py-2 w-full text-sm inputAnimation focus:outline-none" name="password_confirmation" placeholder="" required>
               <label for="password-confirm" class="control-label text-sm">Confirm Password</label>
            </div>
          </div>
          </div>

          <div class="flex lg:items-center flex-col lg:flex-row">
            <div class="w-full py-1">
              <div class="form-check flex items-center">
                <input type="checkbox" class="form-check-input" name="termsandcondn" value="1" @if(old('termsandcondn')==1) checked @endif>
                <label for="termsandcondn" class="form-control px-2 py-2 w-full text-sm">I Agree to
                  <a href="{{ url('/terms-of-service') }}" target="_blank">
                    <b>Terms and Conditions</b>
                  </a>
                </label>
              </div>
              @if ($errors->has('termsandcondn'))
                <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
                  {{ $errors->first('termsandcondn') }}
                </span>
              @endif
            </div>
        </div>
        {{-- recaptcha quitted for now --}}
        {{-- <div class="flex lg:items-center flex-col lg:flex-row">
          <div class="g-recaptcha" id="feedback-recaptcha" name="feedback-recaptcha"
           data-sitekey="{{ env('GOOGLE_RECAPTCHA_KEY') }}"></div>
          @if ($errors->has('g-recaptcha-response'))
            <span class="invalid-feedback text-red-500 text-xs font-semibold" role="alert">
              <p>{{ $errors->first('g-recaptcha-response') }}</p>
            </span>
          @endif     
        </div> --}}
        <div class="form-group my-6">
            <div class="w-full z-40">
              <button type="submit" class="btn btn-primary bg-red-600 text-gray-200 uppercase px-8 py-1 tracking-wider w-full z-40">
                {{ __('Register') }}
              </button>
            </div>
          </div>
          <div class="">
            <p class="form-check-label text-center tracking-wider">Already have account
              <a href="{{ url('/login') }}" class="underline text-blue-500 mx-1">Sign in</a> here
            </p>
          </div>
        </div>
      </form>
    @endif
  </div>
</div>
</div>
{{-- <script src="https://www.google.com/recaptcha/api.js" async defer></script> --}}
@endsection

@push('scripts')
  
@endpush