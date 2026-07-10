<?php

namespace App\Http\Requests\Auth;

use App\Rules\UniqueCanonicalEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['required', Rule::email()->rfcCompliant(strict: true), 'max:150', new UniqueCanonicalEmail()],
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
