{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@push('styles')
<style>
  .klass-login-page {
    width: 100%;
    min-height: 100vh;
    display: grid;
    place-items: center;
    font-family: "Plus Jakarta Sans", sans-serif;
    background: #0D1526;
    position: relative;
    overflow: hidden;
    padding: 24px;
  }

  .klass-login-page::before {
    content: "";
    position: relative;
    position: absolute;
    inset: -12% -8%;
    background:
      radial-gradient(40% 30% at 18% 20%, rgba(30, 111, 217, 0.12) 0%, rgba(30, 111, 217, 0) 78%),
      radial-gradient(32% 28% at 82% 18%, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0) 76%),
      radial-gradient(36% 28% at 70% 82%, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0) 74%),
      radial-gradient(38% 30% at 20% 88%, rgba(255, 255, 255, 0.06) 0%, rgba(255, 255, 255, 0) 72%),
      repeating-linear-gradient(150deg, rgba(255, 255, 255, 0.03) 0 1px, rgba(255, 255, 255, 0) 1px 30px);
    pointer-events: none;
    opacity: 0.32;
  }

  .klass-login-page::after {
    content: "";
    position: absolute;
    inset: auto -18% -32% -18%;
    height: 66%;
    background:
      radial-gradient(46% 46% at 15% 90%, rgba(30, 111, 217, 0.14) 0%, rgba(30, 111, 217, 0) 76%),
      radial-gradient(44% 44% at 83% 72%, rgba(34, 197, 94, 0.11) 0%, rgba(34, 197, 94, 0) 74%),
      repeating-linear-gradient(145deg, rgba(255, 255, 255, 0.028) 0 1px, rgba(255, 255, 255, 0) 1px 34px);
    opacity: 0.28;
    pointer-events: none;
  }

  .klass-login-card {
    width: 100%;
    max-width: 440px;
    background: #EEF1F5;
    border-radius: 16px;
    box-shadow: 0 28px 60px rgba(5, 11, 27, 0.45), 0 8px 18px rgba(5, 11, 27, 0.28);
    padding: 48px;
    position: relative;
    z-index: 1;
  }

  .klass-login-intro {
    text-align: center;
  }

  .klass-login-logo-frame {
    width: 180px;
    margin: 0 auto;
    display: block;
  }

  .klass-login-logo {
    width: 100%;
    height: auto;
    display: block;
  }

  .klass-intro-title {
    margin: 14px 0 0;
    color: #0d1526;
    font-size: 24px;
    font-weight: 700;
    line-height: 1.4;
  }

  .klass-intro-sub {
    margin: 8px 0 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
  }

  .klass-intro-divider {
    width: 100%;
    height: 1px;
    margin: 18px 0 0;
    background: #d7dee9;
  }

  .klass-maintenance {
    margin-top: 24px;
    border: 1px solid rgba(30, 111, 217, 0.28);
    background: rgba(30, 111, 217, 0.08);
    color: #0d1526;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 14px;
    font-weight: 600;
  }

  .klass-form {
    margin-top: 22px;
  }

  .klass-field {
    margin-bottom: 14px;
  }

  .klass-label {
    display: block;
    margin-bottom: 7px;
    color: #0d1526;
    font-size: 13px;
    font-weight: 600;
  }

  .klass-input {
    width: 100%;
    background: #F6F8F8;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 15px;
    color: #0d1526;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
    outline: none;
    min-height: 48px;
  }

  .klass-input:focus {
    border-color: #1e6fd9;
    box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.12);
  }

  .klass-input::placeholder {
    color: #94a3b8;
  }

  .klass-password-wrap {
    position: relative;
  }

  .klass-password-wrap .klass-input {
    padding-right: 46px;
  }

  .klass-password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    width: 26px;
    height: 26px;
    color: #1e6fd9;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
  }

  .klass-password-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(30, 111, 217, 0.2);
    border-radius: 6px;
  }

  .klass-password-toggle svg {
    width: 20px;
    height: 20px;
  }

  .klass-actions-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 14px;
    margin-bottom: 18px;
  }

  .klass-checkbox-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
  }

  .klass-checkbox {
    margin-top: 2px;
    appearance: none;
    width: 18px;
    height: 18px;
    border: 1.5px solid #94a3b8;
    border-radius: 5px;
    background: #ffffff;
    position: relative;
    cursor: pointer;
    flex: 0 0 auto;
  }

  .klass-checkbox:checked {
    background: #22c55e;
    border-color: #22c55e;
  }

  .klass-checkbox:checked::after {
    content: "";
    position: absolute;
    left: 5px;
    top: 1px;
    width: 5px;
    height: 10px;
    border: solid #0d1526;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
  }

  .klass-checkbox-label {
    color: #334155;
    font-size: 13px;
    line-height: 1.5;
  }

  .klass-forgot-link {
    color: #1e6fd9;
    font-size: 13px;
    text-decoration: none;
    font-weight: 500;
  }

  .klass-forgot-link:hover {
    text-decoration: underline;
  }

  .klass-error {
    margin-top: 6px;
    display: block;
    color: #dc2626;
    font-size: 12px;
    line-height: 1.4;
    font-weight: 600;
  }

  .klass-submit {
    margin-top: 6px;
    width: 100%;
    border: 0;
    border-radius: 8px;
    background: #1E6FD9;
    color: #ffffff;
    font-size: 16px;
    font-weight: 700;
    padding: 14px;
    cursor: pointer;
    transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
  }

  .klass-submit:hover {
    background: #1a5fc4;
    transform: translateY(-1px);
    box-shadow: 0 10px 18px rgba(30, 111, 217, 0.2);
  }

  .klass-submit:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.18);
  }

  .klass-divider {
    display: flex;
    align-items: center;
    gap: 14px;
    margin: 18px 0 14px;
    color: #9ca3af;
    font-size: 13px;
    font-weight: 500;
  }

  .klass-divider::before,
  .klass-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
  }

  .klass-google-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background: #ffffff;
    color: #1f2937;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    cursor: pointer;
  }

  .klass-google-btn:hover {
    background: #f9fafb;
    box-shadow: 0 8px 18px rgba(0,0,0,0.04);
    transform: translateY(-1px);
  }

  .klass-google-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.18);
  }

  .klass-meta {
    margin-top: 14px;
    text-align: center;
  }

  .klass-meta-lock {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #64748b;
    font-size: 12px;
    font-weight: 500;
  }

  .klass-meta-register {
    margin-top: 9px;
    color: #334155;
    font-size: 14px;
  }

  .klass-meta-register a {
    color: #22c55e;
    font-weight: 600;
    text-decoration: none;
  }

  .klass-meta-register a:hover {
    text-decoration: underline;
  }

  .klass-errors-all {
    margin-top: 16px;
    padding: 12px 14px;
    border: 1px solid rgba(220, 38, 38, 0.18);
    border-radius: 10px;
    background: #fef2f2;
    color: #991b1b;
    font-size: 12px;
    line-height: 1.5;
  }

  .klass-errors-all p {
    margin: 0;
  }

  @media (max-width: 767px) {
    .klass-login-page {
      padding: 14px;
    }

    .klass-login-card {
      padding: 28px;
    }

    .klass-actions-row {
      flex-direction: column;
      gap: 12px;
      align-items: stretch;
    }

    .klass-checkbox-row {
      width: 100%;
    }

    .klass-forgot-link {
      display: block;
      text-align: center;
    }
  }
