{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.auth-preview')

@section('title', 'Set up ' . $invite->school_name)

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-ap-screen="school-invite-claim">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => 'An open education protocol for humans and agents.',
      'support' => 'Your early-access request has been approved. Create the school admin account, then choose how to set up.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card ap-card--wide">
        <h1 class="ap-title">Set up {{ $invite->school_name }}</h1>
        <p class="ap-sub">This invite is for <strong>{{ $invite->email }}</strong>. It works once and expires {{ $invite->expires_at->format('j M Y \a\t g:i A') }}.</p>

        @if ($errors->any())
          <div class="ap-alert ap-alert--error" role="alert" data-testid="school-invite-error">
            {{ $errors->first() }}
          </div>
        @endif

        <form method="POST" action="{{ route('school-invite.claim', $token) }}" class="ap-form" aria-label="Create school admin account" novalidate>
          @csrf
          <div class="ap-field">
            <label class="ap-label" for="invite-name">Your full name</label>
            <input id="invite-name" type="text" name="name" value="{{ old('name', $invite->contact_name) }}" class="ap-input{{ $errors->has('name') ? ' is-invalid' : '' }}" required autocomplete="name">
          </div>
          <div class="ap-field">
            <label class="ap-label" for="invite-email">Email address</label>
            <input id="invite-email" type="email" value="{{ $invite->email }}" class="ap-input" disabled autocomplete="email">
            <p class="ap-meta" style="margin:4px 0 0;">This address is fixed by the invite.</p>
          </div>
          <div class="ap-field">
            <label class="ap-label" for="invite-phone">Phone (WhatsApp)</label>
            <input id="invite-phone" type="tel" name="phone" value="{{ old('phone') }}" class="ap-input{{ $errors->has('phone') ? ' is-invalid' : '' }}" placeholder="0701234567 or +256701234567" required autocomplete="tel">
          </div>
          <div class="ap-field">
            <label class="ap-label" for="invite-password">Password</label>
            <input id="invite-password" type="password" name="password" class="ap-input{{ $errors->has('password') ? ' is-invalid' : '' }}" required autocomplete="new-password">
            <p class="ap-meta" style="margin:4px 0 0;">At least 8 characters.</p>
          </div>
          <div class="ap-field">
            <label class="ap-label" for="invite-password-confirm">Confirm password</label>
            <input id="invite-password-confirm" type="password" name="password_confirmation" class="ap-input" required autocomplete="new-password">
          </div>
          <button type="submit" class="ap-submit" data-testid="school-invite-submit">Create school account</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
