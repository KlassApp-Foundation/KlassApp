{{-- SPDX-License-Identifier: MIT --}}
{{-- "Check your email" step (Part D2): accepts the 6-digit code OR the emailed Confirm-email link.
     Polls /register/verify/status so this tab continues on its own when the link is opened elsewhere. --}}
@extends('layouts.auth-preview')

@section('title', 'Check your email')

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-ap-screen="verify-code">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => 'Check your inbox',
      'support' => 'Tap Confirm email in our message, or enter the 6-digit code, to finish signing up.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card">
        <h1 class="ap-title">{{ __('Check your email') }}</h1>
        <p class="ap-sub">We sent a message to <strong>{{ $email }}</strong>. Tap <strong>Confirm email</strong> in it, or enter the 6-digit code here.</p>

        @if (session('status'))
          <div class="ap-alert ap-alert--success" role="status" data-testid="verify-code-status">{{ session('status') }}</div>
        @endif
        @if ($errors->has('code'))
          <div class="ap-alert ap-alert--error" role="alert" data-testid="auth-flash-error">{{ $errors->first('code') }}</div>
        @elseif ($errors->any())
          <div class="ap-alert ap-alert--error" role="alert" data-testid="auth-flash-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.verify.submit') }}" class="ap-form" data-testid="verify-code-form">
          @csrf

          <div class="ap-field">
            <label class="ap-label" for="code">{{ __('6-digit code') }}</label>
            <input id="code" type="text" class="ap-input ap-input--code{{ $errors->has('code') ? ' is-invalid' : '' }}"
                   name="code" value="{{ old('code') }}" placeholder="000000"
                   inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus
                   autocomplete="one-time-code" data-testid="verify-code-input">
            @if ($errors->has('code'))
              <span class="ap-error" role="alert">{{ $errors->first('code') }}</span>
            @endif
          </div>

          <p class="ap-sub" style="font-size: 13px;">The code and the link work for {{ $minutes }} minutes and can be used once.</p>

          <button type="submit" class="ap-submit" data-testid="ap-primary-submit">{{ __('Confirm email') }}</button>
        </form>

        <div class="ap-note" role="status" data-testid="verify-code-waiting"
             data-verify-poll
             data-status-url="{{ route('register.verify.status') }}"
             data-expires-at="{{ $expiresAt }}">
          <span data-verify-wait>Confirmed on your phone? This page continues on its own.</span>
          <span data-verify-expired hidden>This code has expired. Resend to get a new code and link.</span>
        </div>

        <form method="POST" action="{{ route('register.verify.continue') }}" id="verify-continue-form" hidden>
          @csrf
        </form>

        <form method="POST" action="{{ route('register.verify.resend') }}" style="margin-top: 8px;">
          @csrf
          <button type="submit" class="ap-back" style="min-height: 44px;" data-testid="verify-code-resend">{{ __('Resend') }}</button>
        </form>
        <a href="{{ route('register') }}" class="ap-back" style="margin-top: 0; min-height: 44px; display: flex; align-items: center;">Change email</a>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var box = document.querySelector('[data-verify-poll]');
  if (!box) { return; }

  var url = box.getAttribute('data-status-url');
  var expiresAt = parseInt(box.getAttribute('data-expires-at'), 10) * 1000;
  var POLL_MS = 5000;
  var timer = null;
  var stopped = false;

  function stop() {
    stopped = true;
    if (timer) { clearInterval(timer); timer = null; }
  }

  function showExpired() {
    stop();
    box.querySelector('[data-verify-wait]').hidden = true;
    box.querySelector('[data-verify-expired]').hidden = false;
  }

  function check() {
    if (stopped) { return; }
    if (Date.now() >= expiresAt) { showExpired(); return; }

    fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (data && data.confirmed === true) {
          stop();
          document.getElementById('verify-continue-form').submit();
        }
      })
      .catch(function () { /* offline or transient: try again on the next tick */ });
  }

  function start() {
    if (!timer && !stopped) { timer = setInterval(check, POLL_MS); }
  }

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') {
      check();
      start();
    } else if (timer) {
      clearInterval(timer);
      timer = null;
    }
  });

  if (document.visibilityState === 'visible') { start(); }
})();
</script>
@endpush
