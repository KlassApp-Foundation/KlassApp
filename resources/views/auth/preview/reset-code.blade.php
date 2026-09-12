{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview restyle of auth/passwords/code.
     DEVIATION from mockup: keep REAL single input (pattern="[0-9]{6}" maxlength="6"),
     not mockup six discrete boxes — open decision, not settled for cutover. --}}
@extends('layouts.auth-preview')

@section('title', 'Enter Reset Code')

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-screen="reset-code">
  <div class="ap-card">
    <span class="ap-preview-badge">Preview</span>
    <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="ap-logo" alt="KlassApp">
    <h1 class="ap-title">{{ __('Enter Reset Code') }}</h1>
    <p class="ap-sub">Enter the 6-digit code sent to <strong>{{ $email }}</strong>.</p>

    @if (session('status'))
      <div class="ap-alert ap-alert--success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->has('code'))
      <div class="ap-alert ap-alert--error" role="alert" data-testid="auth-flash-error">{{ $errors->first('code') }}</div>
    @endif

    <form method="POST" action="{{ route('password.reset.code.verify') }}" class="ap-form" data-testid="preview-reset-code-form">
      @csrf
      <input type="hidden" name="email" value="{{ $email }}">

      <div class="ap-field">
        <label class="ap-label" for="code">{{ __('Reset Code') }}</label>
        <input id="code" type="text" class="ap-input ap-input--code{{ $errors->has('code') ? ' is-invalid' : '' }}"
               name="code" value="{{ old('code') }}" placeholder="000000"
               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus
               data-testid="reset-code-input">
        @if ($errors->has('code'))
          <span class="ap-error" role="alert">{{ $errors->first('code') }}</span>
        @endif
      </div>

      <button type="submit" class="ap-submit" data-testid="ap-primary-submit">{{ __('Verify Code') }}</button>
    </form>

    <form method="GET" action="{{ route('password.reset.code.resend') }}" style="margin-top: 8px;">
      <input type="hidden" name="email" value="{{ $email }}">
      <button type="submit" class="ap-back">{{ __("Didn't receive it? Resend code") }}</button>
    </form>
    <a href="{{ route('preview.reset-request') }}" class="ap-back" style="margin-top: 8px;">← Try a different email</a>
  </div>
</div>
@endsection
