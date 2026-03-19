{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.empty')

@section('content')

<style>
  @import url("https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap");

  .klass-register-page {
    width: 100%;
    min-height: 100vh;
    display: grid;
    place-items: center;
    font-family: "Plus Jakarta Sans", sans-serif;
    background: #0D1526;
    position: relative;
    overflow: hidden;
    padding: 24px;
  }

  .klass-register-page::before {
    content: "";
    position: relative;
    position: absolute;
    inset: -12% -8%;
    background:
      radial-gradient(40% 30% at 18% 20%, rgba(30, 111, 217, 0.12) 0%, rgba(30, 111, 217, 0) 78%),
      radial-gradient(32% 28% at 82% 18%, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0) 76%),
      radial-gradient(36% 28% at 70% 82%, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0) 74%),
      radial-gradient(38% 30% at 20% 88%, rgba(255, 255, 255, 0.06) 0%, rgba(255, 255, 255, 0) 72%),
      repeating-linear-gradient(150deg, rgba(255, 255, 255, 0.03) 0 1px, rgba(255, 255, 255, 0) 1px 30px);
    pointer-events: none;
    opacity: 0.32;
  }

  .klass-register-page::after {
    content: "";
    position: absolute;
    inset: auto -18% -32% -18%;
    height: 66%;
    background:
      radial-gradient(46% 46% at 15% 90%, rgba(30, 111, 217, 0.14) 0%, rgba(30, 111, 217, 0) 76%),
      radial-gradient(44% 44% at 83% 72%, rgba(34, 197, 94, 0.11) 0%, rgba(34, 197, 94, 0) 74%),
      repeating-linear-gradient(145deg, rgba(255, 255, 255, 0.028) 0 1px, rgba(255, 255, 255, 0) 1px 34px);
    opacity: 0.28;
    pointer-events: none;
  }

  .klass-register-card {
    width: 100%;
    max-width: 520px;
    background: #EEF1F5;
    border-radius: 16px;
    box-shadow: 0 28px 60px rgba(5, 11, 27, 0.45), 0 8px 18px rgba(5, 11, 27, 0.28);
    padding: 48px;
    position: relative;
    z-index: 1;
  }

  .klass-register-intro {
    text-align: center;
  }

  .klass-register-logo-frame {
    width: 164px;
    margin: 0 auto;
    overflow: hidden;
    display: block;
  }

  .klass-register-logo {
    width: 236px;
    max-width: none;
    height: auto;
    margin-left: -36px;
    display: block;
  }

  .klass-intro-title {
    margin: 14px 0 0;
    color: #0d1526;
    font-size: 18px;
    font-weight: 600;
    line-height: 1.4;
  }

  .klass-intro-sub {
    margin: 8px 0 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
  }

  .klass-intro-divider {
    width: 100%;
    height: 1px;
    margin: 18px 0 0;
    background: #d7dee9;
  }

  .klass-maintenance {
    margin-top: 24px;
    border: 1px solid rgba(30, 111, 217, 0.28);
    background: rgba(30, 111, 217, 0.08);
    color: #0d1526;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 14px;
    font-weight: 600;
  }

  .klass-form {
    margin-top: 22px;
  }

  .klass-field {
    margin-bottom: 14px;
  }

  .klass-label {
    display: block;
    margin-bottom: 7px;
    color: #0d1526;
    font-size: 13px;
    font-weight: 600;
  }

  .klass-input,
  .klass-select {
    width: 100%;
    background: #F6F8F8;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 15px;
    color: #0d1526;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
    outline: none;
    min-height: 48px;
  }

  .klass-input:focus,
  .klass-select:focus {
    border-color: #1e6fd9;
    box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.12);
  }

  .klass-select {
    -webkit-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='%230D1526' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 18px;
    padding-right: 40px;
  }

  .klass-phone-shell {
    width: 100%;
    display: flex;
    align-items: center;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    background: #F6F8F8;
    min-height: 48px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
    overflow: hidden;
  }

  .klass-phone-shell:focus-within {
    border-color: #1e6fd9;
    box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.12);
  }

  .klass-phone-prefix {
    margin-left: 8px;
    background: #0D1526;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    border-radius: 999px;
    padding: 6px 10px;
    white-space: nowrap;
    line-height: 1.2;
  }

  .klass-phone-divider {
    width: 1px;
    height: 24px;
    background: #d1d9e6;
    margin: 0 8px;
    flex: 0 0 auto;
  }

  .klass-phone-shell .klass-input {
    border: 0;
    box-shadow: none;
    background: transparent;
    min-height: 46px;
    padding-left: 4px;
    padding-right: 14px;
  }

  .klass-phone-shell .klass-input:focus {
    border: 0;
    box-shadow: none;
  }

  .klass-phone-shell .klass-input:disabled {
    color: #94a3b8;
    cursor: not-allowed;
  }

  .klass-password-wrap {
    position: relative;
  }

  .klass-password-wrap .klass-input {
    padding-right: 46px;
  }

  .klass-password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    width: 26px;
    height: 26px;
    color: #1e6fd9;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
  }

  .klass-password-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(30, 111, 217, 0.2);
    border-radius: 6px;
  }

  .klass-password-toggle svg {
    width: 20px;
    height: 20px;
  }

  .klass-checkbox-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 10px 0 0;
  }

  .klass-checkbox {
    margin-top: 2px;
    appearance: none;
    width: 18px;
    height: 18px;
    border: 1.5px solid #94a3b8;
    border-radius: 5px;
    background: #ffffff;
    position: relative;
    cursor: pointer;
    flex: 0 0 auto;
  }

  .klass-checkbox:checked {
    background: #22c55e;
    border-color: #22c55e;
  }

  .klass-checkbox:checked::after {
    content: "";
    position: absolute;
    left: 5px;
    top: 1px;
    width: 5px;
    height: 10px;
    border: solid #0d1526;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
  }

  .klass-checkbox-label {
    color: #334155;
    font-size: 14px;
    line-height: 1.5;
  }

  .klass-checkbox-label a {
    color: #1e6fd9;
    font-weight: 600;
    text-decoration: none;
  }

  .klass-checkbox-label a:hover {
    text-decoration: underline;
  }

  .klass-error {
    margin-top: 6px;
    display: block;
    color: #dc2626;
    font-size: 12px;
    line-height: 1.4;
    font-weight: 600;
  }

  .klass-submit {
    margin-top: 18px;
    width: 100%;
    border: 0;
    border-radius: 8px;
    background: #22c55e;
    color: #0d1526;
    font-size: 16px;
    font-weight: 700;
    padding: 14px;
    cursor: pointer;
    transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
  }

  .klass-submit:hover {
    background: #18b455;
    transform: translateY(-1px);
    box-shadow: 0 10px 18px rgba(34, 197, 94, 0.2);
  }

  .klass-submit:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.18);
  }

  .klass-meta {
    margin-top: 14px;
    text-align: center;
  }

  .klass-meta-lock {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #64748b;
    font-size: 12px;
    font-weight: 500;
  }

  .klass-meta-login {
    margin-top: 9px;
    color: #334155;
    font-size: 14px;
  }

  .klass-meta-login a {
    color: #1e6fd9;
    font-weight: 600;
    text-decoration: none;
  }

  .klass-meta-login a:hover {
    text-decoration: underline;
  }

  .klass-errors-all {
    margin-top: 16px;
    padding: 12px 14px;
    border: 1px solid rgba(220, 38, 38, 0.18);
    border-radius: 10px;
    background: #fef2f2;
    color: #991b1b;
    font-size: 12px;
    line-height: 1.5;
  }

  .klass-errors-all p {
    margin: 0;
  }

  @media (max-width: 767px) {
    .klass-register-page {
      padding: 14px;
    }

    .klass-register-card {
      padding: 28px;
    }
  }
