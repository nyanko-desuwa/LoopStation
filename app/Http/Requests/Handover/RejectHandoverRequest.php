<?php

namespace App\Http\Requests\Handover;

use Illuminate\Foundation\Http\FormRequest;

class RejectHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('handover.reject') ?? false;
    }

    public function rules(): array
    {
        return [
            'reject_reason' => ['required', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('reject_reason')) {
            $this->merge([
                'reject_reason' => trim($this->string('reject_reason')->toString()),
            ]);
        }
    }
}
