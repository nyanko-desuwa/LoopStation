<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('event.manage_rewards') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge(['name' => trim($this->string('name')->toString())]);
        }
    }
}
