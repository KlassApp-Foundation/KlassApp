{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview restyle of auth/register. Forms POST to real register / auth.google.start. --}}
@extends('layouts.auth-preview')

@section('title', 'Register')

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-screen="register">
  <div class="ap-card ap-card--wide">
    <span class="ap-preview-badge">Preview</span>
    <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="ap-logo" alt="KlassApp">
    <h1 class="ap-title">Create your KlassApp account</h1>
    <p class="ap-sub">Name, email, and WhatsApp: then finish school setup with Toshi.</p>

    @if(\Config::get('settings.register')==1)
      <div class="ap-maintenance">Register page is under maintenance!!!</div>
    @else
      @if ($errors->any())
        <div class="ap-alert ap-alert--error" role="alert" data-testid="auth-flash-error">
          @foreach ($errors->all() as $error)
            <p style="margin:0 0 4px;">{{ $error }}</p>
          @endforeach
        </div>
      @endif
      @if (session('failmessage'))
        <div class="ap-alert ap-alert--error" role="alert">{{ session('failmessage') }}</div>
      @endif

      <form method="POST" action="{{ route('register') }}" class="ap-form" id="saas-register-form" aria-label="{{ __('Register') }}" data-testid="preview-register-form">
        @csrf

        <div class="ap-field">
          <label class="ap-label" for="name">Your Full Name</label>
          <input id="name" type="text" class="ap-input{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" placeholder="Grace Nakato" required autocomplete="name">
          @if ($errors->has('name'))
            <span class="ap-error" role="alert">{{ $errors->first('name') }}</span>
          @endif
        </div>

        <div class="ap-field">
          <label class="ap-label" for="email">Email Address</label>
          <input id="email" type="email" class="ap-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="you@school.ug" required autocomplete="email">
          @if ($errors->has('email'))
            <span class="ap-error" role="alert">{{ $errors->first('email') }}</span>
          @endif
        </div>

        <div class="ap-field">
          <label class="ap-label" for="phone">Phone (WhatsApp)</label>
          <input id="phone" type="tel" class="ap-input{{ $errors->has('phone') ? ' is-invalid' : '' }}" name="phone" value="{{ old('phone') }}" placeholder="0701234567 or +256701234567" required autocomplete="tel">
          @if ($errors->has('phone'))
            <span class="ap-error" role="alert">{{ $errors->first('phone') }}</span>
          @endif
        </div>

        <div class="ap-field">
          <label class="ap-label" for="password">Password</label>
          <div class="ap-password-wrap">
            <input id="password" type="password" class="ap-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" autocomplete="new-password">
            @include('auth.preview._password-toggle', ['target' => 'password'])
          </div>
          @if ($errors->has('password'))
            <span class="ap-error" role="alert">{{ $errors->first('password') }}</span>
          @endif
        </div>

        <div class="ap-field">
          <label class="ap-label" for="password-confirm">Confirm Password</label>
          <div class="ap-password-wrap">
            <input id="password-confirm" type="password" class="ap-input" name="password_confirmation" autocomplete="new-password">
            @include('auth.preview._password-toggle', ['target' => 'password-confirm'])
          </div>
        </div>

        <div class="ap-field ap-checkbox-row">
          <input id="termsandcondn" type="checkbox" class="ap-checkbox" name="termsandcondn" value="1" @if(old('termsandcondn')==1) checked @endif required>
          <label for="termsandcondn" class="ap-checkbox-label">
            I agree to <a href="{{ url('/terms-of-service') }}" target="_blank">Terms and Conditions</a>
          </label>
        </div>
        @if ($errors->has('termsandcondn'))
          <span class="ap-error" role="alert">{{ $errors->first('termsandcondn') }}</span>
        @endif

        <button type="submit" class="ap-submit" data-testid="ap-primary-submit">Create account with password</button>

        <div class="ap-divider"><span>or</span></div>

        {{-- Register Google: POST to auth.google.start with formnovalidate + formaction --}}
        <button type="submit" class="ap-google" formaction="{{ route('auth.google.start') }}" formmethod="post" formnovalidate data-testid="register-google">
          @include('auth.preview._google-icon')
          Continue with Google
        </button>

        <div class="ap-meta">
          Already have an account? <a href="{{ route('preview.login') }}">Sign in</a>
        </div>
      </form>
    @endif
  </div>
</div>
@endsection

@push('scripts')
@include('auth.preview._toggle-script')
<script>
(function () {
  var form = document.getElementById('saas-register-form');
  if (!form) return;
  form.addEventListener('submit', function (e) {
    var submitter = e.submitter;
    if (!submitter) return;
    var action = submitter.getAttribute('formaction') || '';
    if (action.indexOf('google') === -1) return;
    var password = document.getElementById('password');
    var confirm = document.getElementById('password-confirm');
    if (password) { password.removeAttribute('required'); password.value = password.value || ''; }
    if (confirm) { confirm.removeAttribute('required'); }
  });
})();
</script>
@endpush
