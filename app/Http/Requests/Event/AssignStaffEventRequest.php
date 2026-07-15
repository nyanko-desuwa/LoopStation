<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class AssignStaffEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('event.assign_staff') ?? false;
    }

    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
