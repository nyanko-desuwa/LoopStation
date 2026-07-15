<?php

namespace App\Http\Requests\Permission;

use App\Models\RolePermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('role_permission.update') ?? false;
    }

    public function rules(): array
    {
        return [
            // role lấy từ URL; body role nếu gửi phải khớp (check ở controller).
            'role' => ['sometimes', Rule::in(RolePermission::ROLES)],
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ];
    }
}
