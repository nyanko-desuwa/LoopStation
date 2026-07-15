<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('event.manage_rewards') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000'],
            'remaining' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
