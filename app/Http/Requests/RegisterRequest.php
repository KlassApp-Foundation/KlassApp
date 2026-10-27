<?php

namespace App\Http\Requests;

use App\Models\School;
use App\Models\User;
use App\Support\PhoneCountry;
use Illuminate\Foundation\Http\FormRequest;
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
                function (string $attribute, mixed $value, \Closure $fail) {
                    $normalized = PhoneCountry::normalise((string) $value, 'Uganda');
                    if ($normalized === null) {
                        $fail('Enter a valid phone number. The country code is filled in for Uganda; replace it if the number is from somewhere else.');

                        return;
                    }
                    $keys = PhoneCountry::keys((string) $value, 'Uganda');
                    if (User::matchingPhone((string) $value, 'Uganda')->exists()) {
                        $fail('This phone number is already registered.');
                    }
                    if (School::query()->whereIn('phone', $keys)->exists()) {
                        $fail('This phone number is already registered to a school.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'termsandcondn' => ['required', 'accepted'],
            'country' => ['nullable', 'string', 'max:100'],
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
            'phone.regex' => 'Enter a valid phone number, including its country code.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'termsandcondn.required' => 'Please agree to the Terms and Conditions.',
            'termsandcondn.accepted' => 'Please agree to the Terms and Conditions.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('mobile_no') && ! $this->filled('phone')) {
            $this->merge(['phone' => $this->input('mobile_no')]);
        }

        if (! $this->filled('country')) {
            $this->merge(['country' => \App\Services\OnboardingStepsService::DEFAULT_COUNTRY]);
        }

        $normalized = PhoneCountry::normalise((string) $this->input('phone'), 'Uganda');
        if ($normalized !== null) {
            $this->merge(['phone' => $normalized]);
        }
        }
    }
}
