{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.auth-preview')

@section('title', 'Choose how to set up ' . $school->name)

@section('content')
@php
    $toshiPathAvailable = (bool) ($toshiPathAvailable ?? false);
@endphp
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-ap-screen="onboarding-choice">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => 'An open education protocol for humans and agents.',
      'support' => $toshiPathAvailable
        ? 'Two ways to finish setting up. You can switch later; nothing is locked in.'
        : 'Finish setting up step by step. Chat setup with Toshi is coming soon.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card ap-card--wide">
        <h1 class="ap-title">How would you like to set up {{ $school->name }}?</h1>
        <p class="ap-sub">{{ $toshiPathAvailable
            ? 'Both paths cover the same steps: school details, classes, subjects, teachers, students, terms, fees, WhatsApp, and plan.'
            : 'The Wizard covers school details, classes, subjects, teachers, students, terms, fees, WhatsApp, and plan.' }}</p>

        @if (session('successmessage'))
          <div class="ap-alert ap-alert--success" role="status">{{ session('successmessage') }}</div>
        @endif

        <form method="POST" action="{{ route('onboarding.choice.store') }}" class="ap-form" aria-label="Choose onboarding path">
          @csrf
          <div style="display:grid;gap:14px;">
            <button type="submit" name="path" value="wizard" class="ap-submit" data-testid="onboarding-choice-wizard" style="text-align:left;padding:18px 20px;">
              <strong style="display:block;font-size:1rem;">Set up step by step</strong>
              <span style="display:block;font-weight:400;opacity:.9;margin-top:4px;">Use the Wizard. Best when you have your class list and fee structure ready.</span>
            </button>
            @include('auth.partials.toshi-path-choice', ['toshiPathAvailable' => $toshiPathAvailable])
          </div>
        </form>

        <p class="ap-meta" style="margin-top:16px;">{{ $toshiPathAvailable
            ? 'You can change paths from the dashboard later.'
            : 'You can continue setup from the dashboard.' }}</p>
      </div>
    </div>
  </div>
</div>
@endsection
