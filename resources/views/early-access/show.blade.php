{{-- SPDX-License-Identifier: MIT --}}
{{-- Early access page. Served at /early-access, and at /register while SIGNUP_ENABLED=false. --}}
@extends('layouts.auth-preview')

@section('title', 'Request early access')

@section('content')
<div class="ap-page" data-ap-paper="vintage" data-ap-layout="split" data-ap-screen="early-access">
  @include('auth.preview._paper-bg')
  <div class="ap-shell">
    @include('auth.preview._brand-panel', [
      'tagline' => 'An open education protocol for humans and agents.',
      'support' => 'KlassApp opens to new schools soon. Ask for early access and we will be in touch.',
    ])
    <div class="ap-form-panel">
      <div class="ap-form-shell ap-card ap-card--wide">
        <h1 class="ap-title">Request early access</h1>

        <div class="ap-note" data-testid="early-access-countdown">
          <p class="ap-sub" style="margin:0;">Opening on
            <time datetime="{{ $launchAt->format('Y-m-d\TH:i:s\Z') }}" id="ea-launch-date" data-testid="early-access-launch-date">{{ $launchAt->format('j F Y, H:i') }} UTC</time>
          </p>
          <p id="ea-countdown" aria-hidden="true" hidden style="margin:4px 0 0;font-variant-numeric:tabular-nums;"></p>
          <p id="ea-countdown-sr" class="sr-only" role="status" aria-live="polite" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;"></p>
        </div>

        @if ($submitted)
          <div class="ap-alert ap-alert--success" role="status" data-testid="early-access-success">
            Thank you. Your request is in, and we will contact you before launch.
          </div>
          <div class="ap-meta"><a href="{{ route('login') }}">Back to sign in</a></div>
        @else
          @if (session('failmessage'))
            <div class="ap-alert ap-alert--error" role="alert">{{ session('failmessage') }}</div>
          @endif
          @php
            $eaFields = [
              ['school_name', 'School name', 'text', 'organization'],
              ['contact_name', 'Your name', 'text', 'name'],
              ['contact_role', 'Your role', 'text', 'organization-title'],
              ['email', 'Email address', 'email', 'email'],
              ['phone', 'Phone number', 'tel', 'tel'],
              ['country', 'Country', 'select', 'country-name'],
              ['approx_students', 'Approximate number of students', 'number', 'off'],
            ];
            $eaFirstError = collect(array_merge(array_column($eaFields, 0), ['consent']))
              ->first(fn ($f) => $errors->has($f));
          @endphp
          @if ($eaFirstError)
            {{-- One line only; each message is shown next to its field. --}}
            <p class="ap-alert ap-alert--error" role="alert" data-testid="early-access-error-summary" style="margin:0 0 12px;">
              Please check the highlighted {{ count(array_intersect(array_keys($errors->getMessages()), array_merge(array_column($eaFields, 0), ['consent']))) === 1 ? 'field' : 'fields' }}. <a href="#ea-{{ $eaFirstError }}">Go to the first one</a>
            </p>
          @endif

          <form method="POST" action="{{ route('early-access.store') }}" class="ap-form" aria-label="Request early access" data-testid="early-access-form" novalidate>
            @csrf
            @foreach ($eaFields as [$field, $label, $type, $autocomplete])
              <div class="ap-field">
                <label class="ap-label" for="ea-{{ $field }}">{{ $label }}</label>
                @if ($type === 'select')
                  <select id="ea-{{ $field }}" name="{{ $field }}" class="ap-input{{ $errors->has($field) ? ' is-invalid' : '' }}" autocomplete="{{ $autocomplete }}" required data-testid="early-access-country" @if($errors->has($field)) aria-invalid="true" aria-describedby="ea-{{ $field }}-error" @endif>
                    <option value="" @selected(old($field) === null || old($field) === '')>Choose your country</option>
                    @foreach ($countries as $countryName)
                      <option value="{{ $countryName }}" data-example="{{ \App\Support\PhoneCountry::example($countryName) }}" @selected(old($field) === $countryName)>{{ $countryName }}</option>
                    @endforeach
                  </select>
                @else
                  <input id="ea-{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ old($field) }}" class="ap-input{{ $errors->has($field) ? ' is-invalid' : '' }}" autocomplete="{{ $autocomplete }}" required @if($type === 'number') min="1" inputmode="numeric" @endif @if($errors->has($field)) aria-invalid="true" @endif @if($field === 'phone') aria-describedby="ea-phone-hint{{ $errors->has($field) ? ' ea-phone-error' : '' }}" @elseif($errors->has($field)) aria-describedby="ea-{{ $field }}-error" @endif>
                @endif
                @if ($field === 'phone')
                  @php $eaPhoneExample = \App\Support\PhoneCountry::example(old('country')); @endphp
                  <p class="ap-meta" id="ea-phone-hint" data-testid="early-access-phone-hint" style="margin:4px 0 0;">{{ $eaPhoneExample ? 'Your phone number, e.g. '.$eaPhoneExample : 'Your phone number, including the country code.' }}</p>
                @endif
                @if ($errors->has($field))
                  <span class="ap-error" id="ea-{{ $field }}-error" data-testid="early-access-error-{{ $field }}">{{ $errors->first($field) }}</span>
                @endif
              </div>
            @endforeach

            {{-- Spam trap: hidden from people and assistive tech. --}}
            <div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">
              <label for="ea-website">Leave this empty</label>
              <input id="ea-website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="ap-field ap-checkbox-row">
              <input id="ea-consent" type="checkbox" class="ap-checkbox" name="consent" value="1" @checked(old('consent')) required @if($errors->has('consent')) aria-invalid="true" aria-describedby="ea-consent-error" @endif>
              <label for="ea-consent" class="ap-checkbox-label">
                @if ($privacyUrl !== '')
                  I agree that KlassApp may store these details to contact me about early access, as described in the <a href="{{ $privacyUrl }}" target="_blank" rel="noopener" data-testid="early-access-privacy-link">privacy notice</a>.
                @else
                  I agree that KlassApp may store these details to contact me about early access.
                @endif
              </label>
            </div>
            @if ($errors->has('consent'))
              <span class="ap-error" id="ea-consent-error" data-testid="early-access-error-consent">{{ $errors->first('consent') }}</span>
            @endif

            <button type="submit" class="ap-submit" data-testid="ap-primary-submit">Request early access</button>

            <div class="ap-meta">
              Already have an account? <a href="{{ route('login') }}">Sign in</a>
            </div>
          </form>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var time = document.getElementById('ea-launch-date');
  var visual = document.getElementById('ea-countdown');
  var sr = document.getElementById('ea-countdown-sr');
  if (!time || !visual || !sr || !window.Intl) return;
  var target = Date.parse(time.getAttribute('datetime'));
  if (isNaN(target)) return;

  // Visitor's local time for the fixed UTC instant.
  try {
    time.textContent = new Intl.DateTimeFormat(undefined, { dateStyle: 'long', timeStyle: 'short' }).format(new Date(target));
  } catch (e) { /* keep the static UTC fallback */ }

  /* ea-countdown-label:start */
  function eaUnit(n, word) { return n + ' ' + (n === 1 ? word : word + 's'); }
  function eaCountdownLabel(ms, withSeconds) {
    ms = Math.max(0, ms);
    if (ms === 0) return 'Early access is open.';
    var d = Math.floor(ms / 86400000), h = Math.floor(ms / 3600000) % 24, m = Math.floor(ms / 60000) % 60, s = Math.floor(ms / 1000) % 60;
    var out = [eaUnit(d, 'day'), eaUnit(h, 'hour'), eaUnit(m, 'minute')];
    if (withSeconds) out.push(eaUnit(s, 'second'));
    return out.join(', ') + ' to go';
  }
  /* ea-countdown-label:end */
  function label(withSeconds) { return eaCountdownLabel(target - Date.now(), withSeconds); }

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  // Announced to screen readers once; the ticking text is aria-hidden.
  sr.textContent = label(false);
  visual.hidden = false;
  function tick() { visual.textContent = label(!reduce); }
  tick();
  // Reduced motion: update once a minute instead of every second.
  setInterval(tick, reduce ? 60000 : 1000);
})();
(function () {
  var country = document.getElementById('ea-country');
  var hint = document.getElementById('ea-phone-hint');
  if (!country || !hint) return;
  function sync() {
    var opt = country.options[country.selectedIndex];
    var example = opt ? (opt.getAttribute('data-example') || '') : '';
    hint.textContent = example
      ? 'Your phone number, e.g. ' + example
      : 'Your phone number, including the country code.';
  }
  country.addEventListener('change', sync);
  sync();
})();
</script>
@endpush
