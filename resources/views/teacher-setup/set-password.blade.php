{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.auth-preview')

@section('title', 'Set your password — ' . ($school->name ?? 'KlassApp'))

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-testid="teacher-setup-password">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => 'Set up your class at ' . ($school->name ?? 'KlassApp'),
      'support' => 'One password, then confirm your class, learners and subjects.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card">
        <h1 class="ap-title">Set your password</h1>
        <p class="ap-sub">
          {{ $teacher->name }} · {{ $teacher->email }}<br>
          You’ll go straight to “Set up my class”.
        </p>

        @if(session('error'))
          <div class="ap-alert ap-alert--error" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
          <div class="ap-alert ap-alert--error" role="alert">
            @foreach($errors->all() as $error)<p style="margin:0 0 4px;">{{ $error }}</p>@endforeach
          </div>
        @endif

        <form method="POST" action="{{ route('teacher.setup.claim', $token) }}">
          @csrf
          <div class="ap-field">
            <label for="password" class="ap-label">Password</label>
            <input id="password" name="password" type="password" class="ap-input" required autocomplete="new-password" autofocus>
            <p class="ap-hint">At least 8 characters, with a lower-case letter, an upper-case letter and a number.</p>
          </div>
          <div class="ap-field">
            <label for="password_confirmation" class="ap-label">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="ap-input" required autocomplete="new-password">
          </div>
          <button type="submit" class="ds-btn ds-btn-primary ds-btn-md w-full">Set password and continue</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
