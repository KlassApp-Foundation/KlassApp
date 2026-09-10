{{-- SPDX-License-Identifier: MIT --}}
{{-- Preview restyle of auth/force-change-password.
     Security: 5 Password::min(8)->mixedCase()->numbers()->symbols() rules;
     NO skip / cancel / sign-out escape hatch. Preview GET bypasses auth middleware;
     form still POSTs to real password.force-change. --}}
@extends('layouts.auth-preview')

@section('title', 'Update password')

@section('content')
<div class="ap-page" data-ap-screen="force-change-password">
  <div class="ap-card">
    <span class="ap-preview-badge">Preview</span>
    <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="ap-logo" alt="KlassApp">
    <h1 class="ap-title">Update your password</h1>
    <p class="ap-sub">Your account was created with a temporary password. Please set a new one to continue.</p>

    @if(session('successmessage'))
      <div class="ap-alert ap-alert--error" role="alert">{{ session('successmessage') }}</div>
    @endif
    @if ($errors->any())
      <div class="ap-alert ap-alert--error" role="alert" data-testid="auth-flash-error">
        @foreach ($errors->all() as $error)
          <p style="margin:0 0 4px;">{{ $error }}</p>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('password.force-change') }}" class="ap-form" data-testid="preview-force-change-form">
      @csrf

      <p class="ap-requirements-title">Password requirements</p>
      <ul class="ap-requirements" data-testid="password-requirements">
        <li>At least 8 characters</li>
        <li>One uppercase letter</li>
        <li>One lowercase letter</li>
        <li>One number</li>
        <li>One special character</li>
      </ul>

      <div class="ap-field">
        <label class="ap-label" for="current_password">Current password</label>
        <div class="ap-password-wrap">
          <input id="current_password" type="password" class="ap-input{{ $errors->has('current_password') ? ' is-invalid' : '' }}" name="current_password" required>
          @include('auth.preview._password-toggle', ['target' => 'current_password'])
        </div>
        @error('current_password')
          <span class="ap-error" role="alert">{{ $message }}</span>
        @enderror
      </div>

      <div class="ap-field">
        <label class="ap-label" for="password">New password</label>
        <div class="ap-password-wrap">
          <input id="password" type="password" class="ap-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
          @include('auth.preview._password-toggle', ['target' => 'password'])
        </div>
        @error('password')
          <span class="ap-error" role="alert">{{ $message }}</span>
        @enderror
      </div>

      <div class="ap-field">
        <label class="ap-label" for="password_confirmation">Confirm new password</label>
        <div class="ap-password-wrap">
          <input id="password_confirmation" type="password" class="ap-input" name="password_confirmation" required>
          @include('auth.preview._password-toggle', ['target' => 'password_confirmation'])
        </div>
      </div>

      <button type="submit" class="ap-submit" data-testid="ap-primary-submit">Update password</button>
    </form>
    {{-- Intentionally no skip / cancel / sign-out link --}}
  </div>
</div>
@endsection

@push('scripts')
@include('auth.preview._toggle-script')
@endpush
