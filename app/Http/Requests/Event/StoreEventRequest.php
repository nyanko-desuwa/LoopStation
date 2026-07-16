<?php

namespace App\Http\Requests\Event;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('event.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'location' => ['required', 'string', 'max:300'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'expired_at' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(Event::STATUSES)],
            'qr_code' => ['sometimes', 'string', 'max:100', 'unique:events,qr_code'],
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
