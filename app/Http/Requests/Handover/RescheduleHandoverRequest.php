<?php

namespace App\Http\Requests\Handover;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->hasPermission('handover.reschedule')
            || $user->hasPermission('handover.update')
            || $user->hasPermission('handover.create')
        );
    }

    public function rules(): array
    {
        return [
            'appointment_time' => ['required', 'date', 'after:now'],
        ];
    }
}
