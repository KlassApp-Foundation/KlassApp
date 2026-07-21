{{-- SPDX-License-Identifier: MIT --}}
<div>
  @if(\Config::get('settings.login_status')==0)
    <div style="margin-top:20px;padding:12px 16px;border-radius:10px;background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;font-size:14px;font-weight:600;">
      Login page under maintenance
    </div>
  @else
    @if (session('resent'))
      <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:#F0FDF4;border:1px solid #BBF7D0;color:#166534;font-size:13px;font-weight:600;">
        A fresh verification code has been sent to your email.
      </div>
    @endif

    <form method="POST" action="{{ url('/verifyotp') }}" aria-label="{{ __('Verify OTP') }}">
      @csrf

      <div class="klass-field">
        <label class="klass-label" for="otp">{{ __('Verification Code') }}</label>
        <div class="klass-otp-input-group">
          <span class="klass-otp-icon-left" aria-hidden="true">
            <svg class="fill-current w-4 h-4" version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve"><g><g>
              <path d="M469.333,64H42.667C19.135,64,0,83.135,0,106.667v298.667C0,428.865,19.135,448,42.667,448h426.667c23.531,0,42.667-19.135,42.667-42.667V106.667C512,83.135,492.865,64,469.333,64z M469.333,384c0,5.888-4.779,10.667-10.667,10.667H53.333c-5.888,0-10.667-4.779-10.667-10.667v-256c0-5.888,4.779-10.667,10.667-10.667h405.333c5.888,0,10.667,4.779,10.667,10.667V384z M448,128H64v256h384V128z M384,192h-42.667c-5.888,0-10.667,4.779-10.667,10.667v42.667c0,5.888,4.779,10.667,10.667,10.667H384c5.888,0,10.667-4.779,10.667-10.667v-42.667C394.667,196.779,389.888,192,384,192z M266.667,192H224c-5.888,0-10.667,4.779-10.667,10.667v42.667c0,5.888,4.779,10.667,10.667,10.667h42.667c5.888,0,10.667-4.779,10.667-10.667v-42.667C277.333,196.779,272.555,192,266.667,192z M149.333,192h-42.667c-5.888,0-10.667,4.779-10.667,10.667v42.667C96,251.221,100.779,256,106.667,256h42.667c5.888,0,10.667-4.779,10.667-10.667v-42.667C160,196.779,155.221,192,149.333,192z"></path>
            </g></g></svg>
          </span>
          <input id="otp" type="text" class="klass-otp-input" name="otp" value="{{ old('otp') }}" placeholder="000000" required autofocus>
        </div>
        @if ($errors->has('otp'))
          <p style="margin-top:6px;font-size:13px;color:#DC2626;">{{ $errors->first('otp') }}</p>
        @endif
      </div>

      <button type="submit" class="klass-otp-verify-btn">{{ __('Verify') }}</button>

      <div class="klass-otp-resend">
        {{ __("Didn't receive the code?") }}
        <a href="?resend=1&email={{ urlencode(auth()->user()->email ?? '') }}" class="klass-otp-resend-link" id="resend-link">{{ __('Resend') }}</a>
      </div>
    </form>
  @endif
</div>
