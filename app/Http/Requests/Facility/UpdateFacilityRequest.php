<?php

namespace App\Http\Requests\Facility;

use App\Models\Facility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('facility.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:200'],
            'type' => ['sometimes', 'required', Rule::in(Facility::TYPES)],
            'address' => ['sometimes', 'nullable', 'string', 'max:300'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'required', Rule::in(Facility::STATUSES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge([
                'name' => trim($this->string('name')->toString()),
            ]);
        }

        if ($this->has('address') && is_string($this->input('address'))) {
            $this->merge([
                'address' => trim($this->string('address')->toString()),
            ]);
        }
    }
}
