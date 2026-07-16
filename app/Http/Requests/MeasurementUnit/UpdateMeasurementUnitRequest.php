<?php

namespace App\Http\Requests\MeasurementUnit;

use App\Models\MeasurementUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeasurementUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('measurement_unit.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'symbol' => ['sometimes', 'required', 'string', 'max:20'],
            'category' => ['sometimes', 'required', Rule::in(MeasurementUnit::CATEGORIES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['name', 'symbol', 'category'] as $field) {
            if ($this->filled($field)) {
                $merge[$field] = trim($this->string($field)->toString());
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
