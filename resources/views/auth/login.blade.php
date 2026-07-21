{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@push('styles')
<style>
  *, *::before, *::after { box-sizing: border-box; }
  .klass-login-form { width: 100%; max-width: 420px; }
  .klass-login-intro { text-align: center; margin-bottom: 32px; }
  .klass-login-logo-frame { display: block; margin: 0 auto 16px; width: 160px; }
  .klass-login-logo { width: 100%; height: auto; display: block; }
  .klass-intro-title {
    margin: 0; font-family: 'Sora', sans-serif;
    font-size: 22px; font-weight: 700; color: #0F172A; letter-spacing: -0.02em;
  }
  .klass-intro-sub { margin: 6px 0 0; color: #64748B; font-size: 14px; }
  .klass-maintenance {
    margin-top: 20px; padding: 12px 16px; border-radius: 10px;
    background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; font-size: 14px; font-weight: 600;
  }
  .klass-field { margin-bottom: 16px; }
  .klass-label { display: block; margin-bottom: 6px; color: #0F172A; font-size: 13px; font-weight: 600; }
  .klass-input {
    width: 100%; background: #F8FAFC; border: 1.5px solid #E2E8F0;
    border-radius: 10px; padding: 12px 16px; font-size: 15px; color: #1E293B;
    outline: none; min-height: 48px; font-family: 'DM Sans', sans-serif;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
  }
  .klass-input:focus { border-color: #22C55E; box-shadow: 0 0 0 3px rgba(34,197,94,0.12); }
  .klass-input::placeholder { color: #94A3B8; }
  .klass-password-wrap { position: relative; }
  .klass-password-wrap .klass-input { padding-right: 46px; }
  .klass-password-toggle {
    position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
    width: 44px; height: 44px; border-radius: 8px; border: none;
    background: transparent; cursor: pointer; display: flex;
    align-items: center; justify-content: center; color: #94A3B8;
    transition: color 0.15s ease;
  }
  .klass-password-toggle:hover { color: #0F172A; }
  .klass-password-toggle:focus { outline: none; box-shadow: 0 0 0 3px rgba(34,197,94,0.12); }
  .klass-password-toggle svg { width: 20px; height: 20px; }
  .klass-actions-row { display: flex; align-items: center; justify-content: space-between; margin-top: 20px; }
  .klass-checkbox-row { display: flex; align-items: center; gap: 8px; }
  .klass-checkbox { width: 16px; height: 16px; accent-color: #22C55E; }
  .klass-checkbox-label { font-size: 13px; color: #0F172A; cursor: pointer; }
  .klass-forgot-link { font-size: 13px; font-weight: 600; color: #22C55E; text-decoration: none; min-height: 44px; display: inline-flex; align-items: center; }
  .klass-forgot-link:hover { text-decoration: underline; }
  .klass-submit {
    margin-top: 6px; width: 100%; border: 0; border-radius: 10px;
    background: #22C55E; color: #fff; font-size: 16px; font-weight: 700;
    padding: 14px; cursor: pointer; font-family: 'DM Sans', sans-serif;
    transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
  }
  .klass-submit:hover { background: #16A34A; transform: translateY(-1px); box-shadow: 0 10px 18px rgba(34,197,94,0.2); }
  .klass-submit:focus { outline: none; box-shadow: 0 0 0 3px rgba(34,197,94,0.18); }
  .klass-divider {
    display: flex; align-items: center; gap: 14px;
    margin: 20px 0 16px; color: #94A3B8; font-size: 13px; font-weight: 500;
  }
  .klass-divider::before, .klass-divider::after { content: ''; flex: 1; height: 1px; background: #E2E8F0; }
  .klass-google-btn {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    width: 100%; padding: 13px 14px; border: 1px solid #E2E8F0; border-radius: 10px;
    background: #fff; color: #1E293B; font-size: 15px; font-weight: 600;
    text-decoration: none; cursor: pointer; transition: background 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
  }
  .klass-google-btn:hover { background: #F8FAFC; box-shadow: 0 8px 18px rgba(0,0,0,0.04); transform: translateY(-1px); }
  .klass-google-btn:focus { outline: none; box-shadow: 0 0 0 3px rgba(34,197,94,0.18); }
  .klass-meta { margin-top: 20px; text-align: center; }
  .klass-meta-lock { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #94A3B8; text-decoration: none; }
  .klass-error { font-size: 12px; color: #DC2626; margin-top: 4px; display: block; }
  @media (max-width: 480px) { .klass-login-form { padding: 0 16px; } }
</style>
@endpush

@section('content')
<div class="klass-login-form">
  <div class="klass-login-intro">
    <span class="klass-login-logo-frame">
      <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-login-logo" alt="KlassApp">
    </span>
    <p class="klass-intro-title">Welcome back</p>
    <p class="klass-intro-sub">Sign in to your school dashboard.</p>
  </div>
  @if(\Config::get('settings.login_status', 1) == 0)
    <div class="klass-maintenance">Login page is under maintenance</div>
  @else
    <form method="POST" action="/login" aria-label="{{ __('Login') }}">
      @csrf
      <div class="klass-field">
        <label class="klass-label" for="email">Email, Phone, Name, or Registration Number</label>
        <input id="email" type="text" class="klass-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="your@school.edu" required>
        @if ($errors->has('email'))
          <span class="klass-error" role="alert">{{ $errors->first('email') }}</span>
        @endif
      </div>
      <div class="klass-field">
        <label class="klass-label" for="password">Password</label>
        <div class="klass-password-wrap">
          <input id="password" type="password" class="klass-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
          <button class="klass-password-toggle" type="button" onclick="var i=document.getElementById('password');if(i)i.type=i.type==='password'?'text':'password';" aria-label="Show or hide password">
            <svg viewBox="0 0 24 24" fill="none"><path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/></svg>
          </button>
        </div>
        @if ($errors->has('password'))
          <span class="klass-error" role="alert">{{ $errors->first('password') }}</span>
        @endif
      </div>
      <div class="klass-actions-row">
        <div class="klass-checkbox-row">
          <input id="remember" type="checkbox" class="klass-checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
          <label for="remember" class="klass-checkbox-label">Remember me</label>
        </div>
        <a href="{{ route('password.email') }}" class="klass-forgot-link">Forgot password?</a>
      </div>
      <button type="submit" class="klass-submit">Sign in</button>
    </form>
    <div class="klass-divider">or</div>
    <a href="{{ url('/auth/google') }}" class="klass-google-btn">
      <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      Sign in with Google
    </a>
    <div class="klass-meta">
      <p style="font-size:13px;color:#64748B;">Don't have an account? <a href="{{ route('register') }}" style="color:#22C55E;font-weight:600;text-decoration:none;">Sign up</a></p>
      <a href="{{ url('/') }}" class="klass-meta-lock">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        Go to KlassApp website
      </a>
    </div>
  @endif
</div>
@endsection