</style>

<div class="klass-register-page">
  <div class="klass-register-card">
    <div class="klass-register-intro">
      <span class="klass-register-logo-frame">
        <img src="{{ asset('images/klassapp-logo-primary.png') }}" class="klass-register-logo" alt="KlassApp">
      </span>
      <p class="klass-intro-title">Your school's future starts here.</p>
      <p class="klass-intro-sub">Register your school and start your free 30-day Pro trial.</p>
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
            <label class="klass-label" for="school_name">School Name</label>
            <input id="school_name" type="text" class="klass-input" name="school_name" value="{{ old('school_name') }}" placeholder="e.g. Lincoln Academy" required>
            @if ($errors->has('school_name'))
              <span class="klass-error" role="alert">{{ $errors->first('school_name') }}</span>
            @endif
          </div>

          <div class="klass-field">
            <label class="klass-label" for="name">Your Full Name</label>
            <input id="name" type="text" class="klass-input{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" placeholder="Your name" required>
            @if ($errors->has('name'))
              <span class="klass-error" role="alert">{{ $errors->first('name') }}</span>
            @endif
          </div>

          <div class="klass-field">
            <label class="klass-label" for="role">Your Role</label>
            <select id="role" name="role" class="klass-select">
              <option value="" @if(old('role')=='') selected @endif>Select your role</option>
              <option value="Principal or Head Teacher" @if(old('role')=='Principal or Head Teacher') selected @endif>Principal or Head Teacher</option>
              <option value="School Administrator" @if(old('role')=='School Administrator') selected @endif>School Administrator</option>
              <option value="Teacher" @if(old('role')=='Teacher') selected @endif>Teacher</option>
              <option value="IT or Tech Staff" @if(old('role')=='IT or Tech Staff') selected @endif>IT or Tech Staff</option>
              <option value="Other" @if(old('role')=='Other') selected @endif>Other</option>
            </select>
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
            <label class="klass-label" for="mobile_no">Mobile Number</label>
            <div class="klass-phone-shell" id="mobile_shell">
              <span class="klass-phone-prefix" id="mobile_prefix" hidden>+256</span>
              <span class="klass-phone-divider" id="mobile_divider" hidden></span>
              <input id="mobile_no" type="tel" class="klass-input{{ $errors->has('mobile_no') ? ' is-invalid' : '' }}" name="mobile_no" value="{{ old('mobile_no') }}" placeholder="Select your country first" inputmode="numeric" pattern="0?[0-9]{9,10}" minlength="9" maxlength="11" title="Please enter your local number only, without the leading zero — between 9 and 10 digits." required>
            </div>
            @if ($errors->has('mobile_no'))
              <span class="klass-error" role="alert">{{ $errors->first('mobile_no') }}</span>
            @endif
          </div>

          <div class="klass-field">
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

          <div class="klass-field">
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

          <div class="klass-checkbox-row">
            <input id="termsandcondn" type="checkbox" class="klass-checkbox" name="termsandcondn" value="1" @if(old('termsandcondn')==1) checked @endif>
            <label for="termsandcondn" class="klass-checkbox-label">
              I Agree to <a href="{{ url('/terms-of-service') }}" target="_blank">Terms and Conditions</a>
            </label>
          </div>
          @if ($errors->has('termsandcondn'))
            <span class="klass-error" role="alert">{{ $errors->first('termsandcondn') }}</span>
          @endif

          <button type="submit" class="klass-submit">Create my free account</button>

          <div class="klass-meta">
            <p class="klass-meta-lock">
              <svg viewBox="0 0 20 20" width="14" height="14" fill="none" aria-hidden="true">
                <rect x="4" y="9" width="12" height="8" rx="2" stroke="currentColor" stroke-width="1.6"></rect>
                <path d="M7 9V7.2C7 5.43 8.34 4 10 4C11.66 4 13 5.43 13 7.2V9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
              </svg>
              Free 30-day Pro trial · No credit card needed
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
</div>

