<?php

namespace App\Http\Requests\Handover;

use App\Models\HandoverRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('handover.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'classification_type' => ['nullable', Rule::in(HandoverRequest::CLASSIFICATIONS)],
            'estimated_weight' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit_id' => ['nullable', 'integer', 'exists:measurement_units,id'],
            'appointment_time' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.waste_type_id' => ['required', 'integer', 'distinct', 'exists:waste_types,id'],
            'items.*.weight' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'items.*.unit_id' => ['required', 'integer', 'exists:measurement_units,id'],
        ];
    }
}