</style>
@endpush

@section('content')

<div class="klass-login-page">
  <div class="klass-login-card">
    <div class="klass-login-intro">
      <span class="klass-login-logo-frame">
        <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-login-logo" alt="KlassApp">
      </span>
      <p class="klass-intro-title">Welcome back</p>
      <p class="klass-intro-sub">Sign in to your school dashboard.</p>
      <div class="klass-intro-divider"></div>
    </div>

    @if(\Config::get('settings.login_status', 1) == 0)
      <div class="klass-maintenance">
        Login page is under maintenance
      </div>
    @else
      <form method="POST" action="/login" aria-label="{{ __('Login') }}" class="klass-form">
        @csrf

        <div class="klass-field">
          <label class="klass-label" for="email">Email, Phone, Name, or Registration Number</label>
          <input id="email" type="text" class="klass-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="your@school.edu, phone, name, or registration number" required>
          @if ($errors->has('email'))
            <span class="klass-error" role="alert">{{ $errors->first('email') }}</span>
          @endif
        </div>

        <div class="klass-field">
          <label class="klass-label" for="password">Password</label>
          <div class="klass-password-wrap">
            <input id="password" type="password" class="klass-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
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

        <div class="klass-actions-row">
          <div class="klass-checkbox-row">
            <input id="remember" type="checkbox" class="klass-checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <label for="remember" class="klass-checkbox-label">
              Remember me
            </label>
          </div>
          <a href="{{ route('password.request') }}" class="klass-forgot-link">Forgot your password?</a>
        </div>

        <button type="submit" class="klass-submit">Sign in to KlassApp</button>

        <div class="klass-divider">
          <span>or</span>
        </div>

        <a href="{{ url('/auth/google') }}" class="klass-google-btn">
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          {{ __('Continue with Google') }}
        </a>

        <div class="klass-meta">
          <p class="klass-meta-lock">
            <svg viewBox="0 0 20 20" width="14" height="14" fill="none" aria-hidden="true">
              <rect x="4" y="9" width="12" height="8" rx="2" stroke="currentColor" stroke-width="1.6"></rect>
              <path d="M7 9V7.2C7 5.43 8.34 4 10 4C11.66 4 13 5.43 13 7.2V9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
            </svg>
            Your data is encrypted and secure.
          </p>
          <p class="klass-meta-register">New to KlassApp? <a href="{{ url('/register') }}">Register your school for free.</a></p>
        </div>

        @if ($errors->any())
          <div class="klass-errors-all">
            @foreach ($errors->all() as $error)
              <p>{{ $error }}</p>
            @endforeach
          </div>
        @endif
      </form>
    @endif
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.klass-password-toggle');

    toggleButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();

        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);

        if (!input) {
          return;
        }

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
