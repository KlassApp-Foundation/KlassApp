{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.auth-preview')

@section('title', 'Verify Your Email')

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-ap-screen="verify-code">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => 'Check your inbox',
      'support' => 'Enter the 6-digit code we sent to finish signing up.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card">
        <h1 class="ap-title">{{ __('Verify Your Email') }}</h1>
        <p class="ap-sub">Enter the 6-digit code sent to <strong>{{ $email }}</strong>.</p>

        @if (session('status'))
          <div class="ap-alert ap-alert--success" role="status" data-testid="verify-code-status">{{ session('status') }}</div>
        @endif
        @if ($errors->has('code'))
          <div class="ap-alert ap-alert--error" role="alert" data-testid="auth-flash-error">{{ $errors->first('code') }}</div>
        @elseif ($errors->any())
          <div class="ap-alert ap-alert--error" role="alert" data-testid="auth-flash-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.verify.submit') }}" class="ap-form" data-testid="verify-code-form">
          @csrf

          <div class="ap-field">
            <label class="ap-label" for="code">{{ __('Verification Code') }}</label>
            <input id="code" type="text" class="ap-input ap-input--code{{ $errors->has('code') ? ' is-invalid' : '' }}"
                   name="code" value="{{ old('code') }}" placeholder="000000"
                   inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus
                   autocomplete="one-time-code" data-testid="verify-code-input">
            @if ($errors->has('code'))
              <span class="ap-error" role="alert">{{ $errors->first('code') }}</span>
            @endif
          </div>

          <p class="ap-sub" style="font-size: 13px;">This code expires in {{ $minutes }} minutes.</p>

          <button type="submit" class="ap-submit" data-testid="ap-primary-submit">{{ __('Verify Code') }}</button>
        </form>

        <form method="POST" action="{{ route('register.verify.resend') }}" style="margin-top: 8px;">
          @csrf
          <button type="submit" class="ap-back" data-testid="verify-code-resend">{{ __("Didn't receive it? Resend code") }}</button>
        </form>
        <a href="{{ route('register') }}" class="ap-back" style="margin-top: 8px;">← Start over</a>
      </div>
    </div>
  </div>
</div>
@endsection
