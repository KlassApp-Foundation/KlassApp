<?php

namespace App\Http\Requests;

use App\Models\Country;
use App\Models\User;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\pL\s'\-]+$/u"],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+?256)?0?7[0578]\d{7}$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $normalized = app(\App\Services\SchoolSignupBootstrapService::class)->normalizePhone((string) $value);
                    if ($normalized === null) {
                        $fail('Enter a valid WhatsApp phone number.');

                        return;
                    }
                    if (User::where('mobile_no', $normalized)->exists()) {
                        $fail('This phone number is already registered.');
                    }
                    if (\App\Models\School::where('phone', $normalized)->exists()) {
                        $fail('This phone number is already registered to a school.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'termsandcondn' => ['required', 'accepted'],
            // The register select posts countries.name. Also accept a real id
            // or short/ISO code so a value that is in the table is not rejected
            // for using a different column. countries is a global list.
            'country' => ['nullable', 'string', 'max:100', $this->countryMustExist()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Your full name is required.',
            'name.regex' => 'Please enter your full name using letters, spaces, hyphens, and apostrophes only.',
            'email.required' => 'Email is required.',
            'email.unique' => 'This email is already registered.',
            'phone.required' => 'Phone (WhatsApp) is required.',
            'phone.regex' => 'Enter a valid Ugandan WhatsApp number (e.g. 07XX XXX XXX or +2567XXXXXXXX).',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'termsandcondn.required' => 'Please agree to the Terms and Conditions.',
            'termsandcondn.accepted' => 'Please agree to the Terms and Conditions.',
            'country.exists' => 'Choose a country from the list.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('mobile_no') && ! $this->filled('phone')) {
            $this->merge(['phone' => $this->input('mobile_no')]);
        }

        if (! $this->filled('country')) {
            $this->merge(['country' => OnboardingStepsService::DEFAULT_COUNTRY]);
        }

        // Store the countries.name the rest of the app reads, even when the
        // posted value was an id or a code. validated() keeps this merge.
        $match = $this->matchingCountry(trim((string) $this->input('country')));
        if ($match !== null) {
            $this->merge(['country' => $match->name]);
        }
    }

    /**
     * Reject a country that is not in the countries table. A blank field is
     * already the default (Uganda) before this runs. When the table has no
     * rows, that default is still allowed so signup is not blocked before
     * countries are seeded; any other value is rejected.
     */
    private function countryMustExist(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $value = trim((string) $value);
            if ($value === '' || ! Schema::hasTable('countries')) {
                return;
            }

            if ($this->matchingCountry($value) !== null) {
                return;
            }

            if (! Country::query()->exists()
                && strcasecmp($value, OnboardingStepsService::DEFAULT_COUNTRY) === 0) {
                return;
            }

            $fail('Choose a country from the list.');
        };
    }

    private function matchingCountry(string $value): ?Country
    {
        if ($value === '' || ! Schema::hasTable('countries')) {
            return null;
        }

        return Country::query()->where(function ($query) use ($value): void {
            $query->whereRaw('LOWER(name) = ?', [strtolower($value)]);
            if (Schema::hasColumn('countries', 'short_name')) {
                $query->orWhereRaw('LOWER(short_name) = ?', [strtolower($value)]);
            }
            if (Schema::hasColumn('countries', 'iso_code')) {
                $query->orWhereRaw('LOWER(iso_code) = ?', [strtolower($value)]);
            }
            if (ctype_digit($value)) {
                $query->orWhere('id', (int) $value);
            }
        })->first();
    }
}