<script>
  (function () {
    var toggles = document.querySelectorAll('.klass-password-toggle');

    for (var i = 0; i < toggles.length; i++) {
      toggles[i].addEventListener('click', function () {
        var targetId = this.getAttribute('data-target');
        var input = document.getElementById(targetId);

        if (!input) {
          return;
        }

        input.type = input.type === 'password' ? 'text' : 'password';
      });
    }

    var countrySelect = document.getElementById('country');
    var mobileInput = document.getElementById('mobile_no');
    var mobilePrefix = document.getElementById('mobile_prefix');
    var mobileDivider = document.getElementById('mobile_divider');

    if (countrySelect && mobileInput && mobilePrefix && mobileDivider) {
      var countryMap = {
        'Uganda': { code: '+256', flag: '🇺🇬' },
        'Kenya': { code: '+254', flag: '🇰🇪' },
        'Tanzania': { code: '+255', flag: '🇹🇿' },
        'Rwanda': { code: '+250', flag: '🇷🇼' },
        'Nigeria': { code: '+234', flag: '🇳🇬' },
        'Ghana': { code: '+233', flag: '🇬🇭' },
        'South Africa': { code: '+27', flag: '🇿🇦' },
        'United Kingdom': { code: '+44', flag: '🇬🇧' },
        'United States': { code: '+1', flag: '🇺🇸' }
      };

      function hidePrefix() {
        mobilePrefix.hidden = true;
        mobileDivider.hidden = true;
      }

      function showPrefix(text) {
        mobilePrefix.textContent = text;
        mobilePrefix.hidden = false;
        mobileDivider.hidden = false;
      }

      function updateMobileByCountry(clearExistingValue) {
        var selectedCountry = countrySelect.value;

        if (!selectedCountry) {
          hidePrefix();
          mobileInput.disabled = true;
          if (clearExistingValue) {
            mobileInput.value = '';
          }
          mobileInput.placeholder = 'Select your country first';
          return;
        }

        if (selectedCountry === 'Other') {
          hidePrefix();
          mobileInput.disabled = false;
          if (clearExistingValue) {
            mobileInput.value = '';
          }
          mobileInput.placeholder = 'Enter your number with country code';
          return;
        }

        if (countryMap[selectedCountry]) {
          showPrefix(countryMap[selectedCountry].flag + ' ' + countryMap[selectedCountry].code);
          mobileInput.disabled = false;
          if (clearExistingValue) {
            mobileInput.value = '';
          }
          mobileInput.placeholder = 'Enter phone number';
          return;
        }

        hidePrefix();
        mobileInput.disabled = false;
        if (clearExistingValue) {
          mobileInput.value = '';
        }
        mobileInput.placeholder = 'Enter your number with country code';
      }

      updateMobileByCountry(false);
      countrySelect.addEventListener('change', function () {
        updateMobileByCountry(true);
      });
    }
  })();
</script>
@endsection
