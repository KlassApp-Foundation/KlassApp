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
      radial-gradient(35% 30% at 85% 15%, rgba(30, 111, 217, 0.06) 0%, transparent 70%),
      radial-gradient(30% 25% at 75% 85%, rgba(34, 197, 94, 0.04) 0%, transparent 60%),
      radial-gradient(35% 30% at 20% 85%, rgba(30, 111, 217, 0.04) 0%, transparent 60%);
    pointer-events: none;
  }

  .klass-form {
    width: 100%;
    max-width: 600px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .klass-field-full { grid-column: 1 / -1; }

  .klass-register-intro {
    text-align: center;
  }

  .klass-register-logo-frame {
    width: 160px;
    margin: 0 auto;
    display: block;
  }

  .klass-register-logo {
    width: 100%;
    height: auto;
    display: block;
  }

  .klass-intro-title {
    margin: 14px 0 0;
    color: #0F172A;
    font-size: 22px;
    font-weight: 700;
    line-height: 1.3;
    font-family: "Sora", sans-serif;
    letter-spacing: -0.02em;
  }

  .klass-intro-sub {
    margin: 6px 0 0;
    color: #64748B;
    font-size: 14px;
    line-height: 1.5;
  }

  .klass-intro-divider {
    width: 100%;
    height: 1px;
    margin: 18px 0 0;
    background: #E2E8F0;
  }

  .klass-form {
    margin-top: 22px;
  }

  .klass-field {
    margin-bottom: 16px;
  }

  .klass-label {
    display: block;
    margin-bottom: 6px;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
  }

  .klass-input,
  .klass-select {
    width: 100%;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    padding: 13px 16px;
    font-size: 15px;
    color: #0F172A;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
    outline: none;
    min-height: 50px;
    font-family: "DM Sans", sans-serif;
  }

  .klass-input:focus,
  .klass-select:focus {
    border-color: #22C55E;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
  }

  .klass-input::placeholder {
    color: #94A3B8;
  }

  .klass-input.is-invalid {
    border-color: #EF4444;
  }

  .klass-input.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
  }

  .klass-select {
    -webkit-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='%2394A3B8' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-position: right 14px center;
    background-repeat: no-repeat;
    background-size: 18px;
    padding-right: 42px;
    cursor: pointer;
  }

  .klass-phone-shell {
    width: 100%;
    display: flex;
    align-items: center;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    background: #F8FAFC;
    min-height: 50px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
    overflow: hidden;
  }

  .klass-phone-shell:focus-within {
    border-color: #22C55E;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
  }

  .klass-phone-prefix {
    margin-left: 8px;
    background: #22C55E;
    color: #052E16;
    font-size: 13px;
    font-weight: 700;
    border-radius: 8px;
    padding: 6px 12px;
    white-space: nowrap;
    line-height: 1.2;
  }

  .klass-phone-divider {
    width: 1px;
    height: 24px;
    background: #CBD5E1;
    margin: 0 8px;
    flex: 0 0 auto;
  }

  .klass-phone-shell .klass-input {
    border: 0 !important;
    box-shadow: none !important;
    background: transparent;
    min-height: 48px;
    padding-left: 4px;
    padding-right: 14px;
    flex: 1;
    min-width: 0;
    width: 100%;
  }

  .klass-phone-shell .klass-input:focus {
    border: 0;
    box-shadow: none;
  }

  .klass-phone-shell .klass-input:disabled {
    color: #94A3B8;
    cursor: not-allowed;
  }

  .klass-password-wrap {
    position: relative;
  }

  .klass-password-wrap .klass-input {
    padding-right: 48px;
  }

  .klass-password-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    width: 26px;
    height: 26px;
    color: #64748B;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: color 0.15s ease;
  }

  .klass-password-toggle:hover {
    color: #22C55E;
  }

  .klass-password-toggle:focus {
    outline: none;
    color: #22C55E;
  }

  .klass-password-toggle svg {
    width: 20px;
    height: 20px;
  }

  .klass-checkbox-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 12px 0 0;
  }

  .klass-checkbox {
    margin-top: 2px;
    appearance: none;
    width: 18px;
    height: 18px;
    border: 1.5px solid #CBD5E1;
    border-radius: 5px;
    background: #FFFFFF;
    position: relative;
    cursor: pointer;
    flex: 0 0 auto;
    transition: all 0.15s ease;
  }

  .klass-checkbox:checked {
    background: #22C55E;
    border-color: #22C55E;
  }

  .klass-checkbox:checked::after {
    content: "";
    position: absolute;
    left: 5px;
    top: 1px;
    width: 5px;
    height: 10px;
    border: solid #FFFFFF;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
  }

  .klass-checkbox-label {
    color: #475569;
    font-size: 14px;
    line-height: 1.5;
    cursor: pointer;
  }

  .klass-checkbox-label a {
    color: #22C55E;
    font-weight: 600;
    text-decoration: none;
  }

  .klass-checkbox-label a:hover {
    text-decoration: underline;
  }

  .klass-error {
    margin-top: 6px;
    display: block;
    color: #EF4444;
    font-size: 12px;
    line-height: 1.4;
    font-weight: 600;
  }

  .klass-submit {
    margin-top: 18px;
    width: 100%;
    border: 0;
    border-radius: 10px;
    background: #22C55E;
    color: #052E16;
    font-size: 16px;
    font-weight: 700;
    padding: 14px 20px;
    cursor: pointer;
    transition: all 0.18s ease;
    font-family: "DM Sans", sans-serif;
    letter-spacing: 0.01em;
  }

  .klass-submit:hover {
    background: #16A34A;
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(34, 197, 94, 0.25);
  }

  .klass-submit:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.3);
  }

  .klass-submit:active {
    transform: scale(0.98);
  }

  .klass-divider {
    display: flex;
    align-items: center;
    gap: 14px;
    margin: 20px 0 16px;
    color: #94A3B8;
    font-size: 13px;
    font-weight: 500;
  }

  .klass-divider::before,
  .klass-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #E2E8F0;
  }

  .klass-google-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 13px 14px;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    background: #FFFFFF;
    color: #334155;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.18s ease;
    cursor: pointer;
    font-family: "DM Sans", sans-serif;
  }

  .klass-google-btn:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
  }

  .klass-google-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
  }

  .klass-google-btn:active {
    transform: scale(0.98);
  }

  .klass-meta {
    margin-top: 16px;
    text-align: center;
  }

  .klass-meta-lock {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #94A3B8;
    font-size: 12px;
    font-weight: 500;
  }

  .klass-meta-lock svg {
    color: #22C55E;
  }

  .klass-meta-login {
    margin-top: 10px;
    color: #64748B;
    font-size: 14px;
  }

  .klass-meta-login a {
    color: #22C55E;
    font-weight: 600;
    text-decoration: none;
  }

  .klass-meta-login a:hover {
    text-decoration: underline;
  }

  .klass-errors-all {
    margin-top: 16px;
    padding: 12px 16px;
    border: 1px solid rgba(239, 68, 68, 0.18);
    border-radius: 10px;
    background: #FEF2F2;
    color: #991B1B;
    font-size: 13px;
    line-height: 1.5;
  }

  .klass-errors-all p {
    margin: 0;
  }

  .klass-errors-all p + p {
    margin-top: 4px;
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

  @media (max-width: 640px) {
    .klass-register-page {
      padding: 14px;
    }
    .klass-register-card {
      padding: 28px 24px;
    }
    .klass-phone-shell {
      flex-wrap: nowrap;
      min-height: 46px;
    }
    .klass-phone-prefix {
      font-size: 12px;
      padding: 4px 8px;
    }
    .klass-phone-divider {
      margin: 0 4px;
    }
  }
</style>
@endpush

@section('content')

<div class="klass-register-page">
    <div class="klass-register-intro" style="width:100%;max-width:600px;">
      <span class="klass-register-logo-frame">
        <img src="{{ asset('images/klassapp-logo-primary.svg') }}" class="klass-register-logo" alt="KlassApp">
      </span>
      <p class="klass-intro-title">Your school's future starts here.</p>
      <p class="klass-intro-sub">Register your school and start your free 30-day Growth trial.</p>
      <div class="klass-intro-divider"></div>
    </div>

    @if(\Config::get('settings.register')==1)
      <div class="klass-maintenance">
        Register page is under maintenance!!!
      </div>
    @else
      <form method="POST" action="{{ route('register') }}" aria-label="{{ __('Register') }}" class="klass-form">
        @csrf

        <div class="klass-field">
          <input type="hidden" name="plan" value="{{ session('selected_plan') }}">
        </div>

        <div class="klass-field klass-field-full">
          <label class="klass-label" for="school_name">School Name</label>
          <input id="school_name" type="text" class="klass-input{{ $errors->has('school_name') ? ' is-invalid' : '' }}" name="school_name" value="{{ old('school_name') }}" placeholder="name of the school" required>
          @if ($errors->has('school_name'))
            <span class="klass-error" role="alert">{{ $errors->first('school_name') }}</span>
          @endif
        </div>

        <div class="klass-field klass-field-full">
          <label class="klass-label" for="name">Your Full Name</label>
          <input id="name" type="text" class="klass-input{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" placeholder="Your name" required>
          @if ($errors->has('name'))
            <span class="klass-error" role="alert">{{ $errors->first('name') }}</span>
          @endif
        </div>

        <div class="klass-field">
          <label class="klass-label" for="country">Country</label>
          <select id="country" name="country" class="klass-select">
            <option value="" @if(old('country')=='') selected @endif>Select country</option>
            <option value="Uganda" @if(old('country')=='Uganda') selected @endif>Uganda</option>
            <option value="Kenya" @if(old('country')=='Kenya') selected @endif>Kenya</option>
            <option value="Tanzania" @if(old('country')=='Tanzania') selected @endif>Tanzania</option>
            <option value="Rwanda" @if(old('country')=='Rwanda') selected @endif>Rwanda</option>
            <option value="Nigeria" @if(old('country')=='Nigeria') selected @endif>Nigeria</option>
            <option value="Ghana" @if(old('country')=='Ghana') selected @endif>Ghana</option>
            <option value="South Africa" @if(old('country')=='South Africa') selected @endif>South Africa</option>
            <option value="United Kingdom" @if(old('country')=='United Kingdom') selected @endif>United Kingdom</option>
            <option value="United States" @if(old('country')=='United States') selected @endif>United States</option>
            <option value="Afghanistan" @if(old('country')=='Afghanistan') selected @endif>Afghanistan</option>
            <option value="Albania" @if(old('country')=='Albania') selected @endif>Albania</option>
            <option value="Algeria" @if(old('country')=='Algeria') selected @endif>Algeria</option>
            <option value="Argentina" @if(old('country')=='Argentina') selected @endif>Argentina</option>
            <option value="Australia" @if(old('country')=='Australia') selected @endif>Australia</option>
            <option value="Austria" @if(old('country')=='Austria') selected @endif>Austria</option>
            <option value="Bangladesh" @if(old('country')=='Bangladesh') selected @endif>Bangladesh</option>
            <option value="Belgium" @if(old('country')=='Belgium') selected @endif>Belgium</option>
            <option value="Botswana" @if(old('country')=='Botswana') selected @endif>Botswana</option>
            <option value="Brazil" @if(old('country')=='Brazil') selected @endif>Brazil</option>
            <option value="Cameroon" @if(old('country')=='Cameroon') selected @endif>Cameroon</option>
            <option value="Canada" @if(old('country')=='Canada') selected @endif>Canada</option>
            <option value="China" @if(old('country')=='China') selected @endif>China</option>
            <option value="Cote d'Ivoire" @if(old('country')=="Cote d'Ivoire") selected @endif>Cote d'Ivoire</option>
            <option value="Denmark" @if(old('country')=='Denmark') selected @endif>Denmark</option>
            <option value="Egypt" @if(old('country')=='Egypt') selected @endif>Egypt</option>
            <option value="Ethiopia" @if(old('country')=='Ethiopia') selected @endif>Ethiopia</option>
            <option value="Finland" @if(old('country')=='Finland') selected @endif>Finland</option>
            <option value="France" @if(old('country')=='France') selected @endif>France</option>
            <option value="Germany" @if(old('country')=='Germany') selected @endif>Germany</option>
            <option value="India" @if(old('country')=='India') selected @endif>India</option>
            <option value="Indonesia" @if(old('country')=='Indonesia') selected @endif>Indonesia</option>
            <option value="Ireland" @if(old('country')=='Ireland') selected @endif>Ireland</option>
            <option value="Israel" @if(old('country')=='Israel') selected @endif>Israel</option>
            <option value="Italy" @if(old('country')=='Italy') selected @endif>Italy</option>
            <option value="Japan" @if(old('country')=='Japan') selected @endif>Japan</option>
            <option value="Malawi" @if(old('country')=='Malawi') selected @endif>Malawi</option>
            <option value="Malaysia" @if(old('country')=='Malaysia') selected @endif>Malaysia</option>
            <option value="Morocco" @if(old('country')=='Morocco') selected @endif>Morocco</option>
            <option value="Mozambique" @if(old('country')=='Mozambique') selected @endif>Mozambique</option>
            <option value="Namibia" @if(old('country')=='Namibia') selected @endif>Namibia</option>
            <option value="Netherlands" @if(old('country')=='Netherlands') selected @endif>Netherlands</option>
            <option value="New Zealand" @if(old('country')=='New Zealand') selected @endif>New Zealand</option>
            <option value="Pakistan" @if(old('country')=='Pakistan') selected @endif>Pakistan</option>
            <option value="Philippines" @if(old('country')=='Philippines') selected @endif>Philippines</option>
            <option value="Portugal" @if(old('country')=='Portugal') selected @endif>Portugal</option>
            <option value="Saudi Arabia" @if(old('country')=='Saudi Arabia') selected @endif>Saudi Arabia</option>
            <option value="Senegal" @if(old('country')=='Senegal') selected @endif>Senegal</option>
            <option value="Sierra Leone" @if(old('country')=='Sierra Leone') selected @endif>Sierra Leone</option>
            <option value="Singapore" @if(old('country')=='Singapore') selected @endif>Singapore</option>
            <option value="Somalia" @if(old('country')=='Somalia') selected @endif>Somalia</option>
            <option value="South Korea" @if(old('country')=='South Korea') selected @endif>South Korea</option>
            <option value="Spain" @if(old('country')=='Spain') selected @endif>Spain</option>
            <option value="Sweden" @if(old('country')=='Sweden') selected @endif>Sweden</option>
            <option value="Switzerland" @if(old('country')=='Switzerland') selected @endif>Switzerland</option>
            <option value="Turkey" @if(old('country')=='Turkey') selected @endif>Turkey</option>
            <option value="United Arab Emirates" @if(old('country')=='United Arab Emirates') selected @endif>United Arab Emirates</option>
            <option value="Zambia" @if(old('country')=='Zambia') selected @endif>Zambia</option>
            <option value="Zimbabwe" @if(old('country')=='Zimbabwe') selected @endif>Zimbabwe</option>
            <option value="Other" @if(old('country')=='Other') selected @endif>Other</option>
          </select>
        </div>

        <div class="klass-field">
          <label class="klass-label" for="mobile_no">Mobile Number (WhatsApp)</label>
          <div class="klass-phone-shell" id="mobile_shell">
            <span class="klass-phone-prefix" id="mobile_prefix" hidden>+256</span>
            <span class="klass-phone-divider" id="mobile_divider" hidden></span>
            <input id="mobile_no" type="tel" class="klass-input{{ $errors->has('mobile_no') ? ' is-invalid' : '' }}" name="mobile_no" value="{{ old('mobile_no') }}" placeholder="Select your country first" inputmode="numeric" pattern="0?[0-9]{9,10}" minlength="9" maxlength="11" title="Please enter your local number only, without the leading zero — between 9 and 10 digits." required>
          </div>
          @if ($errors->has('mobile_no'))
            <span class="klass-error" role="alert">{{ $errors->first('mobile_no') }}</span>
          @endif
        </div>

        <div class="klass-field klass-field-full">
          <label class="klass-label" for="student_size">Approximate number of students</label>
          <select id="student_size" name="student_size" class="klass-select">
            <option value="" @if(old('student_size')=='') selected @endif>Select school size</option>
            <option value="Under 100 students" @if(old('student_size')=='Under 100 students') selected @endif>Under 100 students</option>
            <option value="100 to 500 students" @if(old('student_size')=='100 to 500 students') selected @endif>100 to 500 students</option>
            <option value="500 to 1,000 students" @if(old('student_size')=='500 to 1,000 students') selected @endif>500 to 1,000 students</option>
            <option value="1,000 to 3,000 students" @if(old('student_size')=='1,000 to 3,000 students') selected @endif>1,000 to 3,000 students</option>
            <option value="Over 3,000 students" @if(old('student_size')=='Over 3,000 students') selected @endif>Over 3,000 students</option>
          </select>
        </div>

        <div class="klass-field klass-field-full">
          <label class="klass-label" for="email">Email Address</label>
          <input id="email" type="email" class="klass-input{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="you@school.edu" required>
          @if ($errors->has('email'))
            <span class="klass-error" role="alert">{{ $errors->first('email') }}</span>
          @endif
        </div>

        <div class="klass-field">
          <label class="klass-label" for="password">Password</label>
          <div class="klass-password-wrap">
            <input id="password" type="password" class="klass-input{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
            <button class="klass-password-toggle" type="button" data-target="password" aria-label="Show or hide password">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"></path>
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"></circle>
              </svg>
            </button>
          </div>
          @if ($errors->has('password'))
            <span class="klass-error" role="alert">{{ $errors->first('password') }}</span>
          @endif
        </div>

        <div class="klass-field">
          <label class="klass-label" for="password-confirm">Confirm Password</label>
          <div class="klass-password-wrap">
            <input id="password-confirm" type="password" class="klass-input" name="password_confirmation" required>
            <button class="klass-password-toggle" type="button" data-target="password-confirm" aria-label="Show or hide confirm password">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M2 12C3.9 8.2 7.5 6 12 6C16.5 6 20.1 8.2 22 12C20.1 15.8 16.5 18 12 18C7.5 18 3.9 15.8 2 12Z" stroke="currentColor" stroke-width="1.7"></path>
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"></circle>
              </svg>
            </button>
          </div>
        </div>

        <div class="klass-field klass-field-full klass-checkbox-row">
          <input id="termsandcondn" type="checkbox" class="klass-checkbox" name="termsandcondn" value="1" @if(old('termsandcondn')==1) checked @endif>
          <label for="termsandcondn" class="klass-checkbox-label">
            I Agree to <a href="{{ url('/terms-of-service') }}" target="_blank">Terms and Conditions</a>
          </label>
          @if ($errors->has('termsandcondn'))
            <span class="klass-error" role="alert">{{ $errors->first('termsandcondn') }}</span>
          @endif
        </div>

        <button type="submit" class="klass-submit klass-field-full">Create my free account</button>

        <div class="klass-divider klass-field-full">
          <span>or</span>
        </div>

        <a href="{{ url('/auth/google') }}" class="klass-google-btn klass-field-full">
          <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          {{ __('Continue with Google') }}
        </a>

        <div class="klass-meta klass-field-full">
          <p class="klass-meta-lock">
            <svg viewBox="0 0 20 20" width="14" height="14" fill="none" aria-hidden="true">
              <rect x="4" y="9" width="12" height="8" rx="2" stroke="currentColor" stroke-width="1.6"></rect>
              <path d="M7 9V7.2C7 5.43 8.34 4 10 4C11.66 4 13 5.43 13 7.2V9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
            </svg>
            Free 30-day Growth trial &middot; No credit card required
          </p>
          <p class="klass-meta-login">Already have an account? <a href="{{ url('/login') }}">Sign in here.</a></p>
        </div>

        @if ($errors->any())
          <div class="klass-errors-all">
            @foreach ($errors->all() as $error)
              <p>{{ $error }}</p>
            @endforeach
          </div>
        @endif
      </form>
    @endif
</div>


@push('scripts')
<script>
(function() {
  'use strict';
  var toggles = document.querySelectorAll('.klass-password-toggle');
  for (var i = 0; i < toggles.length; i++) {
    toggles[i].addEventListener('click', function(e) {
      e.preventDefault();
      var input = document.getElementById(this.getAttribute('data-target'));
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  }

  var country = document.getElementById('country');
  var mobile = document.getElementById('mobile_no');
  var prefix = document.getElementById('mobile_prefix');
  var divider = document.getElementById('mobile_divider');
  if (country && mobile && prefix && divider) {
    var codes = { Uganda: '+256', Kenya: '+254', Tanzania: '+255', Rwanda: '+250', Nigeria: '+234', Ghana: '+233', 'South Africa': '+27', 'United Kingdom': '+44', 'United States': '+1' };
    function upd(clear) {
      var val = country.value;
      if (!val) { prefix.hidden = true; divider.hidden = true; mobile.disabled = true; if (clear) mobile.value = ''; mobile.placeholder = 'Select your country first'; return; }
      if (val === 'Other') { prefix.hidden = true; divider.hidden = true; mobile.disabled = false; mobile.placeholder = 'Enter number with code'; return; }
      var code = codes[val];
      if (code) { prefix.textContent = code; prefix.hidden = false; divider.hidden = false; mobile.disabled = false; mobile.placeholder = '712345678'; }
      else { prefix.hidden = true; divider.hidden = true; mobile.disabled = false; mobile.placeholder = 'Enter number'; }
    }
    country.addEventListener('change', function() { upd(true); });
    upd(false);
  }
})();
</script>
@endpush
