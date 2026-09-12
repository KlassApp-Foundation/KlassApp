{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview restyle of auth/passwords/email. POST → password.email --}}
@extends('layouts.auth-preview')

@section('title', 'Reset Password')

@section('content')
<div class="ap-page" data-ap-screen="reset-request">
  <div class="ap-card">
    <span class="ap-preview-badge">Preview</span>
    <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="ap-logo" alt="KlassApp">
    <h1 class="ap-title">{{ __('Reset Password') }}</h1>
    <p class="ap-sub">Enter your email and we will send you a password reset code.</p>

    @if (session('status'))
      <div class="ap-alert ap-alert--success" role="status">{{ session('status') }}</div>
      <a href="{{ route('preview.login') }}" class="ap-back">← Back to sign in</a>
    @else
      @if ($errors->has('email'))
        <div class="ap-alert ap-alert--error" role="alert" data-testid="auth-flash-error">{{ $errors->first('email') }}</div>
      @endif

      <form method="POST" action="{{ route('password.email') }}" class="ap-form" aria-label="{{ __('Reset Password') }}" data-testid="preview-reset-request-form">
        @csrf
        <div class="ap-field">
          <label class="ap-label" for="email">{{ __('E-Mail Address') }}</label>
          <input id="email" type="email" class="ap-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="you@school.edu" required>
          @if ($errors->has('email'))
            <span class="ap-error" role="alert">{{ $errors->first('email') }}</span>
          @endif
        </div>
        <button type="submit" class="ap-submit" data-testid="ap-primary-submit">{{ __('Send Reset Code') }}</button>
      </form>
      <a href="{{ route('preview.login') }}" class="ap-back">← Back to sign in</a>
    @endif
  </div>
</div>
@endsection
