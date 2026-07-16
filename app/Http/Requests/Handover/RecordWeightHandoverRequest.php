<?php

namespace App\Http\Requests\Handover;

use Illuminate\Foundation\Http\FormRequest;

class RecordWeightHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('handover.record_weight') ?? false;
    }

    public function rules(): array
    {
        return [
            'weight' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit_id' => ['required', 'integer', 'exists:measurement_units,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
