<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_details' => $this->payment_details ? json_encode($this->payment_details) : null,
            'plan_details'    => $this->plan_details ? json_encode($this->plan_details) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'school_id'         => 'nullable|integer|exists:schools,id',
            'user_id'           => 'nullable|integer|exists:users,id',           // Allow changing user if needed
            'plan_id'           => 'nullable|integer|exists:plans,id',
            
            'status'            => 'nullable|in:pending,approve,cancel,expired',
            
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            
            'amount_paid'       => 'nullable|numeric|min:0|max:9999999999.99',
            'payment_reference' => 'nullable|string|max:255',
            'payment_method'    => 'nullable|string|max:100',
            
            'payment_details'   => 'nullable|json',
            'plan_details'      => 'nullable|json',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'user_id.exists'        => 'Selected user does not exist.',
            'school_id.exists'      => 'Selected school does not exist.',
            'plan_id.exists'        => 'Selected plan does not exist.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'amount_paid.min'       => 'Amount paid cannot be negative.',
        ];
    }
}