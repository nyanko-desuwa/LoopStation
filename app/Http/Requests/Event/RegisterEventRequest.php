<?php

namespace App\Http\Requests\Event;

use App\Models\EventRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('event_registration.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'registration_type' => [
                'sometimes',
                Rule::in([EventRegistration::TYPE_VISIT, EventRegistration::TYPE_HANDOVER]),
            ],
        ];
    }
}
