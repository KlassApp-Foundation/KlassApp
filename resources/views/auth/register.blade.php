{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@push('styles')
<style>
  *, *::before, *::after { box-sizing: border-box; }

  .klass-register-page {
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

  .klass-register-page::before {
    content: "";
    position: absolute;
    inset: -20% -10%;
    background:
      radial-gradient(40% 35% at 15% 20%, rgba(34, 197, 94, 0.08) 0%, transparent 70%),
      radial-gradient(35% 30% at 85% 15%, rgba(30, 111, 217, 0.06) 0%, transparent 70%);
    pointer-events: none;
  }

  .klass-register-card {
    position: relative;
    width: 100%;
    max-width: 440px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 36px 32px;
    box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
  }

  .klass-register-logo {
    width: 140px;
    height: auto;
    display: block;
    margin: 0 auto 16px;
  }

  .klass-intro-title {
    margin: 0;
    text-align: center;
    color: #0F172A;
    font-size: 22px;
    font-weight: 700;
    font-family: "Sora", sans-serif;
    letter-spacing: -0.02em;
  }

  .klass-intro-sub {
    margin: 8px 0 0;
    text-align: center;
    color: #64748B;
    font-size: 14px;
    line-height: 1.5;
  }

  .klass-form { margin-top: 24px; }

  .klass-field { margin-bottom: 16px; }

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
    outline: none;
    min-height: 50px;
    font-family: "DM Sans", sans-serif;
  }

  .klass-input:focus {
    border-color: #22C55E;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
  }

  .klass-input.is-invalid { border-color: #EF4444; }

  .klass-error {
    display: block;
    margin-top: 6px;
    color: #DC2626;
    font-size: 12px;
  }

  .klass-checkbox-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
  }

  .klass-checkbox { margin-top: 3px; }

  .klass-checkbox-label {
    color: #475569;
    font-size: 13px;
    line-height: 1.4;
  }

  .klass-checkbox-label a { color: #1E6FD9; }

  .klass-submit {
    width: 100%;
    border: none;
    border-radius: 10px;
    background: #16A34A;
    color: #FFFFFF;
    font-size: 15px;
    font-weight: 700;
    min-height: 50px;
    cursor: pointer;
  }

  .klass-submit:hover { background: #15803D; }

  .klass-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 18px 0;
    color: #94A3B8;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }

  .klass-divider::before,
  .klass-divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: #E2E8F0;
  }

  .klass-google-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 50px;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    background: #FFFFFF;
    color: #0F172A;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
  }

  .klass-google-btn:hover { background: #F8FAFC; }

  .klass-meta {
    margin-top: 18px;
    text-align: center;
    color: #64748B;
    font-size: 14px;
  }

  .klass-meta a { color: #22C55E; font-weight: 600; text-decoration: none; }

  .klass-errors-all {
    margin-top: 16px;
    padding: 12px 16px;
    border: 1px solid rgba(239, 68, 68, 0.18);
    border-radius: 10px;
    background: #FEF2F2;
    color: #991B1B;
    font-size: 13px;
  }

  .klass-maintenance {
    margin-top: 20px;
    padding: 12px 16px;
    border: 1px solid rgba(34, 197, 94, 0.2);
    background: rgba(34, 197, 94, 0.06);
    color: #166534;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
  }
</style>
@endpush

@section('content')
<div class="klass-register-page">
  <div class="klass-register-card">
    <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-register-logo" alt="KlassApp">
    <p class="klass-intro-title">Create your KlassApp account</p>
    <p class="klass-intro-sub">Name, email, and WhatsApp — then finish school setup with Toshi.</p>

    @if(\Config::get('settings.register')==1)
      <div class="klass-maintenance">Register page is under maintenance!!!</div>
    @else
      <form method="POST" action="{{ route('register') }}" class="klass-form" id="saas-register-form" aria-label="{{ __('Register') }}">
        @csrf

        <div class="klass-field">
          <label class="klass-label" for="name">Your Full Name</label>
          <input id="name" type="text" class="klass-input{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" placeholder="Grace Nakato" required autocomplete="name">
          @if ($errors->has('name'))
            <span class="klass-error" role="alert">{{ $errors->first('name') }}</span>
          @endif
        </div>

        <div class="klass-field">
          <label class="klass-label" for="email">Email Address</label>
          <input id="email" type="email" class="klass-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="you@school.ug" required autocomplete="email">
          @if ($errors->has('email'))
            <span class="klass-error" role="alert">{{ $errors->first('email') }}</span>
          @endif
        </div>

        <div class="klass-field">
          <label class="klass-label" for="phone">Phone (WhatsApp)</label>
          <input id="phone" type="tel" class="klass-input{{ $errors->has('phone') ? ' is-invalid' : '' }}" name="phone" value="{{ old('phone') }}" placeholder="0701234567 or +256701234567" required autocomplete="tel">
          @if ($errors->has('phone'))
            <span class="klass-error" role="alert">{{ $errors->first('phone') }}</span>
          @endif
        </div>

        <div class="klass-field">
          <label class="klass-label" for="password">Password</label>
          <input id="password" type="password" class="klass-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" autocomplete="new-password">
          @if ($errors->has('password'))
            <span class="klass-error" role="alert">{{ $errors->first('password') }}</span>
          @endif
        </div>

        <div class="klass-field">
          <label class="klass-label" for="password-confirm">Confirm Password</label>
          <input id="password-confirm" type="password" class="klass-input" name="password_confirmation" autocomplete="new-password">
        </div>

        <div class="klass-field klass-checkbox-row">
          <input id="termsandcondn" type="checkbox" class="klass-checkbox" name="termsandcondn" value="1" @if(old('termsandcondn')==1) checked @endif required>
          <label for="termsandcondn" class="klass-checkbox-label">
            I agree to <a href="{{ url('/terms-of-service') }}" target="_blank">Terms and Conditions</a>
          </label>
        </div>
        @if ($errors->has('termsandcondn'))
          <span class="klass-error" role="alert">{{ $errors->first('termsandcondn') }}</span>
        @endif

        <button type="submit" class="klass-submit">Create account with password</button>

        <div class="klass-divider"><span>or</span></div>

        <button type="submit" class="klass-google-btn" formaction="{{ route('auth.google.start') }}" formmethod="post" formnovalidate>
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          Continue with Google
        </button>

        <div class="klass-meta">
          Already have an account? <a href="{{ url('/login') }}">Sign in</a>
        </div>

        @if ($errors->any())
          <div class="klass-errors-all">
            @foreach ($errors->all() as $error)
              <p>{{ $error }}</p>
            @endforeach
          </div>
        @endif

        @if (session('failmessage'))
          <div class="klass-errors-all"><p>{{ session('failmessage') }}</p></div>
        @endif
      </form>
    @endif
  </div>
</div>

@push('scripts')
<script>
(function () {
  var form = document.getElementById('saas-register-form');
  if (!form) return;
  form.addEventListener('submit', function (e) {
    var submitter = e.submitter;
    if (!submitter) return;
    var action = submitter.getAttribute('formaction') || '';
    if (action.indexOf('google') === -1) return;
    // Google path: password fields are not required
    var password = document.getElementById('password');
    var confirm = document.getElementById('password-confirm');
    if (password) { password.removeAttribute('required'); password.value = password.value || ''; }
    if (confirm) { confirm.removeAttribute('required'); }
  });
})();
</script>
@endpush
@endsection
