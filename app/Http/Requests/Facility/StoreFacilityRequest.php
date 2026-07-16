<?php

namespace App\Http\Requests\Facility;

use App\Models\Facility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Gate bằng RBAC; middleware permission:facility.create cũng chặn trước.
        return $this->user()?->hasPermission('facility.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'type' => ['required', Rule::in(Facility::TYPES)],
            'address' => ['nullable', 'string', 'max:300'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(Facility::STATUSES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge([
                'name' => trim($this->string('name')->toString()),
            ]);
        }

        if ($this->filled('address')) {
            $this->merge([
                'address' => trim($this->string('address')->toString()),
            ]);
        }
    }
}
