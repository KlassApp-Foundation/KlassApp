{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview restyle of auth/passwords/reset. POST → password.reset --}}
@extends('layouts.auth-preview')

@section('title', 'Set New Password')

@section('content')
<div class="ap-page" data-ap-screen="reset-newpw">
  <div class="ap-card">
    <span class="ap-preview-badge">Preview</span>
    <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="ap-logo" alt="KlassApp">
    <h1 class="ap-title">{{ __('Set New Password') }}</h1>
    <p class="ap-sub">Choose a new password for your account.</p>

    @if ($errors->any())
      <div class="ap-alert ap-alert--error" role="alert" data-testid="auth-flash-error">
        @foreach ($errors->all() as $error)
          <p style="margin:0 0 4px;">{{ $error }}</p>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('password.reset') }}" class="ap-form" aria-label="{{ __('Reset Password') }}" data-testid="preview-reset-newpw-form">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">

      <div class="ap-field">
        <label class="ap-label" for="email">{{ __('E-Mail Address') }}</label>
        <input id="email" type="email" class="ap-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email ?? old('email') }}" placeholder="you@school.edu" required>
        @if ($errors->has('email'))
          <span class="ap-error" role="alert">{{ $errors->first('email') }}</span>
        @endif
      </div>

      <div class="ap-field">
        <label class="ap-label" for="password">{{ __('New Password') }}</label>
        <div class="ap-password-wrap">
          <input id="password" type="password" class="ap-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
          @include('auth.preview._password-toggle', ['target' => 'password'])
        </div>
        @if ($errors->has('password'))
          <span class="ap-error" role="alert">{{ $errors->first('password') }}</span>
        @endif
      </div>

      <div class="ap-field">
        <label class="ap-label" for="password-confirm">{{ __('Confirm New Password') }}</label>
        <div class="ap-password-wrap">
          <input id="password-confirm" type="password" class="ap-input" name="password_confirmation" required>
          @include('auth.preview._password-toggle', ['target' => 'password-confirm'])
        </div>
      </div>

      <button type="submit" class="ap-submit" data-testid="ap-primary-submit">{{ __('Reset Password') }}</button>
    </form>

    <a href="{{ route('preview.login') }}" class="ap-back">← Back to sign in</a>
  </div>
</div>
@endsection

@push('scripts')
@include('auth.preview._toggle-script')
@endpush
