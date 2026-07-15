<?php

namespace App\Http\Requests\Event;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('event.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string'],
            'location' => ['sometimes', 'required', 'string', 'max:300'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'start_time' => ['sometimes', 'required', 'date'],
            'end_time' => ['sometimes', 'required', 'date', 'after:start_time'],
            'expired_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'required', Rule::in(Event::STATUSES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('title')) {
            $this->merge(['title' => trim($this->string('title')->toString())]);
        }
        if ($this->filled('location')) {
            $this->merge(['location' => trim($this->string('location')->toString())]);
        }
    }
}
