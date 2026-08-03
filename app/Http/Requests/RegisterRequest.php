<?php

namespace App\Http\Requests;

use App\Models\User;
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
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('mobile_no') && ! $this->filled('phone')) {
            $this->merge(['phone' => $this->input('mobile_no')]);
        }
    }
}
