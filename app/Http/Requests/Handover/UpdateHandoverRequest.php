<?php

namespace App\Http\Requests\Handover;

use App\Models\HandoverRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var HandoverRequest $handover */
        $handover = $this->route('handover');

        return $user !== null
            && $handover->user_id === $user->id
            && ($user->hasPermission('handover.update') || $user->hasPermission('handover.create'));
    }

    public function rules(): array
    {
        return [
            'classification_type' => ['sometimes', 'nullable', Rule::in(HandoverRequest::CLASSIFICATIONS)],
            'estimated_weight' => ['sometimes', 'nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'unit_id' => ['sometimes', 'nullable', 'integer', 'exists:measurement_units,id'],
            'appointment_time' => ['sometimes', 'nullable', 'date', 'after:now'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.waste_type_id' => ['required_with:items', 'integer', 'distinct', 'exists:waste_types,id'],
            'items.*.weight' => ['required_with:items', 'numeric', 'min:0.01', 'max:999999.99'],
            'items.*.unit_id' => ['required_with:items', 'integer', 'exists:measurement_units,id'],
        ];
    }
}
