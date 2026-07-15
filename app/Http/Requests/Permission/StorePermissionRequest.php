<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('permission.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'resource' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/'],
            'action' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/'],
            'code' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
                Rule::unique('permissions', 'code'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['resource', 'action', 'code', 'name'] as $field) {
            if ($this->filled($field)) {
                $merge[$field] = trim($this->string($field)->toString());
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
