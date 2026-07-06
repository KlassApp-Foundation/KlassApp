{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@push('styles')
<style>
  *, *::before, *::after { box-sizing: border-box; }

  .klass-auth-page {
    width: 100%;
    min-height: 100vh;
    display: grid;
    place-items: center;
    font-family: "DM Sans", sans-serif;
    background: #0F172A;
    position: relative;
    overflow: hidden;
    padding: 24px;
  }

  .klass-auth-page::before {
    content: "";
    position: absolute;
    inset: -20% -10%;
    background:
      radial-gradient(40% 35% at 15% 20%, rgba(34, 197, 94, 0.08) 0%, transparent 70%),
      radial-gradient(35% 30% at 85% 15%, rgba(30, 111, 217, 0.06) 0%, transparent 70%);
    pointer-events: none;
  }

  .klass-auth-card {
    width: 100%;
    max-width: 440px;
    background: #FFFFFF;
    border-radius: 20px;
    box-shadow: 0 32px 80px rgba(0, 0, 0, 0.5), 0 4px 16px rgba(0, 0, 0, 0.2);
    padding: 48px 40px 40px;
    position: relative;
    z-index: 1;
  }

  .klass-auth-intro {
    text-align: center;
  }

  .klass-auth-logo-frame {
    width: 160px;
    margin: 0 auto 4px;
    display: block;
  }

  .klass-auth-logo {
    width: 100%;
    height: auto;
    display: block;
  }

  .klass-auth-title {
    margin: 16px 0 0;
    color: #0F172A;
    font-size: 22px;
    font-weight: 700;
    line-height: 1.3;
    font-family: "Sora", sans-serif;
    letter-spacing: -0.02em;
  }

  .klass-auth-sub {
    margin: 8px 0 0;
    color: #64748B;
    font-size: 14px;
    line-height: 1.5;
  }

  .klass-auth-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(34, 197, 94, 0.1);
    color: #22C55E;
  }

  .klass-field {
    margin-top: 16px;
  }

  .klass-field:first-of-type {
    margin-top: 22px;
  }

  .klass-label {
    display: block;
    margin-bottom: 6px;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
  }

  .klass-input {
    width: 100%;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    padding: 13px 16px;
    font-size: 15px;
    color: #0F172A;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
    outline: none;
    min-height: 50px;
    font-family: "DM Sans", sans-serif;
  }

  .klass-input:focus {
    border-color: #22C55E;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
  }

  .klass-input::placeholder {
    color: #94A3B8;
  }

  .klass-input.is-invalid {
    border-color: #EF4444;
  }

  .klass-input.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
  }

  .klass-password-wrap {
    position: relative;
  }

  .klass-password-wrap .klass-input {
    padding-right: 48px;
  }

  .klass-password-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    width: 26px;
    height: 26px;
    color: #64748B;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: color 0.15s ease;
  }

  .klass-password-toggle:hover {
    color: #22C55E;
  }

  .klass-password-toggle:focus {
    outline: none;
    color: #22C55E;
  }

  .klass-password-toggle svg {
    width: 20px;
    height: 20px;
  }

  .klass-error {
    margin-top: 6px;
    display: block;
    color: #EF4444;
    font-size: 12px;
    line-height: 1.4;
    font-weight: 600;
  }

  .klass-submit {
    margin-top: 20px;
    width: 100%;
    border: 0;
    border-radius: 10px;
    background: #22C55E;
    color: #052E16;
    font-size: 16px;
    font-weight: 700;
    padding: 14px 20px;
    cursor: pointer;
    transition: all 0.18s ease;
    font-family: "DM Sans", sans-serif;
  }

  .klass-submit:hover {
    background: #16A34A;
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(34, 197, 94, 0.25);
  }

  .klass-submit:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.3);
  }

  .klass-submit:active {
    transform: scale(0.98);
  }

  @media (max-width: 640px) {
    .klass-auth-page { padding: 14px; }
    .klass-auth-card { padding: 32px 24px; }
  }
</style>
@endpush

@section('content')

<div class="klass-auth-page">
  <div class="klass-auth-card">
    <div class="klass-auth-intro">
      <div class="klass-auth-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0110 0v4"></path>
        </svg>
      </div>
      <span class="klass-auth-logo-frame">
        <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-auth-logo" alt="KlassApp">
      </span>
      <h1 class="klass-auth-title">{{ __('Create New Password') }}</h1>
      <p class="klass-auth-sub">Set a secure password to complete your account recovery.</p>
    </div>

    <form method="POST" action="{{ route('password.reset') }}" aria-label="{{ __('Reset Password') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">

      <div class="klass-field">
        <label for="email" class="klass-label">{{ __('E-Mail Address') }}</label>
        <input id="email" type="email" class="klass-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email ?? old('email') }}" required autofocus placeholder="you@school.edu">
        @if ($errors->has('email'))
          <span class="klass-error" role="alert">{{ $errors->first('email') }}</span>
        @endif
      </div>

      <div class="klass-field">
        <label for="password" class="klass-label">{{ __('Password') }}</label>
        <div class="klass-password-wrap">
          <input id="password" type="password" class="klass-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required placeholder="At least 8 characters">
          <button class="klass-password-toggle" type="button" data-target="password" aria-label="Show or hide password">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"></path>
              <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"></circle>
            </svg>
          </button>
        </div>
        @if ($errors->has('password'))
          <span class="klass-error" role="alert">{{ $errors->first('password') }}</span>
        @endif
      </div>

      <div class="klass-field">
        <label for="password-confirm" class="klass-label">{{ __('Confirm Password') }}</label>
        <div class="klass-password-wrap">
          <input id="password-confirm" type="password" class="klass-input" name="password_confirmation" required placeholder="Re-enter your password">
          <button class="klass-password-toggle" type="button" data-target="password-confirm" aria-label="Show or hide confirm password">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"></path>
              <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"></circle>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="klass-submit">{{ __('Reset Password') }}</button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var toggles = document.querySelectorAll('.klass-password-toggle');
  toggles.forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      var targetId = this.getAttribute('data-target');
      var input = document.getElementById(targetId);
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        this.innerHTML = '<svg viewBox="0 0 24 24" fill="none"><path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
      } else {
        input.type = 'password';
        this.innerHTML = '<svg viewBox="0 0 24 24" fill="none"><path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"></path><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"></circle></svg>';
      }
    });
  });
});
</script>

@endsection
