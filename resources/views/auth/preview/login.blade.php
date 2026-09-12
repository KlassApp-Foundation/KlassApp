{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview restyle of auth/login. Forms POST to real /login. --}}
@extends('layouts.auth-preview')

@section('title', 'Sign in')

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-screen="login">
  <div class="ap-card">
    <span class="ap-preview-badge">Preview</span>
    <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="ap-logo" alt="KlassApp">
    <h1 class="ap-title">Welcome back</h1>
    <p class="ap-sub">Sign in to your school dashboard.</p>

    @if(\Config::get('settings.login_status', 1) == 0)
      <div class="ap-maintenance">Login page is under maintenance</div>
    @else
      @if (session('failmessage') || ! empty($failmessage))
        <div class="ap-alert ap-alert--error" role="alert" data-testid="auth-flash-error">{{ session('failmessage') ?? $failmessage }}</div>
      @endif
      @if (session('successmessage'))
        <div class="ap-alert ap-alert--success" role="status" data-testid="auth-flash-success">{{ session('successmessage') }}</div>
      @endif

      <form method="POST" action="/login" class="ap-form" aria-label="{{ __('Login') }}" data-testid="preview-login-form">
        @csrf
        <div class="ap-field">
          <label class="ap-label" for="email">Email, Phone, Name, or Registration Number</label>
          <input id="email" type="text" class="ap-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="your@school.edu" required>
          @if ($errors->has('email'))
            <span class="ap-error" role="alert" data-testid="login-email-error">{{ $errors->first('email') }}</span>
          @endif
        </div>
        <div class="ap-field">
          <label class="ap-label" for="password">Password</label>
          <div class="ap-password-wrap">
            <input id="password" type="password" class="ap-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
            @include('auth.preview._password-toggle', ['target' => 'password'])
          </div>
          @if ($errors->has('password'))
            <span class="ap-error" role="alert">{{ $errors->first('password') }}</span>
          @endif
        </div>
        <div class="ap-actions-row">
          <div class="ap-checkbox-row">
            <input id="remember" type="checkbox" class="ap-checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <label for="remember" class="ap-checkbox-label">Remember me</label>
          </div>
          <a href="{{ route('preview.reset-request') }}" class="ap-link">Forgot password?</a>
        </div>
        <button type="submit" class="ap-submit" data-testid="ap-primary-submit">Sign in</button>
      </form>

      <div class="ap-divider">or</div>
      {{-- Login Google: GET anchor to /auth/google (asymmetric vs register POST) --}}
      <a href="{{ url('/auth/google') }}" class="ap-google" data-testid="login-google">
        @include('auth.preview._google-icon')
        Sign in with Google
      </a>

      <div class="ap-meta">
        <p>Don't have an account? <a href="{{ route('preview.register') }}">Sign up</a></p>
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
@include('auth.preview._toggle-script')
@endpush
