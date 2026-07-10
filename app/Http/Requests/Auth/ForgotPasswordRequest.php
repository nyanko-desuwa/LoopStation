<?php

namespace App\Http\Requests\Auth;

use App\Rules\ExistingCanonicalEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', Rule::email()->rfcCompliant(strict: true), 'max:150', new ExistingCanonicalEmail()],
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
