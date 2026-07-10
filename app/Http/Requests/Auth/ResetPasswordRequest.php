<?php

namespace App\Http\Requests\Auth;

use App\Rules\ExistingCanonicalEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', Rule::email()->rfcCompliant(strict: true), 'max:150', new ExistingCanonicalEmail()],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge([
                'email' => trim($this->string('email')->toString()),
            ]);
        }
    }
}
