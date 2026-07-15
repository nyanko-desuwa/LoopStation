<?php

namespace App\Http\Requests\WasteType;

use Illuminate\Foundation\Http\FormRequest;

class StoreWasteTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        // Manager tạo loại chuẩn; user có create_custom tạo loại riêng.
        return $user->hasPermission('waste_type.create')
            || $user->hasPermission('waste_type.create_custom');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            // Chỉ manager (waste_type.create) mới được set is_system=true.
            'is_system' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge([
                'name' => trim($this->string('name')->toString()),
            ]);
        }

        if ($this->filled('icon')) {
            $this->merge([
                'icon' => trim($this->string('icon')->toString()),
            ]);
        }
    }
}
