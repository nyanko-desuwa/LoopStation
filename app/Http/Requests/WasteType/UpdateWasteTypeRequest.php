<?php

namespace App\Http\Requests\WasteType;

use App\Models\WasteType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWasteTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        /** @var WasteType $wasteType */
        $wasteType = $this->route('waste_type');

        // Manager full update.
        if ($user->hasPermission('waste_type.update')) {
            return true;
        }

        // Owner của custom type được sửa (không cần waste_type.update).
        return ! $wasteType->is_system
            && $wasteType->created_by === $user->id
            && $user->hasPermission('waste_type.create_custom');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge([
                'name' => trim($this->string('name')->toString()),
            ]);
        }

        if ($this->has('icon') && is_string($this->input('icon'))) {
            $this->merge([
                'icon' => trim($this->string('icon')->toString()),
            ]);
        }
    }
}
