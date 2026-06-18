<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreCurrentPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    // #[Override]
    protected function prepareForValidation()
    {
        $user = Auth::user();
        return $this->merge([
            "school_id" => $user->school_id
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       return [
        'school_id' => ['required', 'exists:schools,id'],
        'plan_id' => ['required', 'exists:plans,id'],
        'status' => ['nullable', 'in:pending,running,expired'],
    ];
    }
}
