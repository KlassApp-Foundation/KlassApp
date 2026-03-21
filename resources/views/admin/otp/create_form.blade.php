{{-- SPDX-License-Identifier: MIT --}}
<style>
  .klass-otp-form-wrap {
    width: 100%;
    max-width: 360px;
    margin: 0 auto;
    text-align: center;
  }

  .klass-otp-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0b203e;
    margin-bottom: 0.5rem;
  }

  .klass-otp-subtitle {
    font-size: 0.875rem;
    color: #4b5563;
    margin-bottom: 1.25rem;
  }

  .klass-otp-input-group {
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 0.625rem 0.75rem;
    text-align: left;
  }

  .klass-otp-icon {
    color: #6b7280;
    display: inline-flex;
    margin-right: 0.5rem;
  }

  .klass-otp-input {
    width: 100%;
    border: 0;
    outline: none;
    font-size: 0.95rem;
    color: #0f172a;
    background: transparent;
  }

  .klass-otp-verify-btn {
    border: 0;
    color: #ffffff;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    width: 100%;
    border-radius: 999px;
    padding: 0.7rem 1rem;
    background: linear-gradient(90deg, #1d4ed8 0%, #10b981 100%);
    box-shadow: 0 10px 24px rgba(16, 185, 129, 0.35);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .klass-otp-verify-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 26px rgba(29, 78, 216, 0.35);
  }
</style>

<div class="klass-otp-form-wrap">
  @if(\Config::get('settings.login_status')==0)
    <div class="alert-box success">
      Login page under maintenance
    </div>
  @else
    <h2 class="klass-otp-title">{{ __('Verify OTP') }}</h2>
    <p class="klass-otp-subtitle">Enter the one-time password sent to your registered number.</p>

    <form method="POST" action="{{ url('/verifyotp') }}" aria-label="{{ __('Verify OTP') }}">
      @csrf

      <div class="my-5">
        <div class="klass-otp-input-group">
          <span class="klass-otp-icon" aria-hidden="true">
            <svg class="fill-current w-4 h-4" version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve"><g><g>
              <path d="M469.333,64H42.667C19.135,64,0,83.135,0,106.667v298.667C0,428.865,19.135,448,42.667,448h426.667C492.865,448,512,428.865,512,405.333V106.667C512,83.135,492.865,64,469.333,64z M42.667,85.333h426.667c1.572,0,2.957,0.573,4.432,0.897c-36.939,33.807-159.423,145.859-202.286,184.478c-3.354,3.021-8.76,6.625-15.479,6.625s-12.125-3.604-15.49-6.635C197.652,232.085,75.161,120.027,38.228,86.232C39.706,85.908,41.094,85.333,42.667,85.333z M21.333,405.333V106.667c0-2.09,0.63-3.986,1.194-5.896c28.272,25.876,113.736,104.06,169.152,154.453C136.443,302.671,50.957,383.719,22.46,410.893C21.957,409.079,21.333,407.305,21.333,405.333z M469.333,426.667H42.667c-1.704,0-3.219-0.594-4.81-0.974c29.447-28.072,115.477-109.586,169.742-156.009c7.074,6.417,13.536,12.268,18.63,16.858c8.792,7.938,19.083,12.125,29.771,12.125s20.979-4.188,29.76-12.115c5.096-4.592,11.563-10.448,18.641-16.868c54.268,46.418,140.286,127.926,169.742,156.009C472.552,426.073,471.039,426.667,469.333,426.667z M490.667,405.333c0,1.971-0.624,3.746-1.126,5.56c-28.508-27.188-113.984-108.227-169.219-155.668c55.418-50.393,140.869-128.57,169.151-154.456c0.564,1.91,1.194,3.807,1.194,5.897V405.333z"/>
            </g></g></svg>
          </span>
          <input id="otp" type="text" class="klass-otp-input{{ $errors->has('otp') ? ' is-invalid' : '' }}" placeholder="Enter OTP" name="otp" required>
        </div>

        @if ($errors->has('otp'))
          <span class="invalid-feedback text-red-500 text-xs font-semibold block mt-2" role="alert">
            {{ $errors->first('otp') }}
          </span>
        @endif
      </div>

      <div class="mt-6">
        <button type="submit" class="klass-otp-verify-btn">
          {{ __('Verify') }}
        </button>
      </div>
    </form>
  @endif
</div>