<?php

namespace App\Http\Requests\Handover;

use Illuminate\Foundation\Http\FormRequest;

class AssignStaffHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('handover.assign_staff') ?? false;
    }

    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
