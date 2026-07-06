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
    background: rgba(30, 111, 217, 0.1);
    color: #1E6FD9;
  }

  .klass-field {
    margin-top: 20px;
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

  .klass-error {
    margin-top: 6px;
    display: block;
    color: #EF4444;
    font-size: 12px;
    line-height: 1.4;
    font-weight: 600;
  }

  .klass-status {
    margin-top: 16px;
    border-radius: 10px;
    padding: 12px 16px;
    background: #F0FDF4;
    border: 1px solid #BBF7D0;
    color: #166534;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
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

  .klass-back-link {
    display: block;
    margin-top: 16px;
    text-align: center;
    color: #64748B;
    font-size: 14px;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.15s ease;
  }

  .klass-back-link:hover {
    color: #22C55E;
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
          <rect x="2" y="4" width="20" height="16" rx="2"></rect>
          <path d="M22 7l-10 7L2 7"></path>
        </svg>
      </div>
      <span class="klass-auth-logo-frame">
        <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-auth-logo" alt="KlassApp">
      </span>
      <h1 class="klass-auth-title">{{ __('Reset Password') }}</h1>
      <p class="klass-auth-sub">Enter your email and we will send you a password reset link.</p>
    </div>

    @if (session('status'))
      <div class="klass-status" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" aria-label="{{ __('Reset Password') }}">
      @csrf

      <div class="klass-field">
        <label for="email" class="klass-label">{{ __('E-Mail Address') }}</label>
        <input id="email" type="email" class="klass-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required placeholder="you@school.edu">
        @if ($errors->has('email'))
          <span class="klass-error" role="alert">{{ $errors->first('email') }}</span>
        @endif
      </div>

      <button type="submit" class="klass-submit">{{ __('Send Password Reset Link') }}</button>
    </form>

    <a href="{{ route('login') }}" class="klass-back-link">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Back to sign in
    </a>
  </div>
</div>

@endsection
