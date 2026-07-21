{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@push('styles')
<style>
  *, *::before, *::after { box-sizing: border-box; }

  .klass-otp-page {
    width: 100%;
    min-height: 100vh;
    display: grid;
    place-items: center;
    font-family: "DM Sans", sans-serif;
    background: #FAFAF5;
    position: relative;
    overflow: hidden;
    padding: 24px;
  }

  .klass-otp-page::before {
    content: "";
    position: absolute;
    inset: -20% -10%;
    background:
      radial-gradient(40% 35% at 15% 20%, rgba(34, 197, 94, 0.08) 0%, transparent 70%),
      radial-gradient(35% 30% at 85% 15%, rgba(30, 111, 217, 0.06) 0%, transparent 70%),
      radial-gradient(30% 25% at 75% 85%, rgba(34, 197, 94, 0.04) 0%, transparent 60%),
      radial-gradient(35% 30% at 20% 85%, rgba(30, 111, 217, 0.04) 0%, transparent 60%);
    pointer-events: none;
  }

  .klass-otp-shell {
    width: 100%;
    max-width: 420px;
    position: relative;
  }

  .klass-otp-intro {
    text-align: center;
    margin-bottom: 32px;
  }

  .klass-otp-logo-wrap {
    display: block;
    margin: 0 auto 16px;
    width: 160px;
  }

  .klass-otp-logo {
    width: 100%;
    height: auto;
    display: block;
  }

  .klass-otp-title {
    margin: 0;
    font-family: 'Sora', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #0F172A;
    letter-spacing: -0.02em;
  }

  .klass-otp-subtitle {
    margin: 6px 0 0;
    color: #64748B;
    font-size: 14px;
    line-height: 1.6;
  }

  .klass-otp-card {
    background: #FFFFFF;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.04);
    border: 1px solid #F1F5F9;
  }

  .klass-otp-icon {
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

  .klass-field { margin-bottom: 16px; }
  .klass-label { display: block; margin-bottom: 6px; color: #0F172A; font-size: 13px; font-weight: 600; }

  .klass-otp-input-group {
    display: flex;
    align-items: center;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    padding: 0 16px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
  }

  .klass-otp-input-group:focus-within {
    border-color: #22C55E;
    box-shadow: 0 0 0 3px rgba(34,197,94,0.12);
  }

  .klass-otp-icon-left {
    color: #94A3B8;
    display: inline-flex;
    margin-right: 10px;
    flex-shrink: 0;
  }

  .klass-otp-input {
    width: 100%;
    border: 0;
    outline: none;
    font-size: 15px;
    color: #1E293B;
    background: transparent;
    padding: 12px 0;
    font-family: 'DM Sans', sans-serif;
    min-height: 48px;
  }

  .klass-otp-input::placeholder { color: #94A3B8; }

  .klass-otp-verify-btn {
    margin-top: 6px;
    width: 100%;
    border: 0;
    border-radius: 10px;
    background: #22C55E;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    padding: 14px;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
  }

  .klass-otp-verify-btn:hover {
    background: #16A34A;
    transform: translateY(-1px);
    box-shadow: 0 10px 18px rgba(34,197,94,0.2);
  }

  .klass-otp-resend {
    text-align: center;
    margin-top: 20px;
    font-size: 13px;
    color: #64748B;
  }

  .klass-otp-resend-link {
    color: #22C55E;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: color 0.15s ease;
  }

  .klass-otp-resend-link:hover { color: #16A34A; text-decoration: underline; }
  .klass-otp-resend-link.is-disabled { color: #94A3B8; text-decoration: none; cursor: not-allowed; pointer-events: none; }

  @media (max-width: 640px) {
    .klass-otp-page { padding: 14px; }
    .klass-otp-card { padding: 24px 20px; }
  }
</style>
@endpush

@section('content')
<div class="klass-otp-page">
  <div class="klass-otp-shell">
    <div class="klass-otp-intro">
      <a href="{{ url('/') }}" class="klass-otp-logo-wrap" aria-label="KlassApp Home">
        <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-otp-logo" alt="KlassApp">
      </a>
      <h1 class="klass-otp-title">{{ __('Verify OTP') }}</h1>
      <p class="klass-otp-subtitle">Enter the verification code sent to {{ optional(auth()->user())->email }}.</p>
    </div>
    <div class="klass-otp-card">
      @include('admin.otp.create_form')
    </div>
  </div>
</div>
@endsection
