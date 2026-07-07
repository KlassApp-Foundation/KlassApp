{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@push('styles')
<style>
  *, *::before, *::after { box-sizing: border-box; }

  .klass-auth-page {
    width: 100%;
    min-height: 100vh;
    font-family: "DM Sans", sans-serif;
    background: #FAFAF5;
    padding: 48px 24px;
    max-width: 440px;
    margin: 0 auto;
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

  .klass-auth-text {
    margin: 10px 0 0;
    color: #64748B;
    font-size: 14px;
    line-height: 1.7;
    text-align: center;
  }

  .klass-auth-text + .klass-auth-text {
    margin-top: 6px;
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

  .klass-link {
    color: #22C55E;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.15s ease;
  }

  .klass-link:hover {
    color: #16A34A;
    text-decoration: underline;
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

  @media (max-width: 640px) {
    .klass-auth-page { padding: 14px; }
    .klass-auth-card { padding: 32px 24px; }
  }
</style>
@endpush

@section('content')

<div class="klass-auth-page">
  <div class="klass-auth-intro">
    <div class="klass-auth-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"></path>
      </svg>
    </div>
    <span class="klass-auth-logo-frame">
      <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-auth-logo" alt="KlassApp">
    </span>
    <h1 class="klass-auth-title">{{ __('Verify Your Email Address') }}</h1>

    @if (session('resent'))
      <div class="klass-status" role="status">
        {{ __('A fresh verification link has been sent to your email address.') }}
      </div>
    @endif

    <p class="klass-auth-text">
      {{ __('Before proceeding, please check your email for a verification link.') }}
    </p>
    <p class="klass-auth-text">
      {{ __('If you did not receive the email, please contact your school administrator to request a new verification link.') }}
    </p>
  </div>
</div>

@endsection
