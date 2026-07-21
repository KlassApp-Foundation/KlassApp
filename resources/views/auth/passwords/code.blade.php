{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@push('styles')
<style>
  *, *::before, *::after { box-sizing: border-box; }

  .klass-auth-wrap {
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

  .klass-auth-wrap::before {
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

  .klass-auth-inner {
    width: 100%;
    max-width: 420px;
    position: relative;
  }

  .klass-auth-intro {
    text-align: center;
    margin-bottom: 32px;
  }

  .klass-auth-logo-frame {
    display: block;
    margin: 0 auto 16px;
    width: 160px;
  }

  .klass-auth-logo { width: 100%; height: auto; display: block; }

  .klass-auth-title {
    margin: 0; font-family: 'Sora', sans-serif;
    font-size: 22px; font-weight: 700; color: #0F172A; letter-spacing: -0.02em;
  }

  .klass-auth-sub {
    margin: 6px 0 0; color: #64748B; font-size: 14px;
  }

  .klass-status {
    margin-top: 20px; border-radius: 10px; padding: 12px 16px;
    background: #F0FDF4; border: 1px solid #BBF7D0;
    color: #166534; font-size: 13px; font-weight: 600; line-height: 1.5;
  }

  .klass-field { margin-bottom: 16px; }
  .klass-label { display: block; margin-bottom: 6px; color: #0F172A; font-size: 13px; font-weight: 600; }

  .klass-input {
    width: 100%; background: #F8FAFC; border: 1.5px solid #E2E8F0;
    border-radius: 10px; padding: 12px 16px; font-size: 24px; color: #1E293B;
    outline: none; min-height: 56px; font-family: 'DM Sans', sans-serif;
    text-align: center; letter-spacing: 8px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
  }

  .klass-input:focus { border-color: #22C55E; box-shadow: 0 0 0 3px rgba(34,197,94,0.12); }
  .klass-input::placeholder { color: #94A3B8; letter-spacing: 2px; font-size: 16px; }
  .klass-input.is-invalid { border-color: #EF4444; }

  .klass-error {
    margin-top: 6px; display: block; color: #EF4444;
    font-size: 12px; line-height: 1.4; font-weight: 600;
  }

  .klass-submit {
    margin-top: 6px; width: 100%; border: 0; border-radius: 10px;
    background: #22C55E; color: #fff; font-size: 16px; font-weight: 700;
    padding: 14px; cursor: pointer; font-family: 'DM Sans', sans-serif;
    transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
  }

  .klass-submit:hover { background: #16A34A; transform: translateY(-1px); box-shadow: 0 10px 18px rgba(34,197,94,0.2); }
  .klass-submit:focus { outline: none; box-shadow: 0 0 0 3px rgba(34,197,94,0.18); }

  .klass-back-link {
    display: block; margin-top: 16px; text-align: center;
    color: #64748B; font-size: 14px; text-decoration: none; font-weight: 500;
    transition: color 0.15s ease;
  }
  .klass-back-link:hover { color: #22C55E; }

  @media (max-width: 640px) { .klass-auth-wrap { padding: 14px; } }
</style>
@endpush

@section('content')
<div class="klass-auth-wrap">
  <div class="klass-auth-inner">
    <div class="klass-auth-intro">
      <span class="klass-auth-logo-frame">
        <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-auth-logo" alt="KlassApp">
      </span>
      <h1 class="klass-auth-title">{{ __('Enter Reset Code') }}</h1>
      <p class="klass-auth-sub">Enter the 6-digit code sent to <strong>{{ $email }}</strong>.</p>
    </div>

    @if (session('status'))
      <div class="klass-status" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.reset.code.verify') }}">
      @csrf
      <input type="hidden" name="email" value="{{ $email }}">

      <div class="klass-field">
        <label class="klass-label" for="code">{{ __('Reset Code') }}</label>
        <input id="code" type="text" class="klass-input{{ $errors->has('code') ? ' is-invalid' : '' }}"
               name="code" value="{{ old('code') }}" placeholder="000000"
               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus>
        @if ($errors->has('code'))
          <span class="klass-error" role="alert">{{ $errors->first('code') }}</span>
        @endif
      </div>

      <button type="submit" class="klass-submit">{{ __('Verify Code') }}</button>
    </form>

    <form method="GET" action="{{ route('password.reset.code.resend') }}" style="margin-top: 16px; text-align: center;">
      <input type="hidden" name="email" value="{{ $email }}">
      <button type="submit" class="klass-back-link" style="border: none; background: none; cursor: pointer; display: inline; font-size: 14px; font-family: 'DM Sans', sans-serif;">
        {{ __("Didn't receive it? Resend code") }}
      </button>
    </form>
    <a href="{{ route('password.email') }}" class="klass-back-link" style="display: block; margin-top: 8px;">← Try a different email</a>
  </div>
</div>
@endsection
