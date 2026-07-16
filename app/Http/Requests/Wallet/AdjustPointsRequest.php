<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('points.adjust') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'points' => ['required', 'integer', 'min:1', 'max:1000000'],
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'description' => ['required', 'string', 'max:300'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('description')) {
            $this->merge([
                'description' => trim($this->string('description')->toString()),
            ]);
        }
    }
}
