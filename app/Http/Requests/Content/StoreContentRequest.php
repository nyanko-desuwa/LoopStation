<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('content.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string'],
            'thumbnail_url' => ['nullable', 'string', 'max:500'],
            'timer_seconds' => ['sometimes', 'integer', 'min:1', 'max:86400'],
            'points_reward' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('title')) {
            $this->merge(['title' => trim($this->string('title')->toString())]);
        }
    }
}
